# lanlist.org

A free and open list of LAN parties. It's provided purely for the benefit of the LAN party community.

## Development

This code was first created in approx 2013, the site grew to a few hundred LANs and the VM died! IT was resurrected again several years ago, and the code and functionality has been updated substancially! 

`composer update` should be all you need to get things running, and a `includes/config.php` file.

```php
<?php

define('DB_DSN', 'mysql:host=localhost;dbname=lanlist');
define('DB_USER', 'lanlist');
define('DB_PASS', 'sekrit');

// Optional: OliveTin async favicon jobs (moderation UI → misc.php → OliveTin → scripts/run-favicon-job.py).
// Match binding id to your OliveTin config (see docs/olivetin-favicon-ops.md).
define('OLIVETIN_BASE_URL', 'https://olivetin.example:1337');   // Base URL only; trailing slash stripped
define('OLIVETIN_API_KEY', '');                                 // Bearer token / JWT forwarded in Authorization header
define('OLIVETIN_BINDING_ORGANIZER_FAVICON_FETCH', 'lanlist-org-favicon');
// Optional override default API path prefix (/api):
// define('OLIVETIN_API_PREFIX', '/api');

// Admin newsletter: schedule scripts/run-newsletter.php in OliveTin (replaces cron → public/scheduler.php).
// See docs/olivetin-newsletter-ops.md.

// Test/sandbox organizers excluded from unpublished-organizer moderation (comma-separated IDs).
define('MODERATION_EXCLUDED_ORGANIZER_IDS', '319');

?>
```

The database is provided in `schema.sql`. Existing deployments should apply incremental SQL in order: legacy unnumbered scripts in [`scripts/`](scripts/) (e.g. [`scripts/add_async_jobs.sql`](scripts/add_async_jobs.sql)), then numbered migrations from **`5_*.sql`** upward (e.g. [`scripts/5_add_event_tickets_not_released_until.sql`](scripts/5_add_event_tickets_not_released_until.sql)). New schema changes should use the next sequential number. See [`docs/favicon-fetch-job-queue-plan.md`](docs/favicon-fetch-job-queue-plan.md) and [`docs/olivetin-favicon-ops.md`](docs/olivetin-favicon-ops.md) for OliveTin async jobs.

