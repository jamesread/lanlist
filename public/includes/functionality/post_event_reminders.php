<?php

declare(strict_types=1);

require_once __DIR__ . '/edit_notifications.php';
require_once __DIR__ . '/organizer_visibility.php';

/**
 * Calendar days after dateFinish before a post-event reminder may be sent.
 */
function lanlistPostEventReminderDaysAfterFinish(): int
{
    return 2;
}

/**
 * How many calendar days after the target we still attempt send (missed daily runs).
 */
function lanlistPostEventReminderCatchUpDays(): int
{
    return 2;
}

/**
 * Rolling window for the per-user post-event reminder cap.
 */
function lanlistPostEventReminderPerUserCooldownDays(): int
{
    return 30;
}

/**
 * @param array<string, mixed> $user
 */
function lanlistUserRecentlyReceivedPostEventReminder(array $user): bool
{
    $last = $user['lastPostEventReminderEmailDate'] ?? null;
    if ($last === null || $last === '') {
        return false;
    }

    $lastTs = strtotime((string) $last);
    if ($lastTs === false) {
        return false;
    }

    $cooldownSeconds = lanlistPostEventReminderPerUserCooldownDays() * 86400;

    return (time() - $lastTs) < $cooldownSeconds;
}

function lanlistRecordPostEventReminderEmailSent(int $userId): void
{
    global $db;

    $stmt = $db->prepare('UPDATE users SET lastPostEventReminderEmailDate = NOW() WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * Events whose finish date falls in the reminder window and have not been emailed yet.
 *
 * @return list<array<string, mixed>>
 */
function lanlistFetchEventsDueForPostEventReminder(): array
{
    global $db;

    $daysAfter = lanlistPostEventReminderDaysAfterFinish();
    $catchUp = lanlistPostEventReminderCatchUpDays();

    $sql = 'SELECT e.id, e.title, e.organizer, e.dateFinish, e.dateStart, o.title AS organizerTitle
        FROM events e
        INNER JOIN organizers o ON e.organizer = o.id
        WHERE e.published = 1
            AND e.organizer IS NOT NULL
            AND e.postEventReminderSentAt IS NULL
            AND DATE(e.dateFinish) <= DATE(DATE_SUB(CURDATE(), INTERVAL :daysAfter DAY))
            AND DATE(e.dateFinish) >= DATE(DATE_SUB(CURDATE(), INTERVAL :maxDaysAfter DAY))'
        . lanlistSqlPublicOrganizerVisible('o') . '
        ORDER BY e.organizer ASC, e.dateFinish DESC';

    $maxDaysAfter = $daysAfter + $catchUp;
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':daysAfter', $daysAfter, \PDO::PARAM_INT);
    $stmt->bindValue(':maxDaysAfter', $maxDaysAfter, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function lanlistOrganizerHasUpcomingPublishedEvents(int $organizerId): bool
{
    global $db;

    $sql = 'SELECT 1 FROM events e
        WHERE e.organizer = :organizer
            AND e.published = 1
            AND e.dateFinish > NOW()
        LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':organizer', $organizerId, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchRow() !== false;
}

/**
 * @param list<array<string, mixed>> $events
 * @return list<array{organizerId: int, organizerTitle: string, event: array<string, mixed>, eventIds: list<int>}>
 */
function lanlistGroupPostEventReminderEventsByOrganizer(array $events): array
{
    $byOrganizer = [];

    foreach ($events as $event) {
        $organizerId = (int) ($event['organizer'] ?? 0);
        if ($organizerId <= 0) {
            continue;
        }

        if (!isset($byOrganizer[$organizerId])) {
            $byOrganizer[$organizerId] = [
                'organizerId' => $organizerId,
                'organizerTitle' => (string) ($event['organizerTitle'] ?? ''),
                'event' => $event,
                'eventIds' => [],
            ];
        }

        $byOrganizer[$organizerId]['eventIds'][] = (int) $event['id'];
    }

    return array_values($byOrganizer);
}

function lanlistMarkPostEventReminderSent(array $eventIds): void
{
    global $db;

    if ($eventIds === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $sql = 'UPDATE events SET postEventReminderSentAt = NOW() WHERE id IN (' . $placeholders . ')';
    $stmt = $db->prepare($sql);
    foreach ($eventIds as $i => $eventId) {
        $stmt->bindValue($i + 1, (int) $eventId, \PDO::PARAM_INT);
    }
    $stmt->execute();
}

/**
 * @return array{eventsConsidered: int, organizersEmailed: int, emailsSent: int, skippedHasUpcoming: int, skippedNoRecipients: int, skippedUserCap: int}
 */
function lanlistRunPostEventReminders(): array
{
    global $tpl;

    $summary = [
        'eventsConsidered' => 0,
        'organizersEmailed' => 0,
        'emailsSent' => 0,
        'skippedHasUpcoming' => 0,
        'skippedNoRecipients' => 0,
        'skippedUserCap' => 0,
    ];

    $dueEvents = lanlistFetchEventsDueForPostEventReminder();
    $summary['eventsConsidered'] = count($dueEvents);
    $groups = lanlistGroupPostEventReminderEventsByOrganizer($dueEvents);

    $tpl->assign('siteBaseUrl', SITE_BASE_URL);
    $tpl->assign('siteTitle', SITE_TITLE);
    $addEventUrl = SITE_BASE_URL . '/formHandler.php?formClazz=FormNewEvent';

    foreach ($groups as $group) {
        $organizerId = $group['organizerId'];

        if (lanlistOrganizerHasUpcomingPublishedEvents($organizerId)) {
            $summary['skippedHasUpcoming']++;
            lanlistMarkPostEventReminderSent($group['eventIds']);

            if (PHP_SAPI === 'cli') {
                fwrite(
                    STDOUT,
                    'post-event reminder skip organizer_id=' . $organizerId
                    . ' reason=has_upcoming_events event_ids=' . implode(',', $group['eventIds']) . "\n"
                );
            }

            continue;
        }

        $users = lanlistFetchOrganizerAssociatedUsers($organizerId);
        $recipients = [];

        foreach ($users as $user) {
            if (!lanlistUserReceivesOrganizerUpdateEmails($user)) {
                continue;
            }

            if (empty($user['email'])) {
                continue;
            }

            if (lanlistUserRecentlyReceivedPostEventReminder($user)) {
                $summary['skippedUserCap']++;
                continue;
            }

            $recipients[] = $user;
        }

        if ($recipients === []) {
            $summary['skippedNoRecipients']++;
            lanlistMarkPostEventReminderSent($group['eventIds']);

            if (PHP_SAPI === 'cli') {
                fwrite(
                    STDOUT,
                    'post-event reminder skip organizer_id=' . $organizerId
                    . ' reason=no_recipients event_ids=' . implode(',', $group['eventIds']) . "\n"
                );
            }

            continue;
        }

        $event = $group['event'];
        $organizer = [
            'id' => $organizerId,
            'title' => $group['organizerTitle'],
        ];
        $organizerUrl = SITE_BASE_URL . '/viewOrganizer.php?id=' . $organizerId;
        $subject = 'How did ' . $event['title'] . ' go?';

        $tpl->assign('event', $event);
        $tpl->assign('organizer', $organizer);
        $tpl->assign('organizerUrl', $organizerUrl);
        $tpl->assign('addEventUrl', $addEventUrl);

        foreach ($recipients as $user) {
            $tpl->assign('user', $user);
            $body = $tpl->fetch('email.postEventReminder.tpl');

            if (PHP_SAPI === 'cli') {
                fwrite(
                    STDOUT,
                    'post-event reminder send organizer_id=' . $organizerId
                    . ' event_id=' . (int) $event['id']
                    . ' username=' . $user['username']
                    . ' email=' . $user['email'] . "\n"
                );
            }

            sendEmail($user['email'], $body, $subject);
            lanlistRecordPostEventReminderEmailSent((int) $user['id']);
            $summary['emailsSent']++;
        }

        if ($recipients !== []) {
            $summary['organizersEmailed']++;
        }
        lanlistMarkPostEventReminderSent($group['eventIds']);
    }

    return $summary;
}
