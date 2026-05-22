#!/usr/bin/env python3
"""
One-shot favicon async job runner for OliveTin (see docs/favicon-fetch-job-queue-plan.md).

Requires MYSQL_USER / MYSQL_PASS; optional MYSQL_HOST (default localhost), MYSQL_DATABASE (default lanlist).
Also requires ImageMagick (`magick`) on PATH, same as scripts/favicon-build.sh.

Example (from repo root, after cd scripts):
  MYSQL_USER=... MYSQL_PASS=... ./run-favicon-job.py --job-id 12 --organizer-id 34
"""

from __future__ import annotations

import argparse
import os
import subprocess
import sys
from pathlib import Path

import mysql.connector

JOB_TYPE_ORGANIZER_FAVICON_FETCH = "organizer_favicon_fetch"

SCRIPT_DIR = Path(__file__).resolve().parent
REPO_ROOT = SCRIPT_DIR.parent
PNG_DIR = REPO_ROOT / "public" / "resources" / "images" / "organizer-favicons"


def _fail(cursor, conn, *, job_pk: int, organizer_id: int, message: str) -> None:
    msg = (message or "unknown error").strip()[:62000]
    cursor.execute(
        """
        UPDATE async_jobs
        SET status = 'failed',
            finished_at = NOW(),
            error_message = %s
        WHERE id = %s
          AND job_type = %s
          AND organizer_id = %s
        LIMIT 1
        """,
        (msg, job_pk, JOB_TYPE_ORGANIZER_FAVICON_FETCH, organizer_id),
    )
    conn.commit()


def _complete(cursor, conn, *, job_pk: int, organizer_id: int) -> None:
    cursor.execute(
        """
        UPDATE async_jobs
        SET status = 'completed',
            finished_at = NOW(),
            error_message = NULL
        WHERE id = %s
          AND job_type = %s
          AND organizer_id = %s
        LIMIT 1
        """,
        (job_pk, JOB_TYPE_ORGANIZER_FAVICON_FETCH, organizer_id),
    )
    conn.commit()


def main() -> int:
    parser = argparse.ArgumentParser(description="Run a single organizer favicon async job.")
    parser.add_argument("--job-id", type=int, required=True)
    parser.add_argument("--organizer-id", type=int, required=True)
    args = parser.parse_args()

    job_pk = int(args.job_id)
    organizer_id = int(args.organizer_id)

    mydb = mysql.connector.connect(
        host=os.environ.get("MYSQL_HOST", "localhost"),
        user=os.environ["MYSQL_USER"],
        password=os.environ["MYSQL_PASS"],
        database=os.environ.get("MYSQL_DATABASE", "lanlist"),
    )
    cur = mydb.cursor(dictionary=True)
    try:
        cur.execute(
            """
            SELECT id, job_type, organizer_id, status
            FROM async_jobs
            WHERE id = %s
            LIMIT 1
            """,
            (job_pk,),
        )
        row = cur.fetchone()
        if row is None:
            print(f"ERROR: async_jobs row not found id={job_pk}", file=sys.stderr)
            return 1

        if row["job_type"] != JOB_TYPE_ORGANIZER_FAVICON_FETCH or int(row["organizer_id"] or 0) != organizer_id:
            _fail(cur, mydb, job_pk=job_pk, organizer_id=organizer_id, message="job row mismatch (type or organizer)")
            print("ERROR: job row does not match expected type / organizer — marked failed.", file=sys.stderr)
            return 2

        r = subprocess.run(
            [sys.executable, str(SCRIPT_DIR / "favicon-get.py"), "--org-id", str(organizer_id)],
            cwd=str(SCRIPT_DIR),
            env=os.environ.copy(),
            check=False,
        )
        if r.returncode != 0:
            _fail(
                cur,
                mydb,
                job_pk=job_pk,
                organizer_id=organizer_id,
                message=f"favicon-get.py exited with code {r.returncode}",
            )
            return r.returncode or 3

        ico_path = SCRIPT_DIR / f"{organizer_id}.ico"
        if not ico_path.is_file():
            _fail(cur, mydb, job_pk=job_pk, organizer_id=organizer_id, message="ICO missing after favicon-get.py")
            return 4

        PNG_DIR.mkdir(parents=True, exist_ok=True)
        png_out = PNG_DIR / f"{organizer_id}.png"
        mc = subprocess.run(
            ["magick", "convert", f"{ico_path}[0]", "-resize", "16x16", str(png_out)],
            cwd=str(SCRIPT_DIR),
            check=False,
            capture_output=True,
            text=True,
        )
        if mc.returncode != 0:
            detail = (mc.stderr or mc.stdout or "").strip()[:2000]
            _fail(cur, mydb, job_pk=job_pk, organizer_id=organizer_id, message=f"magick convert failed: {detail}")
            return 5

        _complete(cur, mydb, job_pk=job_pk, organizer_id=organizer_id)
        print(f"OK job_id={job_pk} organizer={organizer_id} png={png_out}")
        return 0
    finally:
        cur.close()
        mydb.close()


if __name__ == "__main__":
    sys.exit(main())
