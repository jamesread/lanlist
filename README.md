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

?>
```

The database is provided in `schema.sql`.
