#!/usr/bin/env php
<?php
/**
 * One-shot moderator newsletter runner for OliveTin (replaces cron → public/scheduler.php).
 * Emails a site-checks snapshot (same content as siteChecks.php) when issues exist.
 *
 * Optional: --job-id N when OliveTin passes a pre-created async_jobs row.
 * Otherwise this script inserts a job row for the jobs admin UI.
 *
 * Example (from repo root):
 *   php scripts/run-newsletter.php
 *   php scripts/run-newsletter.php --job-id 42
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Forbidden: CLI only.\n");
    exit(1);
}

$publicDir = dirname(__DIR__) . '/public';
if (!is_dir($publicDir)) {
    fwrite(STDERR, "ERROR: public/ not found at {$publicDir}\n");
    exit(1);
}

chdir($publicDir);

require_once 'includes/common.php';
require_once 'includes/functionality/async_jobs.php';
require_once 'includes/classes/ScheduledTaskNewsletter.php';

$jobIdArg = null;
$argv = $_SERVER['argv'] ?? [];
for ($i = 1, $n = count($argv); $i < $n; $i++) {
    $arg = $argv[$i];
    if (str_starts_with($arg, '--job-id=')) {
        $jobIdArg = (int) substr($arg, 9);
    } elseif ($arg === '--job-id' && isset($argv[$i + 1]) && is_numeric($argv[$i + 1])) {
        $jobIdArg = (int) $argv[++$i];
    }
}

/** @var \libAllure\Database $db */
global $db;

$jobId = null;
$metadata = ['source' => 'scripts/run-newsletter.php'];

try {
    if ($jobIdArg !== null && $jobIdArg > 0) {
        $jobId = lanlistBeginNewsletterAsyncJob($jobIdArg, $metadata);
    } else {
        $jobId = lanlistInsertNewsletterAsyncJob($metadata);
    }

    $lastRun = lanlistFetchNewsletterLastRunTime();
    if ($lastRun === null) {
        throw new RuntimeException('scheduler_tasks row for ScheduledTaskNewsletter is missing');
    }

    $task = new ScheduledTaskNewsletter();
    $task->lastExecuted = $lastRun;

    $summary = lanlistRunNewsletterTask($task);
    lanlistTouchNewsletterLastRunTime();

    lanlistCompleteNewsletterAsyncJob($jobId, $summary, $metadata);
    fwrite(STDOUT, 'OK newsletter job_id=' . $jobId . ' updates=' . $summary['updateCount'] . ' emailed=' . ($summary['emailSent'] ? 'yes' : 'no') . "\n");
    exit(0);
} catch (Throwable $e) {
    $msg = $e->getMessage();
    if ($jobId !== null) {
        lanlistFailNewsletterAsyncJob($jobId, $msg);
    }
    fwrite(STDERR, 'ERROR: ' . $msg . "\n");
    exit(1);
}
