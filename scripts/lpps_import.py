"""
Import LPPS v2 organisation feeds into lanlist (used by lpps-crawl.py).

Version 1 feeds are rejected. See scripts/lan-party-publishing-standard-v2.schema.
"""

from __future__ import annotations

import html
import os
import re
import sys
from typing import Any

from lpps_v2 import (
    country_name_from_code,
    event_is_cancelled,
    map_age_policy_bitset,
    map_alcohol_bitset,
    map_sleeping_bitset,
    map_smoking_bitset,
    map_tickets,
    parse_lpps_v2_datetime,
    sanitize_publisher_unique_id,
)

ISO4217_RE = re.compile(r"^[A-Z]{3}$")

SHOWERS_ALLOWED = {None, 0, 1, 2}
SLEEPING_ALLOWED = {None, 0, 1, 2, 3, 4, 5, 6}
ALCOHOL_ALLOWED = {None, 0, 1, 2, 3}
SMOKING_ALLOWED = {None, 0, 1}

LOG_EVENT_TYPE_LPPS = "LPPS_CRAWL"

_skip_db_logs = False


def configure_lpps_logging(*, skip_db_logs: bool = False) -> None:
    global _skip_db_logs
    _skip_db_logs = skip_db_logs


def emit_lpps_log(
    cursor,
    conn,
    priority: str,
    organizer_id: int,
    content: str,
) -> None:
    content = (content or "").strip()[:2048]
    if not content:
        return
    if _skip_db_logs:
        stream = sys.stderr if priority == "ERROR" else sys.stdout
        print(f"[{priority}] {content}", file=stream)
        return
    cursor.execute(
        """INSERT INTO logs (priority, content, eventType, relatedOrganizer, timestamp)
           VALUES (%s, %s, %s, %s, NOW())""",
        (priority, content, LOG_EVENT_TYPE_LPPS, int(organizer_id)),
    )
    conn.commit()


def normalize_name_key(value: str | None) -> str:
    if not value:
        return ""
    text = str(value).lower().strip()
    return re.sub(r"[^a-z0-9]+", "", text)


def sanitize_plain_text(value: Any, max_len: int) -> str | None:
    if value is None:
        return None
    text = str(value).strip()
    if not text:
        return None
    text = re.sub(r"[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]", "", text)
    text = re.sub(r"<[^>]*>", "", text)
    text = html.unescape(text)
    text = re.sub(r"\s+", " ", text).strip()
    if not text:
        return None
    if len(text) > max_len:
        text = text[:max_len]
    return text


def sanitize_http_url(value: Any, max_len: int = 1024) -> str | None:
    if value is None:
        return None
    text = str(value).strip()
    if not text or len(text) > max_len:
        return None
    lower = text.lower()
    if not (lower.startswith("http://") or lower.startswith("https://")):
        return None
    if any(ch in text for ch in ("\r", "\n", "\0")):
        return None
    return text


def sanitize_bounded_int(value: Any, min_v: int, max_v: int) -> int | None:
    if value is None or value == "":
        return None
    try:
        num = int(value)
    except (TypeError, ValueError):
        return None
    if num < min_v or num > max_v:
        return None
    return num


def sanitize_latitude(value: Any) -> float | None:
    if value is None or value == "":
        return None
    try:
        num = float(value)
    except (TypeError, ValueError):
        return None
    if num < -90 or num > 90:
        return None
    return num


def sanitize_longitude(value: Any) -> float | None:
    if value is None or value == "":
        return None
    try:
        num = float(value)
    except (TypeError, ValueError):
        return None
    if num < -180 or num > 180:
        return None
    return num


def map_showers(value: Any) -> int | None:
    if value is None:
        return None
    if isinstance(value, bool):
        return 1 if value else 0
    num = sanitize_bounded_int(value, 0, 2)
    return num if num in SHOWERS_ALLOWED else None


def assert_lpps_v2_typed(obj: dict[str, Any], expected_type: str) -> str | None:
    if obj.get("apiVersion") != 2:
        return f"expected apiVersion 2, got {obj.get('apiVersion')!r}"
    api_type = obj.get("apiType")
    if api_type is None:
        return None
    if str(api_type) != expected_type:
        return f"expected apiType {expected_type!r}, got {api_type!r}"
    return None


def event_feed_label(event: Any) -> str:
    if not isinstance(event, dict):
        return "(invalid event entry)"
    parts: list[str] = []
    pub_id = event.get("publisherUniqueId")
    if pub_id is not None:
        parts.append(f"publisherUniqueId={pub_id!r}")
    name = event.get("name")
    if name is not None:
        label = sanitize_plain_text(name, 48) or str(name)[:48]
        parts.append(f"name={label!r}")
    return " ".join(parts) if parts else "(unidentified event)"


def venue_feed_label(venue: Any) -> str:
    if not isinstance(venue, dict):
        return "(invalid venue entry)"
    parts: list[str] = []
    pub_id = venue.get("publisherUniqueId")
    if pub_id is not None:
        parts.append(f"publisherUniqueId={pub_id!r}")
    name = venue.get("name")
    if name is not None:
        label = sanitize_plain_text(name, 48) or str(name)[:48]
        parts.append(f"name={label!r}")
    return " ".join(parts) if parts else "(unidentified venue)"


def write_lpps_skip_log(
    cursor,
    conn,
    organizer_id: int,
    reason: str,
    *,
    event_label: str | None = None,
) -> None:
    reason = re.sub(r"[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]", "", (reason or "").strip())
    if not reason:
        reason = "unknown reason"
    parts = [f"LPPS import skip organizer id={organizer_id}"]
    if event_label:
        parts.append(event_label)
    parts.append(f"reason: {reason}")
    emit_lpps_log(cursor, conn, "WARN", organizer_id, " ".join(parts)[:2048])


def record_event_skip(
    cursor,
    conn,
    organizer_id: int,
    stats: dict[str, int],
    reason: str,
    event: Any = None,
) -> None:
    stats["events_skipped"] = int(stats.get("events_skipped", 0)) + 1
    write_lpps_skip_log(
        cursor,
        conn,
        organizer_id,
        reason,
        event_label=event_feed_label(event) if event is not None else None,
    )


def resolve_import_user_id(cursor) -> int:
    env = os.environ.get("LANLIST_LPPS_IMPORT_USER_ID", "").strip()
    if env.isdigit():
        return int(env)
    cursor.execute("SELECT id FROM users ORDER BY id ASC LIMIT 1")
    row = cursor.fetchone()
    if not row:
        raise RuntimeError("No users in database; set LANLIST_LPPS_IMPORT_USER_ID")
    return int(row[0])


def fetch_venue_mapping(cursor, organizer_id: int, publisher_venue_id: str) -> int | None:
    cursor.execute(
        """SELECT venue_id FROM organizer_lpps_venues
           WHERE organizer_id = %s AND lppsVenueSiteUniqueId = %s
           LIMIT 1""",
        (organizer_id, publisher_venue_id),
    )
    row = cursor.fetchone()
    return int(row[0]) if row else None


def store_venue_mapping(
    cursor, conn, organizer_id: int, publisher_venue_id: str, venue_id: int
) -> None:
    cursor.execute(
        """INSERT INTO organizer_lpps_venues (organizer_id, lppsVenueSiteUniqueId, venue_id)
           VALUES (%s, %s, %s)
           ON DUPLICATE KEY UPDATE venue_id = VALUES(venue_id)""",
        (organizer_id, publisher_venue_id, venue_id),
    )
    conn.commit()


def find_venue_by_coords_title(cursor, title: str, lat: float, lng: float) -> int | None:
    cursor.execute(
        """SELECT id FROM venues
           WHERE title = %s
             AND lat IS NOT NULL AND lng IS NOT NULL
             AND ABS(lat - %s) < 0.0001
             AND ABS(lng - %s) < 0.0001
           LIMIT 1""",
        (title, lat, lng),
    )
    row = cursor.fetchone()
    return int(row[0]) if row else None


def resolve_or_create_venue(
    cursor,
    conn,
    organizer_id: int,
    venue: dict[str, Any],
    stats: dict[str, int],
) -> tuple[int | None, str | None]:
    err = assert_lpps_v2_typed(venue, "Venue")
    if err:
        return None, err

    publisher_venue_id = sanitize_publisher_unique_id(venue.get("publisherUniqueId"))
    if publisher_venue_id is None:
        return None, "venue missing valid publisherUniqueId"

    cached = fetch_venue_mapping(cursor, organizer_id, publisher_venue_id)
    if cached is not None:
        return cached, None

    title = sanitize_plain_text(venue.get("name"), 48)
    if not title:
        return None, "venue missing name"

    lat = sanitize_latitude(venue.get("gpsLatitude"))
    lng = sanitize_longitude(venue.get("gpsLongitude"))
    if lat is None or lng is None:
        return None, f"venue {publisher_venue_id!r} missing gpsLatitude/gpsLongitude"

    country = country_name_from_code(venue.get("countryCode"))

    existing = find_venue_by_coords_title(cursor, title, lat, lng)
    if existing is not None:
        store_venue_mapping(cursor, conn, organizer_id, publisher_venue_id, existing)
        return existing, None

    cursor.execute(
        "INSERT INTO venues (title, lat, lng, country) VALUES (%s, %s, %s, %s)",
        (title, lat, lng, country),
    )
    conn.commit()
    venue_id = int(cursor.lastrowid)
    store_venue_mapping(cursor, conn, organizer_id, publisher_venue_id, venue_id)
    stats["venues_created"] += 1
    return venue_id, None


def resolve_event_published(organizer_published: int, event: dict[str, Any]) -> int:
    if event_is_cancelled(event):
        return 0
    return 1 if int(organizer_published) else 0


def parse_event_record(
    event: dict[str, Any],
    venue_id: int,
    organizer_id: int,
    organizer_published: int,
) -> tuple[dict[str, Any] | None, str | None]:
    err = assert_lpps_v2_typed(event, "Event")
    if err:
        return None, err

    publisher_id = sanitize_publisher_unique_id(event.get("publisherUniqueId"))
    if publisher_id is None:
        return None, "event missing valid publisherUniqueId"

    title = sanitize_plain_text(event.get("name"), 128)
    if not title:
        return None, f"event {publisher_id!r} missing name"

    date_start = parse_lpps_v2_datetime(event.get("startDate"))
    date_end = parse_lpps_v2_datetime(event.get("endDate"))
    if date_start is None or date_end is None:
        return None, f"event {publisher_id!r} missing startDate/endDate"
    if date_end < date_start:
        return None, f"event {publisher_id!r} endDate before startDate"

    price_in_adv, price_on_door, currency, tickets_until = map_tickets(event.get("tickets"))

    row = {
        "lppsSiteUniqueId": publisher_id,
        "title": title,
        "organizer": organizer_id,
        "venue": venue_id,
        "dateStart": date_start.strftime("%Y-%m-%d %H:%M:%S"),
        "dateFinish": date_end.strftime("%Y-%m-%d %H:%M:%S"),
        "website": sanitize_http_url(event.get("url"), 4096),
        "blurb": sanitize_plain_text(event.get("description"), 16000),
        "showers": map_showers(event.get("hasShowers")),
        "sleeping": map_sleeping_bitset(event.get("sleeping")),
        "alcohol": map_alcohol_bitset(event.get("alcoholPolicy")),
        "smoking": map_smoking_bitset(event.get("smokingPolicy")),
        "ageRestrictions": map_age_policy_bitset(event.get("agePolicy")),
        "numberOfSeats": sanitize_bounded_int(event.get("maximumAttendeeCapacity"), 0, 100000),
        "networkMbps": sanitize_bounded_int(event.get("networkConnectionMbps"), 0, 1_000_000),
        "internetMbps": sanitize_bounded_int(event.get("internetConnectionMbps"), 0, 1_000_000),
        "priceOnDoor": price_on_door,
        "priceInAdv": price_in_adv,
        "currency": currency,
        "ticketsNotReleasedUntil": tickets_until,
        "published": resolve_event_published(organizer_published, event),
    }
    sleeping = row["sleeping"]
    if sleeping is not None and sleeping not in SLEEPING_ALLOWED:
        row["sleeping"] = None
    alcohol = row["alcohol"]
    if alcohol is not None and alcohol not in ALCOHOL_ALLOWED:
        row["alcohol"] = None
    smoking = row["smoking"]
    if smoking is not None and smoking not in SMOKING_ALLOWED:
        row["smoking"] = None
    return row, None


def fetch_existing_lpps_event(cursor, organizer_id: int, publisher_id: str) -> int | None:
    cursor.execute(
        """SELECT id FROM events
           WHERE organizer = %s AND lppsSiteUniqueId = %s
           LIMIT 1""",
        (organizer_id, publisher_id),
    )
    row = cursor.fetchone()
    return int(row[0]) if row else None


def insert_event(cursor, conn, row: dict[str, Any], created_by: int) -> None:
    cursor.execute(
        """INSERT INTO events (
               title, organizer, venue, dateStart, dateFinish, published, website, blurb,
               showers, sleeping, alcohol, smoking, ageRestrictions, numberOfSeats,
               networkMbps, internetMbps, priceOnDoor, priceInAdv, currency,
               ticketsNotReleasedUntil, lppsSiteUniqueId, createdDate, createdBy
           ) VALUES (
               %(title)s, %(organizer)s, %(venue)s, %(dateStart)s, %(dateFinish)s, %(published)s,
               %(website)s, %(blurb)s, %(showers)s, %(sleeping)s, %(alcohol)s, %(smoking)s,
               %(ageRestrictions)s, %(numberOfSeats)s, %(networkMbps)s, %(internetMbps)s,
               %(priceOnDoor)s, %(priceInAdv)s, %(currency)s, %(ticketsNotReleasedUntil)s,
               %(lppsSiteUniqueId)s, NOW(), %(createdBy)s
           )""",
        {**row, "createdBy": created_by},
    )
    conn.commit()


def update_event(cursor, conn, event_id: int, row: dict[str, Any]) -> None:
    cursor.execute(
        """UPDATE events SET
               title = %(title)s,
               venue = %(venue)s,
               dateStart = %(dateStart)s,
               dateFinish = %(dateFinish)s,
               published = %(published)s,
               website = %(website)s,
               blurb = %(blurb)s,
               showers = %(showers)s,
               sleeping = %(sleeping)s,
               alcohol = %(alcohol)s,
               smoking = %(smoking)s,
               ageRestrictions = %(ageRestrictions)s,
               numberOfSeats = %(numberOfSeats)s,
               networkMbps = %(networkMbps)s,
               internetMbps = %(internetMbps)s,
               priceOnDoor = %(priceOnDoor)s,
               priceInAdv = %(priceInAdv)s,
               currency = %(currency)s,
               ticketsNotReleasedUntil = %(ticketsNotReleasedUntil)s
           WHERE id = %(event_id)s AND organizer = %(organizer)s
           LIMIT 1""",
        {**row, "event_id": event_id},
    )
    conn.commit()


def sync_organisation_profile(
    cursor, conn, organizer_id: int, organisation: dict[str, Any]
) -> None:
    """Apply v2 organisation fields to the lanlist organizer row."""
    website = sanitize_http_url(organisation.get("websiteUrl"), 256)
    steam = sanitize_http_url(organisation.get("steamGroupUrl"), 256)
    discord = sanitize_http_url(organisation.get("discordInviteUrl"), 256)
    blurb = sanitize_plain_text(organisation.get("description"), 1024)

    cursor.execute(
        """UPDATE organizers SET
               websiteUrl = %s,
               steamGroupUrl = %s,
               discordInviteUrl = %s,
               blurb = %s
           WHERE id = %s
           LIMIT 1""",
        (website, steam, discord, blurb, organizer_id),
    )
    conn.commit()


def import_organisation_feed(
    cursor,
    conn,
    *,
    organizer_id: int,
    organizer_title: str,
    organizer_published: int,
    organisation: dict[str, Any],
    created_by: int,
) -> tuple[dict[str, int], str | None]:
    stats = {
        "events_inserted": 0,
        "events_updated": 0,
        "events_skipped": 0,
        "venues_created": 0,
    }

    err = assert_lpps_v2_typed(organisation, "Organisation")
    if err:
        return stats, err

    sync_organisation_profile(cursor, conn, organizer_id, organisation)

    org_name = sanitize_plain_text(organisation.get("name"), 128)
    name_warn = ""
    if org_name and normalize_name_key(org_name) != normalize_name_key(organizer_title):
        name_warn = (
            f"; feed organisation name {org_name!r} differs from lanlist {organizer_title!r}"
        )

    venues = organisation.get("venues")
    if venues is None:
        return stats, None
    if not isinstance(venues, list):
        return stats, "organisation.venues must be an array"

    for venue in venues:
        if not isinstance(venue, dict):
            record_event_skip(
                cursor, conn, organizer_id, stats, "venue entry is not an object"
            )
            continue

        venue_id, venue_err = resolve_or_create_venue(
            cursor, conn, organizer_id, venue, stats
        )
        if venue_id is None:
            venue_reason = venue_err or "venue could not be resolved"
            ev_list = venue.get("events")
            if isinstance(ev_list, list):
                for event in ev_list:
                    record_event_skip(
                        cursor,
                        conn,
                        organizer_id,
                        stats,
                        f"venue skipped: {venue_reason}",
                        event,
                    )
            else:
                write_lpps_skip_log(
                    cursor,
                    conn,
                    organizer_id,
                    f"venue skipped: {venue_reason}",
                    event_label=venue_feed_label(venue),
                )
            continue

        events = venue.get("events")
        if events is None:
            continue
        if not isinstance(events, list):
            write_lpps_skip_log(
                cursor,
                conn,
                organizer_id,
                "venue.events must be an array",
                event_label=venue_feed_label(venue),
            )
            continue

        for event in events:
            if not isinstance(event, dict):
                record_event_skip(
                    cursor, conn, organizer_id, stats, "event entry is not an object"
                )
                continue

            row, parse_err = parse_event_record(
                event, venue_id, organizer_id, organizer_published
            )
            if row is None:
                record_event_skip(
                    cursor,
                    conn,
                    organizer_id,
                    stats,
                    parse_err or "event validation failed",
                    event,
                )
                continue

            publisher_id = row["lppsSiteUniqueId"]
            existing_id = fetch_existing_lpps_event(cursor, organizer_id, publisher_id)
            try:
                if existing_id is None:
                    insert_event(cursor, conn, row, created_by)
                    stats["events_inserted"] += 1
                else:
                    update_event(cursor, conn, existing_id, row)
                    stats["events_updated"] += 1
            except Exception as ex:
                record_event_skip(
                    cursor,
                    conn,
                    organizer_id,
                    stats,
                    f"database error: {ex}",
                    event,
                )

    stats["summary_suffix"] = (
        f"imported inserted={stats['events_inserted']} updated={stats['events_updated']} "
        f"skipped={stats['events_skipped']} venues_created={stats['venues_created']}"
        f"{name_warn}"
    )
    return stats, None
