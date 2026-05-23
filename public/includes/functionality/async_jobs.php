<?php

declare(strict_types=1);

if (!defined('LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH')) {
    define('LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH', 'organizer_favicon_fetch');
}

if (!defined('LANLIST_JOB_TYPE_ADMIN_NEWSLETTER')) {
    define('LANLIST_JOB_TYPE_ADMIN_NEWSLETTER', 'admin_newsletter');
}

const LANLIST_NEWSLETTER_SCHEDULER_CLASS = 'ScheduledTaskNewsletter';

/**
 * Human-readable labels for async_jobs.job_type in the admin UI.
 *
 * @return array<string, string>
 */
function lanlistAsyncJobTypeLabels(): array
{
    return [
        LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH => 'Organizer favicon fetch',
        LANLIST_JOB_TYPE_ADMIN_NEWSLETTER => 'Moderator newsletter',
    ];
}

function lanlistAsyncJobTypeLabel(string $jobType): string
{
    $labels = lanlistAsyncJobTypeLabels();

    return $labels[$jobType] ?? $jobType;
}

/**
 * @return array<int, array<string, mixed>>
 */
function lanlistFetchAsyncJobsForAdminList(int $limit = 100): array
{
    global $db;

    $limit = max(1, min(500, $limit));
    $sql = 'SELECT j.id, j.job_type, j.organizer_id, o.title AS organizer_title, j.status,
                   j.execution_tracking_id, j.created_at, j.started_at, j.finished_at,
                   j.error_message, j.metadata
            FROM async_jobs j
            LEFT JOIN organizers o ON j.organizer_id = o.id
            ORDER BY j.id DESC
            LIMIT ' . (int) $limit;
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) {
        return [];
    }

    foreach ($rows as $k => $row) {
        $rows[$k]['job_type_label'] = lanlistAsyncJobTypeLabel((string) $row['job_type']);
        $meta = $row['metadata'] ?? null;
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $rows[$k]['metadataDecoded'] = is_array($decoded) ? $decoded : [];
        } elseif (is_array($meta)) {
            $rows[$k]['metadataDecoded'] = $meta;
        } else {
            $rows[$k]['metadataDecoded'] = [];
        }
    }

    return $rows;
}

/**
 * Last successful newsletter watermark (scheduler_tasks legacy row).
 */
function lanlistFetchNewsletterLastRunTime(): ?string
{
    global $db;

    $sql = 'SELECT lastRunTime FROM scheduler_tasks WHERE className = :cn LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':cn', LANLIST_NEWSLETTER_SCHEDULER_CLASS);
    $stmt->execute();
    $row = $stmt->fetchRow();
    if ($row === false || empty($row['lastRunTime'])) {
        return null;
    }

    return (string) $row['lastRunTime'];
}

function lanlistTouchNewsletterLastRunTime(): void
{
    global $db;

    $sql = 'UPDATE scheduler_tasks SET lastRunTime = NOW() WHERE className = :cn LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':cn', LANLIST_NEWSLETTER_SCHEDULER_CLASS);
    $stmt->execute();
}

/**
 * @return array{updateCount: int, emailSent: bool}
 */
function lanlistRunNewsletterTask(ScheduledTaskNewsletter $task): array
{
    $task->execute();

    return $task->getLastRunSummary();
}

/**
 * @param array<string, mixed> $metadata
 */
function lanlistInsertNewsletterAsyncJob(array $metadata): int
{
    global $db;

    $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $ins = $db->prepare(
        'INSERT INTO async_jobs (job_type, organizer_id, status, metadata, started_at)
         VALUES (:jt, NULL, :st, :meta, NOW())'
    );
    $ins->bindValue(':jt', LANLIST_JOB_TYPE_ADMIN_NEWSLETTER);
    $ins->bindValue(':st', 'processing');
    $ins->bindValue(':meta', $metadataJson);
    $ins->execute();

    return (int) $db->lastInsertId();
}

/**
 * @param array<string, mixed> $metadata
 */
function lanlistBeginNewsletterAsyncJob(int $jobId, array $metadata): int
{
    global $db;

    $sel = $db->prepare(
        'SELECT id, job_type, status FROM async_jobs WHERE id = :id LIMIT 1'
    );
    $sel->bindValue(':id', $jobId, \PDO::PARAM_INT);
    $sel->execute();
    $row = $sel->fetchRow();
    if ($row === false) {
        throw new InvalidArgumentException('async_jobs row not found id=' . $jobId);
    }
    if ((string) $row['job_type'] !== LANLIST_JOB_TYPE_ADMIN_NEWSLETTER) {
        throw new InvalidArgumentException('job #' . $jobId . ' is not a moderator newsletter job');
    }

    $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $up = $db->prepare(
        'UPDATE async_jobs SET status = \'processing\', started_at = NOW(), metadata = :meta, error_message = NULL
         WHERE id = :id AND job_type = :jt LIMIT 1'
    );
    $up->bindValue(':meta', $metadataJson);
    $up->bindValue(':id', $jobId, \PDO::PARAM_INT);
    $up->bindValue(':jt', LANLIST_JOB_TYPE_ADMIN_NEWSLETTER);
    $up->execute();

    return $jobId;
}

/**
 * @param array{updateCount: int, emailSent: bool} $summary
 * @param array<string, mixed> $extraMetadata
 */
function lanlistCompleteNewsletterAsyncJob(int $jobId, array $summary, array $extraMetadata = []): void
{
    global $db;

    $meta = json_encode(
        array_merge(
            $extraMetadata,
            [
                'updateCount' => $summary['updateCount'],
                'emailSent' => $summary['emailSent'],
            ]
        ),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
    );
    $up = $db->prepare(
        'UPDATE async_jobs SET status = \'completed\', finished_at = NOW(), error_message = NULL, metadata = :meta
         WHERE id = :id AND job_type = :jt LIMIT 1'
    );
    $up->bindValue(':meta', $meta);
    $up->bindValue(':id', $jobId, \PDO::PARAM_INT);
    $up->bindValue(':jt', LANLIST_JOB_TYPE_ADMIN_NEWSLETTER);
    $up->execute();
}

function lanlistFailNewsletterAsyncJob(int $jobId, string $message): void
{
    global $db;

    $msg = substr(trim($message), 0, 62000);
    $up = $db->prepare(
        'UPDATE async_jobs SET status = \'failed\', finished_at = NOW(), error_message = :em
         WHERE id = :id AND job_type = :jt LIMIT 1'
    );
    $up->bindValue(':em', $msg);
    $up->bindValue(':id', $jobId, \PDO::PARAM_INT);
    $up->bindValue(':jt', LANLIST_JOB_TYPE_ADMIN_NEWSLETTER);
    $up->execute();
}

/**
 * @param mixed $metadata
 * @return array<string, mixed>
 */
function lanlistDecodeAsyncJobMetadata($metadata): array
{
    if (is_string($metadata)) {
        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }
    if (is_array($metadata)) {
        return $metadata;
    }

    return [];
}

/**
 * Latest favicon job row with metadataDecoded for admin/moderation templates.
 *
 * @return array<string, mixed>|null
 */
function lanlistFetchLatestOrganizerFaviconAsyncJobForDisplay(int $organizerId): ?array
{
    $job = lanlistFetchLatestOrganizerFaviconAsyncJob($organizerId);
    if ($job === null) {
        return null;
    }
    $job['metadataDecoded'] = lanlistDecodeAsyncJobMetadata($job['metadata'] ?? null);

    return $job;
}

/**
 * Pending or processing favicon job rows block duplicate enqueue.
 *
 * @return array<string, mixed>|false Row or false when none active
 */
function lanlistSelectActiveOrganizerFaviconJob(int $organizerId)
{
    global $db;

    $sql = 'SELECT id, status FROM async_jobs
            WHERE job_type = :jt
              AND organizer_id = :oid
              AND status IN (\'pending\', \'processing\')
            ORDER BY id DESC
            LIMIT 1';

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':jt', LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH);
    $stmt->bindValue(':oid', $organizerId, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchRow();
}

/**
 * Latest job row for moderation display (any status).
 *
 * @return array<string, mixed>|null
 */
function lanlistFetchLatestOrganizerFaviconAsyncJob(int $organizerId): ?array
{
    global $db;

    $sql = 'SELECT id, status, execution_tracking_id, created_at, started_at, finished_at, error_message, metadata
            FROM async_jobs
            WHERE job_type = :jt AND organizer_id = :oid
            ORDER BY id DESC
            LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':jt', LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH);
    $stmt->bindValue(':oid', $organizerId, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchRow();
    if ($row === false) {
        return null;
    }

    return $row;
}

/**
 * Remove a job row from async_jobs (admin abandon). Completed jobs cannot be abandoned.
 *
 * @return array<string, mixed>|null|false Deleted row, null if missing, false if completed
 */
function lanlistAbandonAsyncJob(int $jobId)
{
    global $db;

    $sel = $db->prepare(
        'SELECT id, job_type, organizer_id, status FROM async_jobs WHERE id = :id LIMIT 1'
    );
    $sel->bindValue(':id', $jobId, \PDO::PARAM_INT);
    $sel->execute();
    $row = $sel->fetchRow();
    if ($row === false) {
        return null;
    }

    if ((string) $row['status'] === 'completed') {
        return false;
    }

    $del = $db->prepare('DELETE FROM async_jobs WHERE id = :id AND status != \'completed\' LIMIT 1');
    $del->bindValue(':id', $jobId, \PDO::PARAM_INT);
    $del->execute();
    if ($del->rowCount() === 0) {
        return false;
    }

    if (
        (string) $row['job_type'] === LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH
        && in_array((string) $row['status'], ['pending', 'processing'], true)
        && $row['organizer_id'] !== null
        && lanlistSelectActiveOrganizerFaviconJob((int) $row['organizer_id']) === false
    ) {
        $upOrg = $db->prepare('UPDATE organizers SET faviconRefetch = 0 WHERE id = :oid LIMIT 1');
        $upOrg->bindValue(':oid', (int) $row['organizer_id'], \PDO::PARAM_INT);
        $upOrg->execute();
    }

    return $row;
}
