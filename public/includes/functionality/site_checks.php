<?php

declare(strict_types=1);

require_once __DIR__ . '/organizer_visibility.php';

/**
 * @return array<int, int>
 */
function lanlistTicketsNotReleasedMilestones(): array
{
    return [60, 45, 30, 15, 0];
}

function lanlistNextTicketsNotReleasedUntil(string $dateStart): ?string
{
    $startTs = strtotime($dateStart);
    if ($startTs === false) {
        return null;
    }

    $now = time();
    foreach (lanlistTicketsNotReleasedMilestones() as $daysBeforeStart) {
        $untilTs = $startTs - ($daysBeforeStart * 86400);
        if ($untilTs > $now) {
            return date('Y-m-d H:i:s', $untilTs);
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $event
 */
function lanlistEventSilencesMissingTickets(array $event): bool
{
    $until = $event['ticketsNotReleasedUntil'] ?? null;
    if ($until === null || $until === '') {
        return false;
    }

    $untilTs = strtotime((string) $until);

    return $untilTs !== false && $untilTs > time();
}

function lanlistTicketsNotReleasedDaysRemaining(?string $until): int
{
    if ($until === null || $until === '') {
        return 0;
    }

    $untilTs = strtotime($until);
    if ($untilTs === false || $untilTs <= time()) {
        return 0;
    }

    return max(1, (int) ceil(($untilTs - time()) / 86400));
}

/**
 * @return array<int, array<string, mixed>>
 */
function lanlistFetchEventsWithIssues(): array
{
    require_once __DIR__ . '/../classes/EventsChecker.php';

    $checker = new EventsChecker();
    $checker->checkAllEvents();

    return $checker->getEventsList();
}

/**
 * @return array<int, array<string, mixed>>
 */
function lanlistFetchEventsWithSilencedTicketWarning(): array
{
    global $db;

    $sql = 'SELECT e.*, o.id AS organizerId, o.title AS organizerTitle, COUNT(t.id) AS ticketCount
            FROM events e
            LEFT JOIN tickets t ON e.id = t.event
            LEFT JOIN organizers o ON e.organizer = o.id
            WHERE e.dateStart > NOW()
            AND COALESCE(e.published, 0) = 1
            AND (e.priceInAdv IS NULL OR e.priceInAdv = 0)
            AND e.ticketsNotReleasedUntil IS NOT NULL
            AND e.ticketsNotReleasedUntil > NOW()' . lanlistSqlExcludeModerationOrganizerEvents() . '
            GROUP BY e.id
            HAVING ticketCount = 0
            ORDER BY e.ticketsNotReleasedUntil ASC';
    $result = $db->query($sql);
    $events = $result->fetchAll();

    foreach ($events as &$event) {
        $event['ticketsNotReleasedDaysRemaining'] = lanlistTicketsNotReleasedDaysRemaining(
            isset($event['ticketsNotReleasedUntil']) ? (string) $event['ticketsNotReleasedUntil'] : null
        );
    }
    unset($event);

    return $events;
}

/**
 * @return array<int, array<string, mixed>>
 */
function lanlistFetchUnpublishedOrganizers(): array
{
    global $db;

    $sql = 'SELECT o.id, o.title, o.websiteUrl, o.lastChecked, o.published, COUNT(u.id) AS assUserCount, o.assumedStale
            FROM organizers o
            LEFT JOIN users u ON u.organization = o.id AND u.email IS NOT NULL
            WHERE COALESCE(o.published, 0) = 0' . lanlistSqlExcludeModerationOrganizers() . '
            GROUP BY o.id
            ORDER BY o.title';
    $result = $db->query($sql);

    return $result->fetchAll();
}

/**
 * @return array<int, array<string, mixed>>
 */
function lanlistFetchOrganizersWithNoUpcomingEvents(): array
{
    global $db;

    $sql = 'SELECT o.id, o.title, o.websiteUrl, o.lastChecked, COUNT(u.id) AS assUserCount, o.assumedStale
            FROM organizers o
            LEFT JOIN users u ON u.organization = o.id AND u.email IS NOT NULL
            LEFT JOIN events e ON o.id = e.organizer AND e.dateFinish > NOW()
            WHERE e.id IS NULL
            AND COALESCE(o.published, 0) = 1
            AND o.assumedStale IS NULL
            AND (o.lastChecked IS NULL OR o.lastChecked < NOW() - INTERVAL 60 DAY)' . lanlistSqlExcludeModerationOrganizers() . '
            GROUP BY o.id
            ORDER BY o.lastChecked ASC';
    $result = $db->query($sql);

    return $result->fetchAll();
}

/**
 * @return array{
 *     eventsWithIssues: array<int, array<string, mixed>>,
 *     eventsWithSilencedTicketWarning: array<int, array<string, mixed>>,
 *     unpublishedOrganizers: array<int, array<string, mixed>>,
 *     organizersWithNoEvents: array<int, array<string, mixed>>,
 * }
 */
function lanlistFetchModeratorPanelData(): array
{
    return [
        'eventsWithIssues' => lanlistFetchEventsWithIssues(),
        'eventsWithSilencedTicketWarning' => lanlistFetchEventsWithSilencedTicketWarning(),
        'unpublishedOrganizers' => lanlistFetchUnpublishedOrganizers(),
        'organizersWithNoEvents' => lanlistFetchOrganizersWithNoUpcomingEvents(),
    ];
}

/**
 * @param array{
 *     eventsWithIssues?: array<int, array<string, mixed>>,
 *     unpublishedOrganizers?: array<int, array<string, mixed>>,
 *     organizersWithNoEvents?: array<int, array<string, mixed>>,
 * } $panel
 */
function lanlistModeratorPanelIssueCount(array $panel): int
{
    return count($panel['eventsWithIssues'] ?? [])
        + count($panel['unpublishedOrganizers'] ?? [])
        + count($panel['organizersWithNoEvents'] ?? []);
}
