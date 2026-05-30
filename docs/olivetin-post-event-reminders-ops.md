# OliveTin: post-event reminder job

Schedule **`scripts/run-post-event-reminders.php`** daily in OliveTin.

## What it does

Two days after a **published** event's finish date (with up to two extra days if a run was missed), the job emails users linked to that event's organizer when the organizer has **no other upcoming published events** (`dateFinish > NOW()`).

Each qualifying event is processed once (`events.postEventReminderSentAt`). If several events from the same organizer qualify on the same run, users receive **one email** referencing the most recently finished event; all qualifying event rows are marked sent.

Recipients must have a profile email and **Organizer update emails** set to **Always** (profile → email notifications). Each user receives at most **one** post-event reminder per rolling 30-day period (`users.lastPostEventReminderEmailDate`), even if multiple organizers they belong to qualify.

## Example OliveTin action (illustrative)

```yaml
# Example only — align with your OliveTin version.
actions:
  - title: "Lanlist: post-event reminders"
    id: lanlist-post-event-reminders
    # exec: |
    #   cd /path/to/lanlist && php scripts/run-post-event-reminders.php
```

Suggested schedule: once per day (e.g. morning).

Optional argument **`--job-id N`** if you pre-insert an `async_jobs` row and pass the id from OliveTin templating.

## Runtime

- PHP CLI with the same `includes/config.php` / DB credentials as the web app.
- Migration **`scripts/9_add_event_post_event_reminder_sent.sql`** must be applied (adds `events.postEventReminderSentAt`).
- Migration **`scripts/10_add_user_last_post_event_reminder_email_date.sql`** must be applied (adds per-user monthly cap tracking).

## Admin UI

**System → Jobs** (`listSchedulerTasks.php`) lists runs as `post_event_reminders` with metadata: events considered, organizers emailed, emails sent, skips.

## Database setup

```bash
mysql -u … lanlist < scripts/9_add_event_post_event_reminder_sent.sql
```
