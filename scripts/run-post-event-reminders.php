#!/usr/bin/env php
<?php
/**
 * Daily post-event reminder runner for OliveTin.
 * Emails organizer-linked users when a published event finished ~2 days ago and the
 * organizer has no other upcoming events listed.
 *
 * Optional: --job-id N when OliveTin passes a pre-created async_jobs row.
 *
 * Example (from repo root):
 *   php scripts/run-post-event-reminders.php
 *   php scripts/run-post-event-reminders.php --job-id 42
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
require_once 'includes/classes/ScheduledTaskPostEventReminders.php';

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
$metadata = ['source' => 'scripts/run-post-event-reminders.php'];

try {
    if ($jobIdArg !== null && $jobIdArg > 0) {
        $jobId = lanlistBeginPostEventRemindersAsyncJob($jobIdArg, $metadata);
    } else {
        $jobId = lanlistInsertPostEventRemindersAsyncJob($metadata);
    }

    $task = new ScheduledTaskPostEventReminders();
    $summary = lanlistRunPostEventRemindersTask($task);

    lanlistCompletePostEventRemindersAsyncJob($jobId, $summary, $metadata);
    fwrite(
        STDOUT,
        'OK post-event-reminders job_id=' . $jobId
        . ' events=' . $summary['eventsConsidered']
        . ' organizers_emailed=' . $summary['organizersEmailed']
        . ' emails_sent=' . $summary['emailsSent']
        . ' skipped_upcoming=' . $summary['skippedHasUpcoming']
        . ' skipped_no_recipients=' . $summary['skippedNoRecipients']
        . ' skipped_user_cap=' . $summary['skippedUserCap'] . "\n"
    );
    exit(0);
} catch (Throwable $e) {
    $msg = $e->getMessage();
    if ($jobId !== null) {
        lanlistFailPostEventRemindersAsyncJob($jobId, $msg);
    }
    fwrite(STDERR, 'ERROR: ' . $msg . "\n");
    exit(1);
}
