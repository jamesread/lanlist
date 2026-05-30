#!/usr/bin/env python3
"""
Batch LPPS crawl runner for OliveTin (see docs/olivetin-lpps-ops.md).

Inserts or resumes an async_jobs row (job_type organizer_lpps_crawl), runs lpps-crawl.py,
then marks the job completed or failed.

Loads MYSQL_* from /etc/lanlist/config.env when present.

Example (from repo root):
  ./scripts/run-lpps-crawl.py
  ./scripts/run-lpps-crawl.py --job-id 42
  ./scripts/run-lpps-crawl.py --org-id 7
"""

from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
import sys
from pathlib import Path

import mysql.connector

JOB_TYPE_ORGANIZER_LPPS_CRAWL = "organizer_lpps_crawl"
LANLIST_CONFIG_ENV = Path("/etc/lanlist/config.env")
SCRIPT_DIR = Path(__file__).resolve().parent


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


def _load_lanlist_config_env() -> None:
    if not LANLIST_CONFIG_ENV.is_file():
        return
    for line in LANLIST_CONFIG_ENV.read_text(encoding="utf-8").splitlines():
        parsed = _parse_config_env_line(line)
        if parsed is None:
            continue
        key, value = parsed
        if key.startswith("MYSQL_") or key.startswith("LANLIST_"):
            os.environ[key] = value


def _mysql_connect():
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


def _parse_summary(stdout: str) -> dict[str, int] | None:
    for line in reversed(stdout.splitlines()):
        line = line.strip()
        if not line.startswith("SUMMARY "):
            continue
        parts = line[len("SUMMARY ") :].split()
        out: dict[str, int] = {}
        for part in parts:
            if "=" not in part:
                continue
            key, _, val = part.partition("=")
            try:
                out[key] = int(val)
            except ValueError:
                continue
        return out if out else None
    return None


def _insert_job(cursor, conn, metadata: dict) -> int:
    meta_json = json.dumps(metadata, separators=(",", ":"))
    cursor.execute(
        """INSERT INTO async_jobs (job_type, organizer_id, status, metadata, started_at)
           VALUES (%s, NULL, 'processing', %s, NOW())""",
        (JOB_TYPE_ORGANIZER_LPPS_CRAWL, meta_json),
    )
    conn.commit()
    return int(cursor.lastrowid)


def _begin_job(cursor, conn, job_pk: int, metadata: dict) -> None:
    meta_json = json.dumps(metadata, separators=(",", ":"))
    cursor.execute(
        """SELECT id, job_type FROM async_jobs WHERE id = %s LIMIT 1""",
        (job_pk,),
    )
    row = cursor.fetchone()
    if row is None:
        raise RuntimeError(f"async_jobs row not found id={job_pk}")
    if row[0] != job_pk or row[1] != JOB_TYPE_ORGANIZER_LPPS_CRAWL:
        raise RuntimeError(f"job #{job_pk} is not an LPPS crawl job")

    cursor.execute(
        """UPDATE async_jobs
           SET status = 'processing', started_at = NOW(), metadata = %s, error_message = NULL
           WHERE id = %s AND job_type = %s
           LIMIT 1""",
        (meta_json, job_pk, JOB_TYPE_ORGANIZER_LPPS_CRAWL),
    )
    conn.commit()


def _fail_job(cursor, conn, job_pk: int, message: str) -> None:
    msg = (message or "unknown error").strip()[:62000]
    cursor.execute(
        """UPDATE async_jobs
           SET status = 'failed', finished_at = NOW(), error_message = %s
           WHERE id = %s AND job_type = %s
           LIMIT 1""",
        (msg, job_pk, JOB_TYPE_ORGANIZER_LPPS_CRAWL),
    )
    conn.commit()


def _complete_job(cursor, conn, job_pk: int, metadata: dict) -> None:
    meta_json = json.dumps(metadata, separators=(",", ":"))
    cursor.execute(
        """UPDATE async_jobs
           SET status = 'completed', finished_at = NOW(), error_message = NULL, metadata = %s
           WHERE id = %s AND job_type = %s
           LIMIT 1""",
        (meta_json, job_pk, JOB_TYPE_ORGANIZER_LPPS_CRAWL),
    )
    conn.commit()


def main() -> int:
    parser = argparse.ArgumentParser(description="Run LPPS crawl batch job (OliveTin).")
    parser.add_argument("--job-id", type=int, help="Existing async_jobs row id.")
    parser.add_argument("--org-id", type=int, help="Pass through to lpps-crawl.py (single organizer).")
    parser.add_argument(
        "--skip-db-logs",
        action="store_true",
        help="Pass through to lpps-crawl.py (console logging only).",
    )
    args = parser.parse_args()

    _load_lanlist_config_env()

    try:
        mydb = _mysql_connect()
    except RuntimeError as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 1

    cur = mydb.cursor()
    metadata: dict = {"source": "scripts/run-lpps-crawl.py"}
    job_pk: int | None = None

    try:
        if args.job_id:
            job_pk = int(args.job_id)
            _begin_job(cur, mydb, job_pk, metadata)
        else:
            job_pk = _insert_job(cur, mydb, metadata)

        cmd = [sys.executable, str(SCRIPT_DIR / "lpps-crawl.py")]
        if args.org_id:
            cmd.extend(["--org-id", str(int(args.org_id))])
        if args.skip_db_logs:
            cmd.append("--skip-db-logs")

        proc = subprocess.run(
            cmd,
            cwd=str(SCRIPT_DIR),
            env=os.environ.copy(),
            check=False,
            capture_output=True,
            text=True,
        )
        if proc.stdout:
            print(proc.stdout, end="" if proc.stdout.endswith("\n") else "\n")
        if proc.stderr:
            print(proc.stderr, end="" if proc.stderr.endswith("\n") else "\n", file=sys.stderr)

        if proc.returncode != 0:
            _fail_job(
                cur,
                mydb,
                job_pk=job_pk,
                message=f"lpps-crawl.py exited with code {proc.returncode}",
            )
            return proc.returncode or 2

        summary = _parse_summary(proc.stdout or "")
        if summary is None:
            _fail_job(cur, mydb, job_pk=job_pk, message="lpps-crawl.py did not emit SUMMARY line")
            return 3

        metadata.update(summary)
        _complete_job(cur, mydb, job_pk, metadata)
        print(
            f"OK lpps-crawl job_id={job_pk} crawled={summary.get('crawled', 0)} "
            f"ok={summary.get('ok', 0)} failed={summary.get('failed', 0)}"
        )
        return 0
    except RuntimeError as exc:
        if job_pk is not None:
            _fail_job(cur, mydb, job_pk=job_pk, message=str(exc))
        print(f"ERROR: {exc}", file=sys.stderr)
        return 1
    finally:
        cur.close()
        mydb.close()


if __name__ == "__main__":
    sys.exit(main())
