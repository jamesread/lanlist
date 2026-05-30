# OliveTin: organizer LPPS crawl job

Schedule **`scripts/run-lpps-crawl.py`** daily (or as needed) in OliveTin.

## What it does

For each organizer with a non-empty **`lppsUrl`** and **`lppsAdminDisabled = 0`**, the job:

1. Fetches the LPPS **v2** JSON feed ([Lan Party Publishing Standard](https://github.com/jamesread/lan-party-publishing-standard); v1 is rejected)
2. Validates against `lan-party-publishing-standard-v2.schema` (bundled under `scripts/`)
3. **Imports** venues and events into lanlist (matched by LPPS `siteUniqueId`)
4. Updates **`lppsLastCrawl`**, **`lppsCrawlSuccess`**, **`lppsCrawlResult`**
5. Writes one row to **`logs`** (`eventType` **`LPPS_CRAWL`**, `relatedOrganizer` set)

Organizers **without** an LPPS URL or with **admin crawl disabled** are not selected and produce **no** log lines (silent skip).

### Import rules

- Feeds must have **`organisation.apiVersion`** = **2** (version 1 is rejected).
- Events require **`publisherUniqueId`**, **`name`**, **`startDate`**, and **`endDate`**; invalid rows are skipped (each skip logged as **`LPPS_CRAWL`** / WARN with the reason).
- Venues require **`publisherUniqueId`**, **`name`**, **`gpsLatitude`**, **`gpsLongitude`**, and optional **`countryCode`** (ISO 3166-1 alpha-2); mapped via **`organizer_lpps_venues`**.
- Organisation **`websiteUrl`**, **`steamGroupUrl`**, **`discordInviteUrl`**, and **`description`** are synced to the lanlist organizer row.
- **`eventStatus`** `EventCancelled` imports with **`published`** = 0. Policy fields use v2 bitsets (`sleeping`, `alcoholPolicy`, etc.). Tickets come from **`event.tickets[]`**.
- Text fields are stripped to plain text; only **http/https** URLs are stored.
- New events are **`published`** only when the organizer is already published; updates do not change **`published`**.
- Events missing from the feed are left unchanged (no auto-delete).
- Optional **`LANLIST_LPPS_IMPORT_USER_ID`** in config sets **`events.createdBy`** (defaults to the lowest user id).

## Example OliveTin action (illustrative)

```yaml
# Example only — align with your OliveTin version.
actions:
  - title: "Lanlist: LPPS crawl"
    id: lanlist-lpps-crawl
    # exec: |
    #   cd /path/to/lanlist/scripts && ./run-lpps-crawl.py
```

Suggested schedule: once per day (e.g. after favicon jobs).

Optional arguments:

- **`--job-id N`** — resume a pre-created `async_jobs` row
- **`--org-id N`** — crawl one organizer only (still requires eligible LPPS fields)
- **`--skip-db-logs`** — print crawl/skip log lines to the console instead of the `logs` table (useful for dry runs)

## Runtime

- Python 3 with **`requests`**, **`mysql-connector-python`**, and **`jsonschema`** (same stack as other scripts).
- **`MYSQL_*`** for the runner (or `/etc/lanlist/config.env`).
- Migrations **`scripts/11_add_organizer_lpps.sql`**, **`12_add_event_lpps_site_unique_id.sql`**, **`13_add_organizer_lpps_venue_map.sql`** applied.

## Admin UI

**System → Jobs** lists batch runs as **`organizer_lpps_crawl`** with metadata: `crawled`, `ok`, `failed`.

Per-organizer crawl status is on the organizer moderation panel and in **Logs** (`LPPS_CRAWL`).

## Manual run

```bash
cd /path/to/lanlist/scripts
./run-lpps-crawl.py
./lpps-crawl.py --org-id 42
```
