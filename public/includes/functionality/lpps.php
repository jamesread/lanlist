<?php

declare(strict_types=1);

use libAllure\Session;

/** Canonical LPPS specification repository. */
const LANLIST_LPPS_STANDARD_URL = 'https://github.com/jamesread/lan-party-publishing-standard';

function lanlistLppsInfoPagePath(): string
{
    return 'lpps.php';
}

/**
 * Site admins / moderators may disable LPPS crawls for an organizer.
 */
function lanlistUserCanAdministerOrganizerLpps(): bool
{
    return Session::hasPriv('MODERATOR') || Session::hasPriv('SUPERUSER');
}

/**
 * Whether a future LPPS crawl job should process this organizer.
 *
 * @param array<string, mixed> $organizer
 */
function lanlistOrganizerLppsCrawlEligible(array $organizer): bool
{
    if (!empty((int) ($organizer['lppsAdminDisabled'] ?? 0))) {
        return false;
    }

    return trim((string) ($organizer['lppsUrl'] ?? '')) !== '';
}

/**
 * Record the outcome of an LPPS crawl (used by scripts/lpps-crawl.py via SQL; available for PHP callers).
 */
function lanlistRecordOrganizerLppsCrawlResult(int $organizerId, bool $success, string $result): void
{
    global $db;

    if (function_exists('mb_substr')) {
        $result = mb_substr($result, 0, 1024);
    } else {
        $result = substr($result, 0, 1024);
    }

    $stmt = $db->prepare(
        'UPDATE organizers
         SET lppsLastCrawl = NOW(),
             lppsCrawlSuccess = :success,
             lppsCrawlResult = :result
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->bindValue(':id', $organizerId, \libAllure\Database::PARAM_INT);
    $stmt->bindValue(':success', $success ? 1 : 0, \libAllure\Database::PARAM_INT);
    $stmt->bindValue(':result', $result);
    $stmt->execute();
}
