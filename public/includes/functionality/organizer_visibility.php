<?php

declare(strict_types=1);

use libAllure\Session;

/**
 * @return array<int, int>
 */
function lanlistModerationExcludedOrganizerIds(): array
{
    if (!defined('MODERATION_EXCLUDED_ORGANIZER_IDS')) {
        return [];
    }

    $raw = trim((string) MODERATION_EXCLUDED_ORGANIZER_IDS);
    if ($raw === '') {
        return [];
    }

    $ids = [];
    foreach (explode(',', $raw) as $part) {
        $id = (int) trim($part);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function lanlistOrganizerIsModerationExcluded(int $organizerId): bool
{
    return in_array($organizerId, lanlistModerationExcludedOrganizerIds(), true);
}

/**
 * SQL fragment excluding test/sandbox organizers from moderation site checks.
 */
function lanlistSqlExcludeModerationOrganizers(string $organizerIdColumn = 'o.id'): string
{
    $excluded = lanlistModerationExcludedOrganizerIds();
    if ($excluded === []) {
        return '';
    }

    return ' AND ' . $organizerIdColumn . ' NOT IN (' . implode(',', $excluded) . ')';
}

/**
 * SQL fragment excluding events tied to test/sandbox organizers (NULL organizer kept).
 */
function lanlistSqlExcludeModerationOrganizerEvents(string $eventOrganizerColumn = 'e.organizer'): string
{
    $excluded = lanlistModerationExcludedOrganizerIds();
    if ($excluded === []) {
        return '';
    }

    $list = implode(',', $excluded);

    return ' AND (' . $eventOrganizerColumn . ' IS NULL OR ' . $eventOrganizerColumn . ' NOT IN (' . $list . '))';
}

/**
 * SQL fragment requiring a joined organizer alias to be publicly visible.
 */
function lanlistSqlPublicOrganizerVisible(string $organizerAlias = 'o'): string
{
    $conditions = ['COALESCE(' . $organizerAlias . '.published, 0) = 1'];
    $excluded = lanlistModerationExcludedOrganizerIds();
    if ($excluded !== []) {
        $conditions[] = $organizerAlias . '.id NOT IN (' . implode(',', $excluded) . ')';
    }

    return ' AND ' . implode(' AND ', $conditions);
}

/**
 * Extra JOIN conditions for counting/listing public events (organizer must be visible).
 */
function lanlistSqlPublicVisibleEventJoinConditions(string $eventAlias = 'e'): string
{
    $conditions = [
        $eventAlias . '.published = 1',
        'EXISTS (SELECT 1 FROM organizers o2 WHERE o2.id = ' . $eventAlias . '.organizer AND COALESCE(o2.published, 0) = 1',
    ];
    $excluded = lanlistModerationExcludedOrganizerIds();
    if ($excluded !== []) {
        $conditions[1] .= ' AND o2.id NOT IN (' . implode(',', $excluded) . ')';
    }
    $conditions[1] .= ')';

    return implode(' AND ', $conditions);
}

/**
 * @param array<string, mixed> $organizer
 */
function lanlistOrganizerIsPubliclyVisible(array $organizer): bool
{
    if (empty((int) ($organizer['published'] ?? 0))) {
        return false;
    }

    return !lanlistOrganizerIsModerationExcluded((int) ($organizer['id'] ?? 0));
}

/**
 * @param array<string, mixed> $event
 * @param array<string, mixed>|null $organizer
 */
function lanlistEventIsPubliclyVisible(array $event, ?array $organizer = null): bool
{
    if (empty((int) ($event['published'] ?? 0))) {
        return false;
    }

    $organizerId = (int) ($event['organizerId'] ?? $event['organizer'] ?? 0);
    if ($organizerId <= 0) {
        return true;
    }

    if ($organizer !== null) {
        return lanlistOrganizerIsPubliclyVisible($organizer);
    }

    try {
        return lanlistOrganizerIsPubliclyVisible(fetchOrganizer($organizerId));
    } catch (Throwable) {
        return false;
    }
}

function lanlistCanViewNonPublicOrganizer(int $organizerId): bool
{
    if (!Session::isLoggedIn()) {
        return false;
    }

    if (Session::hasPriv('MODERATOR') || Session::hasPriv('SUPERUSER')) {
        return true;
    }

    return (int) Session::getUser()->getData('organization') === $organizerId;
}
