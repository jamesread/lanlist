# lanlist.org

A list of LAN Parties.

## Development

This code was first created in approx 2013 or something like that. It's functional, just a bit of a sign of the times.

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

?>
```

The database is provided in `schema.sql`. Existing deployments should apply incremental SQL such as [`scripts/add_async_jobs.sql`](scripts/add_async_jobs.sql) when OliveTin async jobs ship (see [`docs/favicon-fetch-job-queue-plan.md`](docs/favicon-fetch-job-queue-plan.md) and [`docs/olivetin-favicon-ops.md`](docs/olivetin-favicon-ops.md)).

