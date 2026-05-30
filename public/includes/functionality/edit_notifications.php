<?php

declare(strict_types=1);

/**
 * @return list<array{id: int, username: string, email: string}>
 */
function lanlistFetchOrganizerAssociatedUsers(int $organizerId): array
{
    global $db;

    $sql = 'SELECT u.id, u.username, u.email, u.organizerUpdateEmails, u.eventUpdateEmails, u.lastLowPriorityEmailDate, u.lastPostEventReminderEmailDate FROM users u WHERE u.organization = :organization';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':organization', $organizerId, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function lanlistNormalizeEditValue(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    $normalized = trim(stripslashes((string) $value));

    if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $normalized)) {
        return substr(str_replace('T', ' ', $normalized), 0, 16);
    }

    return $normalized;
}

function lanlistEditValuesEqual(mixed $before, mixed $after): bool
{
    return lanlistNormalizeEditValue($before) === lanlistNormalizeEditValue($after);
}

function lanlistEditFormatYesNo(mixed $value): string
{
    $normalized = lanlistNormalizeEditValue($value);

    return ($normalized === '1' || $normalized === 'true') ? 'yes' : 'no';
}

function lanlistEditFormatScalar(mixed $value): string
{
    $normalized = lanlistNormalizeEditValue($value);

    if ($normalized === '') {
        return '(empty)';
    }

    if (strlen($normalized) > 120) {
        return substr($normalized, 0, 117) . '...';
    }

    return $normalized;
}

function lanlistEditFormatLookup(string $type): callable
{
    return static function (mixed $value) use ($type): string {
        if ($value === null || $value === '') {
            return '(empty)';
        }

        $label = lookupField($value, $type);

        return $label === null ? '(empty)' : (string) $label;
    };
}

function lanlistEditFormatVenueId(mixed $value): string
{
    if ($value === null || $value === '') {
        return '(empty)';
    }

    try {
        return (string) fetchVenue((int) $value)['title'];
    } catch (Throwable) {
        return 'Venue #' . (int) $value;
    }
}

function lanlistEditFormatOrganizerId(mixed $value): string
{
    if ($value === null || $value === '') {
        return '(empty)';
    }

    try {
        return (string) fetchOrganizer((int) $value)['title'];
    } catch (Throwable) {
        return 'Organizer #' . (int) $value;
    }
}

/**
 * @param array<string, mixed> $before
 * @param array<string, mixed> $after
 * @param list<array{label: string, key: string, format?: callable(mixed): string}> $fields
 * @return list<array{label: string, old: string, new: string}>
 */
function lanlistCollectEditChanges(array $before, array $after, array $fields): array
{
    $changes = [];

    foreach ($fields as $field) {
        $key = $field['key'];
        $oldRaw = $before[$key] ?? null;
        $newRaw = $after[$key] ?? null;

        if (lanlistEditValuesEqual($oldRaw, $newRaw)) {
            continue;
        }

        $format = $field['format'] ?? null;
        $changes[] = [
            'label' => $field['label'],
            'old' => $format !== null ? $format($oldRaw) : lanlistEditFormatScalar($oldRaw),
            'new' => $format !== null ? $format($newRaw) : lanlistEditFormatScalar($newRaw),
        ];
    }

    return $changes;
}

/**
 * @return list<array{label: string, key: string, format?: callable(mixed): string}>
 */
function lanlistOrganizerEditFieldMap(): array
{
    return [
        ['label' => 'Published', 'key' => 'published', 'format' => static fn (mixed $value): string => lanlistEditFormatYesNo($value)],
        ['label' => 'Title', 'key' => 'title'],
        ['label' => 'Website', 'key' => 'websiteUrl'],
        ['label' => 'Assumed stale', 'key' => 'assumedStale'],
        ['label' => 'Generic email', 'key' => 'genericEmail'],
        ['label' => 'Steam group URL', 'key' => 'steamGroupUrl'],
        ['label' => 'Discord invite URL', 'key' => 'discordInviteUrl'],
        ['label' => 'LPPS feed URL', 'key' => 'lppsUrl'],
        ['label' => 'LPPS crawl disabled (admin)', 'key' => 'lppsAdminDisabled', 'format' => static fn (mixed $value): string => lanlistEditFormatYesNo($value)],
        ['label' => 'Blurb', 'key' => 'blurb'],
        ['label' => 'Use site favicon', 'key' => 'useFavicon', 'format' => static fn (mixed $value): string => lanlistEditFormatYesNo($value)],
        ['label' => 'Refetch favicon', 'key' => 'faviconRefetch', 'format' => static fn (mixed $value): string => lanlistEditFormatYesNo($value)],
    ];
}

/**
 * @return list<array{label: string, key: string, format?: callable(mixed): string}>
 */
function lanlistEventEditFieldMap(): array
{
    return [
        ['label' => 'Title', 'key' => 'title'],
        ['label' => 'Venue', 'key' => 'venue', 'format' => static fn (mixed $value): string => lanlistEditFormatVenueId($value)],
        ['label' => 'Start', 'key' => 'dateStart'],
        ['label' => 'Finish', 'key' => 'dateFinish'],
        ['label' => 'Event website', 'key' => 'website'],
        ['label' => 'Showers', 'key' => 'showers', 'format' => lanlistEditFormatLookup('showers')],
        ['label' => 'Sleeping', 'key' => 'sleeping', 'format' => lanlistEditFormatLookup('sleeping')],
        ['label' => 'Age restrictions', 'key' => 'ageRestrictions'],
        ['label' => 'Smoking', 'key' => 'smoking', 'format' => lanlistEditFormatLookup('smoking')],
        ['label' => 'Alcohol', 'key' => 'alcohol', 'format' => lanlistEditFormatLookup('alcohol')],
        ['label' => 'Number of seats', 'key' => 'numberOfSeats'],
        ['label' => 'Network (Mbps)', 'key' => 'networkMbps'],
        ['label' => 'Internet (Mbps)', 'key' => 'internetMbps'],
        ['label' => 'Blurb', 'key' => 'blurb'],
        ['label' => 'Organizer', 'key' => 'organizer', 'format' => static fn (mixed $value): string => lanlistEditFormatOrganizerId($value)],
    ];
}

/**
 * @param array<string, mixed> $before
 * @param array<string, mixed> $after
 * @return list<array{label: string, old: string, new: string}>
 */
function lanlistCollectOrganizerEditChanges(array $before, array $after): array
{
    return lanlistCollectEditChanges($before, $after, lanlistOrganizerEditFieldMap());
}

/**
 * @param array<string, mixed> $before
 * @param array<string, mixed> $after
 * @return list<array{label: string, old: string, new: string}>
 */
function lanlistCollectEventEditChanges(array $before, array $after): array
{
    return lanlistCollectEditChanges($before, $after, lanlistEventEditFieldMap());
}

function lanlistLowPriorityEmailThrottleSeconds(): int
{
    return 600;
}

/**
 * @param array<string, mixed> $user
 */
function lanlistUserRecentlyReceivedLowPriorityEmail(array $user): bool
{
    $last = $user['lastLowPriorityEmailDate'] ?? null;
    if ($last === null || $last === '') {
        return false;
    }

    $lastTs = strtotime((string) $last);

    return $lastTs !== false && (time() - $lastTs) < lanlistLowPriorityEmailThrottleSeconds();
}

function lanlistRecordLowPriorityEmailSent(int $userId): void
{
    global $db;

    $stmt = $db->prepare('UPDATE users SET lastLowPriorityEmailDate = NOW() WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * @param list<array{label: string, old: string, new: string}> $changes
 */
function lanlistSendOrganizerEditNotifications(int $organizerId, string $editorUsername, array $changes): void
{
    if ($changes === []) {
        return;
    }

    $organizer = fetchOrganizer($organizerId);

    lanlistSendEntityEditNotifications(
        'organizer',
        $organizerId,
        (string) $organizer['title'],
        SITE_BASE_URL . '/viewOrganizer.php?id=' . $organizerId,
        'Organizer updated: ' . $organizer['title'],
        $editorUsername,
        $changes
    );
}

/**
 * @param list<array{label: string, old: string, new: string}> $changes
 */
function lanlistSendEventEditNotifications(int $eventId, string $editorUsername, array $changes): void
{
    if ($changes === []) {
        return;
    }

    $event = fetchEvent($eventId);
    $organizerId = (int) ($event['organizerId'] ?? 0);
    if ($organizerId <= 0) {
        return;
    }

    lanlistSendEntityEditNotifications(
        'event',
        $organizerId,
        (string) $event['eventTitle'],
        SITE_BASE_URL . '/viewEvent.php?id=' . $eventId,
        'Event updated: ' . $event['eventTitle'],
        $editorUsername,
        $changes
    );
}

/**
 * @param list<array{label: string, old: string, new: string}> $changes
 */
function lanlistSendInlineOrganizerEditNotification(
    int $organizerId,
    string $fieldLabel,
    mixed $oldRaw,
    mixed $newRaw,
    ?callable $format,
    string $editorUsername
): void {
    if (lanlistEditValuesEqual($oldRaw, $newRaw)) {
        return;
    }

    lanlistSendOrganizerEditNotifications($organizerId, $editorUsername, [[
        'label' => $fieldLabel,
        'old' => $format !== null ? $format($oldRaw) : lanlistEditFormatScalar($oldRaw),
        'new' => $format !== null ? $format($newRaw) : lanlistEditFormatScalar($newRaw),
    ]]);
}

/**
 * @param list<array{label: string, old: string, new: string}> $changes
 */
function lanlistSendEntityEditNotifications(
    string $entityType,
    int $organizerId,
    string $entityTitle,
    string $entityUrl,
    string $subject,
    string $editorUsername,
    array $changes
): void
{
    global $tpl;

    $users = lanlistFetchOrganizerAssociatedUsers($organizerId);
    if ($users === []) {
        return;
    }

    $tpl->assign('siteBaseUrl', SITE_BASE_URL);
    $tpl->assign('siteTitle', SITE_TITLE);
    $tpl->assign('entityType', $entityType);
    $tpl->assign('entityTitle', $entityTitle);
    $tpl->assign('entityUrl', $entityUrl);
    $tpl->assign('editorUsername', $editorUsername);
    $tpl->assign('changes', $changes);

    foreach ($users as $user) {
        if ($entityType === 'organizer' && !lanlistUserReceivesOrganizerUpdateEmails($user)) {
            continue;
        }

        if ($entityType === 'event' && !lanlistUserReceivesEventUpdateEmails($user)) {
            continue;
        }

        if ($entityType === 'event' && lanlistUserRecentlyReceivedLowPriorityEmail($user)) {
            continue;
        }

        if (empty($user['email'])) {
            continue;
        }

        $tpl->assign('user', $user);
        sendEmail($user['email'], $tpl->fetch('email.entityEdited.tpl'), $subject);

        if ($entityType === 'event') {
            lanlistRecordLowPriorityEmailSent((int) $user['id']);
        }
    }
}

function lanlistDescribeUserSiteRole(int $groupId): ?string
{
    if ($groupId === ADMIN_GID) {
        return 'site admin';
    }

    if ($groupId === MODERATOR_GID) {
        return 'site moderator';
    }

    return null;
}

function lanlistSendNewEventNotifications(
    int $eventId,
    int $creatorId,
    string $creatorUsername,
    int $creatorGroupId
): void {
    global $tpl;

    $event = fetchEvent($eventId);
    $organizerId = (int) ($event['organizerId'] ?? 0);
    if ($organizerId <= 0) {
        return;
    }

    $users = lanlistFetchOrganizerAssociatedUsers($organizerId);
    if ($users === []) {
        return;
    }

    $creatorSiteRole = lanlistDescribeUserSiteRole($creatorGroupId);
    $eventUrl = SITE_BASE_URL . '/viewEvent.php?id=' . $eventId;
    $subject = 'New event: ' . $event['eventTitle'];

    $tpl->assign('siteBaseUrl', SITE_BASE_URL);
    $tpl->assign('siteTitle', SITE_TITLE);
    $tpl->assign('event', $event);
    $tpl->assign('eventUrl', $eventUrl);
    $tpl->assign('creatorUsername', $creatorUsername);
    $tpl->assign('creatorSiteRole', $creatorSiteRole);

    foreach ($users as $user) {
        if ((int) $user['id'] === $creatorId) {
            continue;
        }

        if (empty($user['email'])) {
            continue;
        }

        $tpl->assign('user', $user);
        sendEmail($user['email'], $tpl->fetch('email.eventCreated.tpl'), $subject);
    }
}
