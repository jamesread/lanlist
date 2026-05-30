<?php

declare(strict_types=1);

require_once __DIR__ . '/site_checks.php';
require_once __DIR__ . '/async_jobs.php';

/**
 * Audit log event types that count as moderator maintenance work.
 *
 * @return list<string>
 */
function lanlistModeratorActionEventTypes(): array
{
    return [
        'EDIT_EVENT',
        'EDIT_ORGANIZER',
        'MODERATION_NO_EVENTS',
        'MODERATION_MARK_STALE',
        'ORGANIZER_LAST_CHECKED',
        'EVENT_TICKETS_NOT_RELEASED',
        'INLINE_EDIT_ORGANIZER',
        'INLINE_PUBLISH_ORGANIZER',
        'JOIN_REQUEST_APPROVED',
        'JOIN_REQUEST_DENIED',
        'TOGGLE_EVENT_PUBLISHED',
        'CREATE_VENUE',
        'STAFF_EMAIL_SENT',
    ];
}

/**
 * Actions that likely cleared or improved a site-check issue.
 *
 * @return list<string>
 */
function lanlistModeratorIssueResolutionEventTypes(): array
{
    return [
        'EDIT_EVENT',
        'EVENT_TICKETS_NOT_RELEASED',
        'INLINE_PUBLISH_ORGANIZER',
        'EDIT_ORGANIZER',
        'ORGANIZER_LAST_CHECKED',
        'MODERATION_NO_EVENTS',
        'MODERATION_MARK_STALE',
        'TOGGLE_EVENT_PUBLISHED',
    ];
}

/**
 * @return list<string>
 */
function lanlistModeratorOrganizerCheckedEventTypes(): array
{
    return [
        'ORGANIZER_LAST_CHECKED',
        'MODERATION_NO_EVENTS',
    ];
}

/**
 * @return list<string>
 */
function lanlistModeratorJoinRequestEventTypes(): array
{
    return [
        'JOIN_REQUEST_APPROVED',
        'JOIN_REQUEST_DENIED',
    ];
}

/**
 * @return array<int, array{id: int, username: string}>
 */
function lanlistFetchModeratorUsers(): array
{
    global $db;

    $sql = 'SELECT id, username FROM users WHERE `group` = :moderator_gid OR `group` = :admin_gid ORDER BY username';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':moderator_gid', MODERATOR_GID, \PDO::PARAM_INT);
    $stmt->bindValue(':admin_gid', ADMIN_GID, \PDO::PARAM_INT);
    $stmt->execute();

    $users = [];
    foreach ($stmt->fetchAll() as $row) {
        $users[] = [
            'id' => (int) $row['id'],
            'username' => (string) $row['username'],
        ];
    }

    return $users;
}

function lanlistLogContentAttributesToModerator(string $content, array $moderators): ?string
{
    $lower = strtolower($content);

    foreach ($moderators as $moderator) {
        $username = (string) $moderator['username'];
        $pattern = '/\bby:?\s' . preg_quote(strtolower($username), '/') . '(\s|\(|$)/';

        if (preg_match($pattern, $lower) === 1) {
            return $username;
        }
    }

    return null;
}

/**
 * @param list<string> $eventTypes
 */
function lanlistModeratorLogSqlInClause(array $eventTypes): string
{
    return implode(',', array_map(
        static fn (string $type): string => "'" . str_replace("'", "''", $type) . "'",
        $eventTypes
    ));
}

/**
 * @param list<string> $eventTypes
 * @return array<int, array<string, mixed>>
 */
function lanlistFetchModeratorActionLogsSince(array $eventTypes, int $days): array
{
    global $db;

    if ($eventTypes === []) {
        return [];
    }

    $in = lanlistModeratorLogSqlInClause($eventTypes);
    $sql = 'SELECT id, eventType, timestamp, content FROM logs
            WHERE eventType IN (' . $in . ')
            AND timestamp >= NOW() - INTERVAL ' . max(1, $days) . ' DAY
            ORDER BY timestamp DESC';
    $result = $db->query($sql);

    return $result->fetchAll();
}

function lanlistCountModeratorActionsForUsername(string $username, int $days, ?array $eventTypes = null): int
{
    $logs = lanlistFetchModeratorActionLogsSince($eventTypes ?? lanlistModeratorActionEventTypes(), $days);
    $moderators = [['id' => 0, 'username' => $username]];
    $count = 0;

    foreach ($logs as $log) {
        if (lanlistLogContentAttributesToModerator((string) $log['content'], $moderators) === $username) {
            $count++;
        }
    }

    return $count;
}

/**
 * @param array<int, array<string, mixed>> $logs
 * @param array<int, array{id: int, username: string}> $moderators
 * @return array<int, array<string, mixed>>
 */
function lanlistFilterLogsToModerators(array $logs, array $moderators): array
{
    $filtered = [];

    foreach ($logs as $log) {
        if (lanlistLogContentAttributesToModerator((string) $log['content'], $moderators) !== null) {
            $filtered[] = $log;
        }
    }

    return $filtered;
}

/**
 * @param list<string> $eventTypes
 */
function lanlistCountTeamModeratorActions(int $days, array $eventTypes): int
{
    $moderators = lanlistFetchModeratorUsers();
    $logs = lanlistFilterLogsToModerators(
        lanlistFetchModeratorActionLogsSince($eventTypes, $days),
        $moderators
    );

    return count($logs);
}

/**
 * @return array<int, array{username: string, total: int, organizersChecked: int, joinRequests: int, issuesAddressed: int}>
 */
function lanlistFetchTeamModeratorActivity(int $days = 30): array
{
    $moderators = lanlistFetchModeratorUsers();
    $byUsername = [];

    foreach ($moderators as $moderator) {
        $byUsername[$moderator['username']] = [
            'username' => $moderator['username'],
            'total' => 0,
            'organizersChecked' => 0,
            'joinRequests' => 0,
            'issuesAddressed' => 0,
        ];
    }

    $organizerChecked = array_flip(lanlistModeratorOrganizerCheckedEventTypes());
    $joinRequest = array_flip(lanlistModeratorJoinRequestEventTypes());
    $issuesAddressed = array_flip(lanlistModeratorIssueResolutionEventTypes());

    foreach (lanlistFetchModeratorActionLogsSince(lanlistModeratorActionEventTypes(), $days) as $log) {
        $username = lanlistLogContentAttributesToModerator((string) $log['content'], $moderators);
        if ($username === null || !isset($byUsername[$username])) {
            continue;
        }

        $eventType = (string) $log['eventType'];
        $byUsername[$username]['total']++;

        if (isset($organizerChecked[$eventType])) {
            $byUsername[$username]['organizersChecked']++;
        }
        if (isset($joinRequest[$eventType])) {
            $byUsername[$username]['joinRequests']++;
        }
        if (isset($issuesAddressed[$eventType])) {
            $byUsername[$username]['issuesAddressed']++;
        }
    }

    $rows = array_values($byUsername);
    usort(
        $rows,
        static fn (array $a, array $b): int => $b['total'] <=> $a['total'] ?: strcmp($a['username'], $b['username'])
    );

    return $rows;
}

/**
 * @return array<int, array{id: int, eventType: string, timestamp: string, content: string, label: string}>
 */
function lanlistFetchRecentModeratorActionsForUsername(string $username, int $limit = 8): array
{
    $logs = lanlistFetchModeratorActionLogsSince(lanlistModeratorActionEventTypes(), 90);
    $moderators = [['id' => 0, 'username' => $username]];
    $actions = [];

    foreach ($logs as $log) {
        if (lanlistLogContentAttributesToModerator((string) $log['content'], $moderators) !== $username) {
            continue;
        }

        $actions[] = [
            'id' => (int) $log['id'],
            'eventType' => (string) $log['eventType'],
            'timestamp' => (string) $log['timestamp'],
            'content' => (string) $log['content'],
            'label' => lanlistModeratorActionLabel((string) $log['eventType']),
        ];

        if (count($actions) >= $limit) {
            break;
        }
    }

    return $actions;
}

function lanlistModeratorActionLabel(string $eventType): string
{
    return match ($eventType) {
        'EDIT_EVENT' => 'Event updated',
        'EDIT_ORGANIZER' => 'Organizer updated',
        'MODERATION_NO_EVENTS' => 'Organizer checked (no events)',
        'MODERATION_MARK_STALE' => 'Organizer marked stale',
        'ORGANIZER_LAST_CHECKED' => 'Organizer checked',
        'EVENT_TICKETS_NOT_RELEASED' => 'Ticket warning silenced',
        'INLINE_EDIT_ORGANIZER' => 'Quick organizer edit',
        'INLINE_PUBLISH_ORGANIZER' => 'Organizer publish toggled',
        'JOIN_REQUEST_APPROVED' => 'Join request approved',
        'JOIN_REQUEST_DENIED' => 'Join request denied',
        'TOGGLE_EVENT_PUBLISHED' => 'Event publish toggled',
        'CREATE_VENUE' => 'Venue created',
        'STAFF_EMAIL_SENT' => 'Organizer contacted',
        default => $eventType,
    };
}

function lanlistFetchHistoricalIssueCount(int $daysAgo): ?int
{
    global $db;

    $daysAgo = max(1, $daysAgo);
    $sql = 'SELECT metadata FROM async_jobs
            WHERE job_type = :job_type
            AND status = \'completed\'
            AND finished_at IS NOT NULL
            AND finished_at <= NOW() - INTERVAL ' . $daysAgo . ' DAY
            ORDER BY finished_at DESC
            LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':job_type', LANLIST_JOB_TYPE_ADMIN_NEWSLETTER);
    $stmt->execute();
    $row = $stmt->fetchRow();

    if ($row === false || empty($row['metadata'])) {
        return null;
    }

    $meta = lanlistDecodeAsyncJobMetadata($row['metadata']);

    return isset($meta['updateCount']) ? (int) $meta['updateCount'] : null;
}

/**
 * @param array{
 *     eventsWithIssues?: array<int, array<string, mixed>>,
 *     unpublishedOrganizers?: array<int, array<string, mixed>>,
 *     organizersWithNoEvents?: array<int, array<string, mixed>>,
 * }|null $panel
 * @return array{
 *     currentIssueCount: int,
 *     issueCount7DaysAgo: int|null,
 *     issueCount30DaysAgo: int|null,
 *     delta7Days: int|null,
 *     delta30Days: int|null,
 *     trend7Days: string|null,
 *     trend30Days: string|null,
 * }
 */
function lanlistFetchSiteHealthTrendMetrics(?array $panel = null): array
{
    if ($panel === null) {
        $panel = lanlistFetchModeratorPanelData();
    }

    $current = lanlistModeratorPanelIssueCount($panel);
    $count7 = lanlistFetchHistoricalIssueCount(7);
    $count30 = lanlistFetchHistoricalIssueCount(30);

    return [
        'currentIssueCount' => $current,
        'issueCount7DaysAgo' => $count7,
        'issueCount30DaysAgo' => $count30,
        'delta7Days' => $count7 === null ? null : $current - $count7,
        'delta30Days' => $count30 === null ? null : $current - $count30,
        'delta7DaysLabel' => lanlistFormatIssueCountDelta($count7, $current),
        'delta30DaysLabel' => lanlistFormatIssueCountDelta($count30, $current),
        'trend7Days' => lanlistFormatIssueCountTrend($count7, $current),
        'trend30Days' => lanlistFormatIssueCountTrend($count30, $current),
    ];
}

function lanlistFormatIssueCountTrend(?int $previous, int $current): ?string
{
    if ($previous === null) {
        return null;
    }

    $delta = $current - $previous;

    if ($delta === 0) {
        return 'unchanged';
    }

    if ($delta < 0) {
        return 'improved';
    }

    return 'increased';
}

function lanlistFormatIssueCountDelta(?int $previous, int $current): ?string
{
    if ($previous === null) {
        return null;
    }

    $delta = $current - $previous;
    if ($delta === 0) {
        return 'unchanged';
    }

    $abs = abs($delta);
    $word = $abs === 1 ? 'issue' : 'issues';

    if ($delta < 0) {
        return 'down ' . $abs . ' ' . $word;
    }

    return 'up ' . $abs . ' ' . $word;
}

/**
 * @param array{
 *     eventsWithIssues?: array<int, array<string, mixed>>,
 *     unpublishedOrganizers?: array<int, array<string, mixed>>,
 *     organizersWithNoEvents?: array<int, array<string, mixed>>,
 * }|null $panel
 * @return array<string, mixed>
 */
function lanlistFetchModeratorImpactMetrics(?int $userId = null, ?string $username = null, ?array $panel = null): array
{
    $teamDays = 30;
    $personalDays = 30;
    $health = lanlistFetchSiteHealthTrendMetrics($panel);
    $teamActivity = lanlistFetchTeamModeratorActivity($teamDays);
    $activeModerators = 0;

    foreach ($teamActivity as $row) {
        if ($row['total'] > 0) {
            $activeModerators++;
        }
    }

    $metrics = [
        'generatedAt' => date('Y-m-d H:i'),
        'siteHealth' => $health,
        'team' => [
            'days' => $teamDays,
            'activeModerators' => $activeModerators,
            'totalModerators' => count(lanlistFetchModeratorUsers()),
            'totalActions' => lanlistCountTeamModeratorActions($teamDays, lanlistModeratorActionEventTypes()),
            'organizersChecked' => lanlistCountTeamModeratorActions($teamDays, lanlistModeratorOrganizerCheckedEventTypes()),
            'joinRequestsHandled' => lanlistCountTeamModeratorActions($teamDays, lanlistModeratorJoinRequestEventTypes()),
            'issuesAddressed' => lanlistCountTeamModeratorActions($teamDays, lanlistModeratorIssueResolutionEventTypes()),
            'byModerator' => $teamActivity,
        ],
        'personal' => null,
    ];

    if ($username !== null && $username !== '') {
        $metrics['personal'] = [
            'username' => $username,
            'userId' => $userId,
            'days' => $personalDays,
            'totalActions' => lanlistCountModeratorActionsForUsername($username, $personalDays),
            'organizersChecked' => lanlistCountModeratorActionsForUsername(
                $username,
                $personalDays,
                lanlistModeratorOrganizerCheckedEventTypes()
            ),
            'joinRequestsHandled' => lanlistCountModeratorActionsForUsername(
                $username,
                $personalDays,
                lanlistModeratorJoinRequestEventTypes()
            ),
            'issuesAddressed' => lanlistCountModeratorActionsForUsername(
                $username,
                $personalDays,
                lanlistModeratorIssueResolutionEventTypes()
            ),
            'recentActions' => lanlistFetchRecentModeratorActionsForUsername($username),
        ];
    }

    return $metrics;
}

/**
 * @param array<string, mixed> $metrics
 */
function lanlistFormatModeratorImpactSummaryText(array $metrics): string
{
    $health = $metrics['siteHealth'];
    $team = $metrics['team'];
    $lines = [];

    $lines[] = 'Site health as of ' . $metrics['generatedAt'] . ': '
        . $health['currentIssueCount'] . ' open issue(s).';

    if ($health['issueCount7DaysAgo'] !== null) {
        $lines[] = 'Compared to 7 days ago: '
            . (lanlistFormatIssueCountDelta($health['issueCount7DaysAgo'], $health['currentIssueCount']) ?? 'unchanged') . '.';
    }

    if ($health['issueCount30DaysAgo'] !== null) {
        $lines[] = 'Compared to 30 days ago: '
            . (lanlistFormatIssueCountDelta($health['issueCount30DaysAgo'], $health['currentIssueCount']) ?? 'unchanged') . '.';
    }

    $lines[] = 'Team totals (last ' . $team['days'] . ' days): '
        . $team['organizersChecked'] . ' organizer check(s), '
        . $team['issuesAddressed'] . ' issue fix(es), '
        . $team['joinRequestsHandled'] . ' join request(s) handled, '
        . $team['activeModerators'] . ' active moderator(s).';

    $personal = $metrics['personal'] ?? null;
    if (is_array($personal)) {
        $lines[] = 'Your contributions (last ' . $personal['days'] . ' days): '
            . $personal['totalActions'] . ' action(s), including '
            . $personal['organizersChecked'] . ' organizer check(s) and '
            . $personal['issuesAddressed'] . ' issue fix(es).';
    }

    return implode("\n", $lines);
}

/**
 * @param array<string, mixed> $metrics
 */
function lanlistFormatModeratorImpactSummaryHtml(array $metrics): string
{
    global $tpl;

    $tpl->assign('moderatorImpact', $metrics);

    return $tpl->fetch('moderatorImpactSummary.tpl');
}
