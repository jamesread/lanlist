"""
LPPS (Lan Party Publishing Standard) version 2 — validation and field mapping for lanlist.
"""

from __future__ import annotations

import json
import re
from datetime import datetime
from functools import lru_cache
from pathlib import Path
from typing import Any

import jsonschema
from jsonschema import Draft7Validator

SCRIPT_DIR = Path(__file__).resolve().parent
SCHEMA_PATH = SCRIPT_DIR / "lan-party-publishing-standard-v2.schema"

LPPS_V2_DATETIME_RE = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$")
PUBLISHER_UNIQUE_ID_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$")

EVENT_STATUS_CANCELLED = "https://schema.org/EventCancelled"
TICKET_PRESALE = "https://schema.org/PreSale"

# ISO 3166-1 alpha-2 → lanlist venue country names (FormHelpers::getCountryList)
COUNTRY_CODE_TO_NAME: dict[str, str] = {
    "GB": "United Kingdom",
    "US": "United States",
    "IE": "Ireland",
    "DE": "Germany",
    "FR": "France",
    "NL": "Netherlands",
    "BE": "Belgium",
    "AU": "Australia",
    "NZ": "New Zealand",
    "CA": "Canada",
    "SE": "Sweden",
    "NO": "Norway",
    "DK": "Denmark",
    "FI": "Finland",
    "PL": "Poland",
    "AT": "Austria",
    "CH": "Switzerland",
    "ES": "Spain",
    "IT": "Italy",
    "PT": "Portugal",
    "CZ": "Czech Republic",
}


@lru_cache(maxsize=1)
def _schema_validator() -> Draft7Validator:
    schema = json.loads(SCHEMA_PATH.read_text(encoding="utf-8"))
    return Draft7Validator(schema)


def validate_lpps_v2_document(data: Any) -> tuple[bool, str]:
    """Return (ok, detail). Rejects version 1 and other non-v2 payloads."""
    if not isinstance(data, dict):
        return False, "root must be a JSON object"

    organisation = data.get("organisation")
    if not isinstance(organisation, dict):
        return False, "missing organisation object"

    if organisation.get("apiVersion") != 2:
        return False, "LPPS v2 required (organisation.apiVersion must be 2)"

    validator = _schema_validator()
    errors = sorted(validator.iter_errors(data), key=lambda e: list(e.path))
    if errors:
        first = errors[0]
        path = ".".join(str(p) for p in first.path) or "(root)"
        return False, f"schema validation failed at {path}: {first.message}"

    return True, "valid LPPS v2 document"


def sanitize_publisher_unique_id(value: Any) -> str | None:
    if value is None:
        return None
    text = str(value).strip()
    if not text or not PUBLISHER_UNIQUE_ID_RE.fullmatch(text):
        return None
    return text


def parse_lpps_v2_datetime(value: Any) -> datetime | None:
    if value is None:
        return None
    text = str(value).strip()
    if not text or not LPPS_V2_DATETIME_RE.match(text):
        return None
    try:
        dt = datetime.strptime(text, "%Y-%m-%dT%H:%M:%S")
    except ValueError:
        return None
    if dt.year < 1990 or dt.year > 2100:
        return None
    return dt


def country_name_from_code(code: Any) -> str:
    if code is None:
        return "Unknown"
    text = str(code).strip().upper()
    if len(text) == 2 and text.isalpha():
        return COUNTRY_CODE_TO_NAME.get(text, "Unknown")
    return "Unknown"


def map_sleeping_bitset(value: Any) -> int | None:
    num = _bitset_int(value, 15)
    if num is None:
        return None
    if num == 0:
        return 0
    if num & 1:
        return 1
    if num == 2:
        return 2
    if (num & 4) and (num & 8):
        return 5
    if num & 8:
        return 4
    if num & 4:
        return 3
    return 0


def map_alcohol_bitset(value: Any) -> int | None:
    num = _bitset_int(value, 15)
    if num is None or num == 0:
        return None
    if num & 1:
        return 0
    if (num & 2) and (num & 4):
        return 3
    if num & 4:
        return 2
    if num & 2 or num & 8:
        return 1
    return None


def map_smoking_bitset(value: Any) -> int | None:
    num = _bitset_int(value, 15)
    if num is None or num == 0:
        return None
    if num & 1:
        return None
    if num & 4:
        return 1
    if num & 2 or num & 8:
        return 0
    return None


def map_age_policy_bitset(value: Any) -> str | None:
    num = _bitset_int(value, 15)
    if num is None or num == 0:
        return None
    if num & 8:
        return "Over 18s Only"
    if num & 4 or num & 1:
        return "Under 18s must be accompanied"
    if num & 2:
        return "Under 18s require parents consent"
    return None


def event_is_cancelled(event: dict[str, Any]) -> bool:
    return event.get("eventStatus") == EVENT_STATUS_CANCELLED


def map_tickets(
    tickets: Any,
) -> tuple[float | None, float | None, str | None, str | None]:
    """Map v2 tickets[] to (priceInAdv, priceOnDoor, currency, ticketsNotReleasedUntil)."""
    if not isinstance(tickets, list):
        return None, None, None, None

    price_in_adv: float | None = None
    price_on_door: float | None = None
    currency: str | None = None
    presale_starts: list[datetime] = []

    for ticket in tickets:
        if not isinstance(ticket, dict):
            continue
        name = str(ticket.get("name") or "").lower()
        price = _ticket_price(ticket.get("price"))
        cur = _ticket_currency(ticket.get("priceCurrency"))
        if cur and currency is None:
            currency = cur

        if price is not None:
            if "door" in name:
                price_on_door = price
            elif price_in_adv is None or price < price_in_adv:
                price_in_adv = price

        if ticket.get("availability") == TICKET_PRESALE:
            dt = parse_lpps_v2_datetime(ticket.get("availabilityStarts"))
            if dt is not None:
                presale_starts.append(dt)

    tickets_until: str | None = None
    if presale_starts:
        tickets_until = min(presale_starts).strftime("%Y-%m-%d %H:%M:%S")

    return price_in_adv, price_on_door, currency, tickets_until


def count_venues_events(organisation: dict[str, Any]) -> tuple[int, int]:
    venues = organisation.get("venues")
    if not isinstance(venues, list):
        raise ValueError("organisation.venues must be an array")
    venue_count = len(venues)
    event_count = 0
    for venue in venues:
        if not isinstance(venue, dict):
            raise ValueError("each venue must be an object")
        events = venue.get("events")
        if isinstance(events, list):
            event_count += len(events)
    return venue_count, event_count


def _bitset_int(value: Any, max_v: int) -> int | None:
    if value is None or value == "":
        return None
    try:
        num = int(value)
    except (TypeError, ValueError):
        return None
    if num < 0 or num > max_v:
        return None
    return num


def _ticket_price(value: Any) -> float | None:
    if value is None or value == "":
        return None
    try:
        num = float(value)
    except (TypeError, ValueError):
        return None
    if num < 0 or num > 99999:
        return None
    return round(num, 2)


def _ticket_currency(value: Any) -> str | None:
    if value is None:
        return None
    text = str(value).strip().upper()
    if len(text) == 3 and text.isalpha():
        return text
    return None
