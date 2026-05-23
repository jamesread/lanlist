<?php

/**
 * @deprecated Use OliveTin to run scripts/run-newsletter.php on a schedule instead of cron → this entrypoint.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    exit;
}

fwrite(
    STDERR,
    "DEPRECATED: public/scheduler.php — remove this from cron and configure OliveTin to run scripts/run-newsletter.php.\n"
);

$repoRoot = dirname(__DIR__);
$runner = $repoRoot . '/scripts/run-newsletter.php';
if (!is_file($runner)) {
    fwrite(STDERR, "ERROR: missing {$runner}\n");
    exit(1);
}

passthru(PHP_BINARY . ' ' . escapeshellarg($runner), $exitCode);
exit((int) $exitCode);
