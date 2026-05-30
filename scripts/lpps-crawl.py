#!/usr/bin/env python3
"""
Crawl Lan Party Publishing Standard (LPPS) v2 JSON feeds for eligible organizers.

Eligible: lppsUrl set (non-empty) and lppsAdminDisabled = 0.
Organizers without a feed URL or with admin crawl disabled are omitted from the query
and produce no log rows.

Updates organizers.lppsLastCrawl / lppsCrawlSuccess / lppsCrawlResult and writes
one logs row per crawl attempt (eventType LPPS_CRAWL).

Requires MYSQL_USER / MYSQL_PASS; optional MYSQL_HOST (default localhost),
MYSQL_DATABASE (default lanlist). Loads /etc/lanlist/config.env when present.

Example:
  ./lpps-crawl.py
  ./lpps-crawl.py --org-id 42
  ./lpps-crawl.py --skip-db-logs
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path
from typing import Any
from urllib.parse import urlparse

import mysql.connector
import requests

LOG_EVENT_TYPE_LPPS = "LPPS_CRAWL"
LANLIST_CONFIG_ENV = Path("/etc/lanlist/config.env")
MAX_RESPONSE_BYTES = 2 * 1024 * 1024
REQUEST_TIMEOUT_SEC = 30
USER_AGENT = "Lanlist-LPPS-Crawl/2.0 (+https://lanlist.info)"


def _parse_config_env_line(line: str) -> tuple[str, str] | None:
    line = line.strip()
    if not line or line.startswith("#"):
        return None
    if line.startswith("export "):
        line = line[7:].strip()
    if "=" not in line:
        return None
    key, _, raw_value = line.partition("=")
    key = key.strip()
    if not re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*", key):
        return None
    value = raw_value.strip()
    if len(value) >= 2 and value[0] == value[-1] and value[0] in ("'", '"'):
        value = value[1:-1]
    return key, value


def load_lanlist_config_env() -> None:
    if not LANLIST_CONFIG_ENV.is_file():
        return
    for line in LANLIST_CONFIG_ENV.read_text(encoding="utf-8").splitlines():
        parsed = _parse_config_env_line(line)
        if parsed is None:
            continue
        key, value = parsed
        if key.startswith("MYSQL_") or key.startswith("LANLIST_"):
            os.environ[key] = value


def mysql_connect():
    missing = [name for name in ("MYSQL_USER", "MYSQL_PASS") if not os.environ.get(name)]
    if missing:
        hint = f"Set {', '.join(missing)} or provide {LANLIST_CONFIG_ENV}."
        raise RuntimeError(hint)
    return mysql.connector.connect(
        host=os.environ.get("MYSQL_HOST", "localhost"),
        user=os.environ["MYSQL_USER"],
        password=os.environ["MYSQL_PASS"],
        database=os.environ.get("MYSQL_DATABASE", "lanlist"),
    )


def truncate_result(text: str, limit: int = 1024) -> str:
    text = (text or "").strip()
    if len(text) <= limit:
        return text
    return text[: limit - 3] + "..."


def write_lpps_log(cursor, conn, priority: str, organizer_id: int, lpps_url: str | None, summary: str) -> None:
    from lpps_import import emit_lpps_log

    url_part = ""
    if lpps_url is not None and str(lpps_url).strip():
        u = str(lpps_url).strip()
        if len(u) > 400:
            u = u[:397] + "..."
        url_part = f" feed={u!s}"
    content = (f"LPPS crawl organizer id={organizer_id}{url_part}: {summary}").strip()
    emit_lpps_log(cursor, conn, priority, organizer_id, content)


def record_crawl_result(cursor, conn, organizer_id: int, success: bool, result: str) -> None:
    cursor.execute(
        """UPDATE organizers
           SET lppsLastCrawl = NOW(),
               lppsCrawlSuccess = %s,
               lppsCrawlResult = %s
           WHERE id = %s
           LIMIT 1""",
        (1 if success else 0, truncate_result(result), int(organizer_id)),
    )
    conn.commit()


def validate_lpps_payload(data: Any) -> tuple[bool, str]:
    from lpps_v2 import count_venues_events, validate_lpps_v2_document

    ok, detail = validate_lpps_v2_document(data)
    if not ok:
        return False, detail

    organisation = data["organisation"]
    try:
        venue_count, event_count = count_venues_events(organisation)
    except ValueError as ex:
        return False, str(ex)
    org_name = organisation.get("name") or "(unnamed)"
    return True, f"valid LPPS v2 {org_name!r}: {venue_count} venue(s), {event_count} event(s)"


def fetch_lpps_json(url: str) -> tuple[bool, Any, str]:
    parsed = urlparse(url)
    if parsed.scheme not in ("http", "https"):
        return False, None, f"unsupported URL scheme: {parsed.scheme or '(none)'}"

    try:
        res = requests.get(
            url,
            timeout=REQUEST_TIMEOUT_SEC,
            headers={"User-Agent": USER_AGENT, "Accept": "application/json"},
            allow_redirects=True,
            stream=True,
        )
    except requests.RequestException as ex:
        return False, None, f"request error: {ex}"

    if res.status_code != 200:
        return False, None, f"HTTP {res.status_code}"

    chunks: list[bytes] = []
    total = 0
    for chunk in res.iter_content(chunk_size=65536):
        if not chunk:
            continue
        total += len(chunk)
        if total > MAX_RESPONSE_BYTES:
            return False, None, f"response exceeds {MAX_RESPONSE_BYTES} bytes"
        chunks.append(chunk)

    raw = b"".join(chunks)
    content_type = (res.headers.get("Content-Type") or "").split(";")[0].strip().lower()
    if content_type and content_type not in ("application/json", "text/json", "application/ld+json", "text/plain"):
        return False, None, f"unexpected Content-Type {content_type!r}"

    try:
        text = raw.decode(res.encoding or "utf-8", errors="replace")
    except LookupError:
        text = raw.decode("utf-8", errors="replace")

    try:
        data = json.loads(text)
    except json.JSONDecodeError as ex:
        return False, None, f"invalid JSON: {ex}"

    return True, data, "fetched JSON"


def process_organizer(cursor, conn, row: tuple, created_by: int) -> str:
    org_id = int(row[0])
    title = row[1]
    lpps_url = str(row[2]).strip()
    org_published = int(row[3]) if len(row) > 3 else 0

    print(f"Crawling id={org_id} title={title!r} url={lpps_url!r}")

    try:
        ok_fetch, payload, fetch_detail = fetch_lpps_json(lpps_url)
        if not ok_fetch:
            record_crawl_result(cursor, conn, org_id, False, fetch_detail)
            write_lpps_log(cursor, conn, "WARN", org_id, lpps_url, f"failed: {fetch_detail}")
            return "failed"

        ok_valid, valid_detail = validate_lpps_payload(payload)
        if not ok_valid:
            record_crawl_result(cursor, conn, org_id, False, valid_detail)
            write_lpps_log(cursor, conn, "WARN", org_id, lpps_url, f"failed: {valid_detail}")
            return "failed"

        from lpps_import import import_organisation_feed

        organisation = payload["organisation"]
        import_stats, import_err = import_organisation_feed(
            cursor,
            conn,
            organizer_id=org_id,
            organizer_title=title,
            organizer_published=org_published,
            organisation=organisation,
            created_by=created_by,
        )
        if import_err:
            record_crawl_result(cursor, conn, org_id, False, import_err)
            write_lpps_log(cursor, conn, "WARN", org_id, lpps_url, f"failed: {import_err}")
            return "failed"

        import_suffix = import_stats.get("summary_suffix", "")
        summary = f"ok ({fetch_detail}; {valid_detail}; {import_suffix})"
        record_crawl_result(cursor, conn, org_id, True, summary)
        write_lpps_log(cursor, conn, "INFO", org_id, lpps_url, summary)
        return "ok"

    except Exception as ex:
        detail = f"exception: {ex}"
        record_crawl_result(cursor, conn, org_id, False, detail)
        write_lpps_log(cursor, conn, "ERROR", org_id, lpps_url, detail)
        print(f"\tExcept {ex}", file=sys.stderr)
        return "failed"


def eligible_organizers_sql(org_id: int | None) -> tuple[str, list]:
    sql = """
        SELECT o.id, o.title, o.lppsUrl, COALESCE(o.published, 0)
        FROM organizers o
        WHERE COALESCE(o.lppsAdminDisabled, 0) = 0
          AND o.lppsUrl IS NOT NULL
          AND TRIM(o.lppsUrl) <> ''
    """
    params: list = []
    if org_id is not None:
        sql += " AND o.id = %s"
        params.append(org_id)
    sql += " ORDER BY o.id"
    return sql, params


def main() -> int:
    parser = argparse.ArgumentParser(description="Crawl LPPS feeds for eligible organizers.")
    parser.add_argument("--org-id", type=int, help="Crawl a single organizer id only.")
    parser.add_argument(
        "--skip-db-logs",
        action="store_true",
        help="Print log lines to the console instead of writing to the logs table.",
    )
    args = parser.parse_args()

    load_lanlist_config_env()

    from lpps_import import configure_lpps_logging, resolve_import_user_id

    configure_lpps_logging(skip_db_logs=args.skip_db_logs)

    try:
        mydb = mysql_connect()
    except RuntimeError as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 1

    cur = mydb.cursor()
    try:
        created_by = resolve_import_user_id(cur)
        sql, params = eligible_organizers_sql(args.org_id)
        cur.execute(sql, params)
        rows = cur.fetchall()

        counts = {"crawled": 0, "ok": 0, "failed": 0}
        for row in rows:
            outcome = process_organizer(cur, mydb, row, created_by)
            counts["crawled"] += 1
            if outcome == "ok":
                counts["ok"] += 1
            else:
                counts["failed"] += 1

        summary = (
            f"SUMMARY crawled={counts['crawled']} ok={counts['ok']} failed={counts['failed']}"
        )
        print(summary)
        return 0
    finally:
        cur.close()
        mydb.close()


if __name__ == "__main__":
    sys.exit(main())
