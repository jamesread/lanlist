<?php

declare(strict_types=1);

if (!defined('LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH')) {
    define('LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH', 'organizer_favicon_fetch');
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
