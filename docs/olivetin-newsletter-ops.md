# OliveTin: admin newsletter job

The admin newsletter previously ran via **cron → `public/scheduler.php`**. That path is **deprecated**; schedule **`scripts/run-newsletter.php`** in OliveTin instead.

## Example OliveTin action (illustrative)

OliveTin’s YAML changes between releases—confirm field names in the [OliveTin documentation](https://docs.olivetin.app/).

```yaml
# Example only — align with your OliveTin version.
actions:
  - title: "Lanlist: admin newsletter"
    id: lanlist-newsletter
    # Place the shell command where your version expects it (exec, commands, etc.).
    # exec: |
    #   cd /path/to/lanlist && php scripts/run-newsletter.php
```

Suggested schedule: same cadence you used for cron (e.g. daily). The script:

1. Inserts an `async_jobs` row (`job_type = admin_newsletter`) for the **Jobs** admin page.
2. Builds the newsletter for activity since `scheduler_tasks.lastRunTime` for `ScheduledTaskNewsletter`.
3. Emails admins when there are updates (unchanged behaviour).
4. Advances the watermark in `scheduler_tasks` on success.

Optional argument **`--job-id N`** if you pre-insert a job row and pass the id from OliveTin templating.

## Runtime

- PHP CLI with the same `includes/config.php` / DB credentials as the web app (`composer install` in repo root).
- `scheduler_tasks` must still contain a row with `className = ScheduledTaskNewsletter` (legacy watermark only).

## Admin UI

**System → Jobs** (`listSchedulerTasks.php`) lists `async_jobs` (newsletter, favicon, LPPS crawl, and post-event reminder runs). Favicon jobs are still enqueued from moderation via OliveTin; see [`olivetin-favicon-ops.md`](olivetin-favicon-ops.md). LPPS crawl: [`olivetin-lpps-ops.md`](olivetin-lpps-ops.md). Post-event reminders: [`olivetin-post-event-reminders-ops.md`](olivetin-post-event-reminders-ops.md).
