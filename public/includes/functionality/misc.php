<?php

use libAllure\Session;
use libAllure\DatabaseFactory;
use libAllure\ErrorHandler;
use libAllure\ElementAutoSelect;
use libAllure\Logger;

function getCountJoinRequests()
{
    $sql = 'SELECT count(j.id) as count FROM organization_join_requests j';
    $stmt = DatabaseFactory::getInstance()->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetchRowNotNull();
    $countJoinRequests = $row['count'];

    return $countJoinRequests;
}

function htmlify($input)
{
    if ($input == null) {
        return '';
    }

    return nl2br(htmlentities(stripslashes($input)));
}

/**
 * Adds steamGroupHref / discordInviteHref for templates when platform URLs are set.
 *
 * @param array<string, mixed> $row
 */
function applyOrganizerPlatformInviteHrefs(array &$row): void
{
    $row['steamGroupHref'] = '';
    $row['discordInviteHref'] = '';

    if (!empty($row['steamGroupUrl'])) {
        $href = trim((string) $row['steamGroupUrl']);
        if (strpos($href, 'http://') !== 0 && strpos($href, 'https://') !== 0) {
            $href = 'https://' . $href;
        }
        $row['steamGroupHref'] = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    }
    if (!empty($row['discordInviteUrl'])) {
        $href = trim((string) $row['discordInviteUrl']);
        if (strpos($href, 'http://') !== 0 && strpos($href, 'https://') !== 0) {
            $href = 'https://' . $href;
        }
        $row['discordInviteHref'] = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    }
}

function sendEmailToAdmins($content, $subject)
{
    return sendEmailToGroup(ADMIN_GID, $content, $subject);
}

function sendEmailToGroup($groupId, $content, $subject)
{
    global $db;

    $sql = 'SELECT id, username, email FROM users WHERE `group` = :group';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':group', intval($groupId));
    $stmt->execute();

    foreach ($stmt->fetchAll() as $user) {
        sendEmail($user['email'], $content, $subject);
    }
}

function lanlistModeratorNewsletterFrequencyOptions(): array
{
    return [
        'daily' => 'Daily',
        'fridays_only' => 'Fridays only',
        'never' => 'Never',
    ];
}

function lanlistOrganizerUpdateEmailOptions(): array
{
    return [
        'always' => 'Always',
        'never' => 'Never',
    ];
}

function lanlistEventUpdateEmailOptions(): array
{
    return [
        'always' => 'Always',
        'never' => 'Never',
    ];
}

/**
 * @param array<string, mixed> $before
 * @param array<string, mixed> $after
 */
function lanlistFormatUserProfileChangeSummary(array $before, array $after): string
{
    $changes = [];

    $stringFields = [
        'email' => 'email',
        'usernameSteam' => 'Steam username',
        'discordUser' => 'Discord user ID',
    ];

    foreach ($stringFields as $field => $label) {
        if (!array_key_exists($field, $after)) {
            continue;
        }

        $old = trim((string) ($before[$field] ?? ''));
        $new = trim((string) ($after[$field] ?? ''));
        if ($old === $new) {
            continue;
        }

        if ($new === '') {
            $changes[] = $label . ' cleared';
        } elseif ($old === '') {
            $changes[] = $label . ' set';
        } else {
            $changes[] = $label . ' changed';
        }
    }

    $optionFields = [
        'moderatorNewsletterFrequency' => [
            'label' => 'moderator newsletter',
            'options' => lanlistModeratorNewsletterFrequencyOptions(),
        ],
        'organizerUpdateEmails' => [
            'label' => 'organizer update emails',
            'options' => lanlistOrganizerUpdateEmailOptions(),
        ],
        'eventUpdateEmails' => [
            'label' => 'event update emails',
            'options' => lanlistEventUpdateEmailOptions(),
        ],
    ];

    foreach ($optionFields as $field => $config) {
        if (!array_key_exists($field, $after)) {
            continue;
        }

        $old = (string) ($before[$field] ?? '');
        $new = (string) ($after[$field] ?? '');
        if ($old === $new) {
            continue;
        }

        $oldLabel = $config['options'][$old] ?? $old;
        $newLabel = $config['options'][$new] ?? $new;
        $changes[] = $config['label'] . ' ' . $oldLabel . ' ? ' . $newLabel;
    }

    return implode('; ', $changes);
}

function lanlistUserReceivesOrganizerUpdateEmails(array $user): bool
{
    return ($user['organizerUpdateEmails'] ?? 'always') !== 'never';
}

function lanlistUserReceivesEventUpdateEmails(array $user): bool
{
    return ($user['eventUpdateEmails'] ?? 'always') !== 'never';
}

function lanlistUserReceivesModeratorNewsletterToday(array $user): bool
{
    $frequency = $user['moderatorNewsletterFrequency'] ?? 'daily';

    if ($frequency === 'never') {
        return false;
    }

    if ($frequency === 'fridays_only') {
        return (int) date('N') === 5;
    }

    return true;
}

/**
 * @return int number of emails sent
 */
function sendModeratorNewsletter($content, $subject): int
{
    global $db;

    $sql = 'SELECT id, username, email, moderatorNewsletterFrequency FROM users WHERE `group` = :moderator_gid OR `group` = :admin_gid';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':moderator_gid', MODERATOR_GID);
    $stmt->bindValue(':admin_gid', ADMIN_GID);
    $stmt->execute();

    $sentCount = 0;

    $recipients = [];

    foreach ($stmt->fetchAll() as $user) {
        if (!lanlistUserReceivesModeratorNewsletterToday($user)) {
            continue;
        }

        $recipients[] = $user;
    }

    if (PHP_SAPI === 'cli') {
        if ($recipients === []) {
            fwrite(STDOUT, "newsletter recipients: none\n");
        } else {
            foreach ($recipients as $user) {
                fwrite(STDOUT, 'newsletter recipient username=' . $user['username'] . ' email=' . $user['email'] . "\n");
            }
        }
    }

    foreach ($recipients as $user) {
        sendEmail($user['email'], $content, $subject);
        $sentCount++;
    }

    return $sentCount;
}

function lanlistStandardEmailFooterHtml(): string
{
    $profileUrl = htmlspecialchars(SITE_BASE_URL . '/formHandler.php?formClazz=FormEditUser', ENT_QUOTES, 'UTF-8');
    $discordUrl = htmlspecialchars('https://discord.gg/jhYWWpNJ3v', ENT_QUOTES, 'UTF-8');
    $siteTitle = htmlspecialchars(SITE_TITLE, ENT_QUOTES, 'UTF-8');

    return '<p><small>'
        . '— ' . $siteTitle . '<br />'
        . 'Manage email settings in <a href="' . $profileUrl . '">your profile</a>.<br />'
        . 'Questions? Join the <a href="' . $discordUrl . '">TechnoWax Discord</a> server (#lanlist channel).'
        . '</small></p>';
}

function sendEmail($recipient, $content, $subject = 'Notification', $includeStandardFooter = true)
{
    $subject = SITE_TITLE . ' - ' . $subject;

    if (empty($content)) {
        throw new Exception('Cannot send a blank email');
    }

	if (empty($recipient) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
		Logger::messageWarning('Not sending email, invalid recipient: ' . $recipient, 'SEND_EMAIL_INVALID');
		return;
	}

    $content = wordwrap($content);

    if ($includeStandardFooter) {
        $content .= lanlistStandardEmailFooterHtml();
    }

//    ErrorHandler::getInstance()->beLazy();

    if (SEND_EMAIL) {
        require_once 'Mail.php';
        require_once 'Mail/smtp.php';

        $smtp = new Mail_smtp([
            'host' => SMTP_HOST,
            'port' => SMTP_PORT,
            'auth' => true,
            'username' => SMTP_USER,
            'password' => SMTP_PASS,
        ]);

        $headers = array(
            'From' => '"' . SITE_TITLE . '" <' . EMAIL_ADDRESS . '>',
            'To' => '<' . $recipient . '>',
            'Reply-To' => '"' . EMAIL_REPLY_TO_NAME . '" <' . EMAIL_REPLY_TO_ADDRESS . '>',
            'Subject' => $subject,
            'Content-Type' => 'text/html'
        );

        $smtpResult = $smtp->send('<' . $recipient . '>', $headers, $content);

        if (is_object($smtpResult) && get_class($smtpResult) == 'PEAR_Error') {
            Logger::messageWarning('Email error ' . $smtpResult->message, 'SEND_EMAIL_ERROR');
        }
    }

//    ErrorHandler::getInstance()->beGreedy();
    $logMeta = null;
    $recipientUserStmt = DatabaseFactory::getInstance()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $recipientUserStmt->bindValue(':email', $recipient);
    $recipientUserStmt->execute();
    $recipientUserRow = $recipientUserStmt->fetchRow();
    if ($recipientUserRow !== false) {
        $recipientUserId = (int) $recipientUserRow['id'];
        if ($recipientUserId > 0) {
            $logMeta = ['relatedUser' => $recipientUserId];
        }
    }

    Logger::messageDebug('Sending email to ' . $recipient . ', subject: ' . $subject, 'SEND_EMAIL', $logMeta);

    $sql = 'INSERT INTO email_log (subject, emailAddress, sent) VALUES (:subject, :emailAddress, now())';
    $stmt = DatabaseFactory::getInstance()->prepare($sql);
    $stmt->bindValue(':emailAddress', $recipient);
    $stmt->bindValue(':subject', $subject);
    $stmt->execute();
}

function normalizeEvents($events)
{
    foreach ($events as $k => $event) {
        $events[$k] = normalizeEvent($event);
    }

    return $events;
}

function normalizeEvent($event)
{
    $dateStart = date_create($event['dateStart']);
    $dateFinish = date_create($event['dateFinish']);

    $event['dateStartHuman'] = date_format($dateStart, 'D jS M Y');
    $event['dateFinishHuman'] = date_format($dateFinish, 'D jS M Y');
    $event['dayStartHuman'] = date_format($dateStart, 'D jS');
    $event['dayFinishHuman'] = date_format($dateFinish, 'D jS');
    $event['dateTag'] = date_format(date_create($event['dateStart']), 'M Y');
    $event['bannerUrl'] = getOrganizerLogoUrl($event['organizerId']);
    $event['networkMbpsHuman'] = formatConnectionMbpsHuman($event['networkMbps'] ?? null);
    $event['internetMbpsHuman'] = formatConnectionMbpsHuman($event['internetMbps'] ?? null);

    return $event;
}

/** Human-readable Mbps for event pages; DB stays integer. Returns null when unknown/not set. */
function formatConnectionMbpsHuman($mbps): ?string
{
    if ($mbps === null || $mbps === '') {
        return null;
    }

    $n = (int) $mbps;

    if ($n === 0) {
        return 'None';
    }

    if ($n === 1000) {
        return 'Gigabit';
    }

    if ($n === 10000) {
        return '10-Gig';
    }

    return $n . ' Mbps';
}

function getOrganizerLogoUrl($organizerId)
{
    $organizerId = intval($organizerId);
    $baseUrl = 'resources/images/organizer-logos/';

    return $baseUrl . $organizerId . '.jpg';
}

function floatToMoney($value, $currency = '£')
{
    if (empty($value) || $value == 0) {
        return '?';
    } else {
        if ($value % 10 != 0) {
            $value = number_format($value, 2);
        }
    }

    switch ($currency) {
        case '':
        case 'GBP': return '<span class = "currency">&pound;' . $value . '</span>';
        case 'SEK':
        default:
            return $value . ' ' . $currency;
    }
}

function issetor(&$v, $default = 'Unknown')
{
    return empty($v) ? $default : $v;
}

function tplBoolToString($arguments, $smarty)
{
    if (!isset($arguments['test'])) {
        $smarty->trigger_error('The test argument is required.');
    }

    $onTrue = isset($arguments['onTrue']) ? $arguments['onTrue'] : 'Yes';
    $onFalse = isset($arguments['onFalse']) ? $arguments['onFalse'] : 'No';
    $onNull = isset($arguments['onNull']) ? $arguments['onNull'] : 'Unknown';

    return boolToString($arguments['test'], $onTrue, $onFalse, $onNull);
}

function boolToString($test, $onTrue = 'Yes', $onFalse = 'No', $onNull = 'Unknown')
{
    if ($test == null || strlen($test) == 0) {
        return $onNull;
    }

    if ($test) {
        return $onTrue;
    } else {
        return $onFalse;
    }
}

function getCountUnreadLogs()
{
    global $db;

    if (!Session::isLoggedIn()) {
        return 0;
    }

    $sql = 'SELECT count(l.id) AS count FROM logs l WHERE l.isread = 0';
    $thing = $db->query($sql)->fetchRow();

    if (empty($thing['count'])) {
        return 0;
    } else {
        return $thing['count'];
    }
}

function getCountryFlagHtml(string $country): string
{
    switch ($country) {
        // https://symbl.cc/en/emoji/flags/country-flag/
        case 'United Kingdom':
            return '&#127468;&#127463;';
        case 'Sweden':
            return '&#127480;&#127466;';
        case 'Switzerland':
            return '&#127464;&#127469;';
        case 'Netherlands':
            return '&#127475;&#127473;';
        case 'Norway':
            return '&#127475;&#127476;';
        case 'Germany':
            return '&#127465;&#127466;';
        case 'Italy':
            return '&#127470;&#127481;';
        case 'Ireland':
            return '&#127470;&#127466;';
        case 'Iceland':
            return '&#127470;&#127480;';
        case 'Poland':
            return '&#127477;&#127473;';
        case 'United States':
            return '&#127482;&#127480;';
        case 'Canada':
            return '&#127464;&#127462;';
        case 'Denmark':
            return '&#127465;&#127472;';
        case 'Finland':
            return '&#127467;&#127470;';
        case 'France':
            return '&#127467;&#127479;';
        case 'Austria':
            return '&#127462;&#127481;';
        case 'Australia':
            return '&#127462;&#127482;';
        case 'Belgium':
            return '&#127463;&#127466;';
        case 'Spain':
            return '&#127466;&#127480;';
        case 'Turkey':
            return '&#127481;&#127479;';
        default:
            return '';
    }
}

function echoCountryFlagOrName(string $country, bool $linkFlag = false): void
{
    $flag = getCountryFlagHtml($country);
    if ($flag !== '') {
        if ($linkFlag) {
            echo '<a href = "eventsList.php?mode=country&amp;country=';
            echo urlencode($country);
            echo '" title = "Upcoming events in ';
            echo htmlspecialchars($country, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            echo '">';
            echo $flag;
            echo '</a>';
            return;
        }

        echo $flag;
        return;
    }

    echo htmlspecialchars($country, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function getCountriesWithUpcomingEventCounts(): array
{
    global $db;

    $sql = 'SELECT v.country, COUNT(e.id) AS eventCount
        FROM events e
        INNER JOIN venues v ON e.venue = v.id
        INNER JOIN organizers o ON e.organizer = o.id
        WHERE e.published = 1
            AND e.dateStart > NOW()
            AND v.country IS NOT NULL
            AND v.country != \'\'' . lanlistSqlPublicOrganizerVisible('o') . '
        GROUP BY v.country
        HAVING eventCount > 0
        ORDER BY v.country ASC';

    return $db->query($sql)->fetchAll();
}

/**
 * Countries with any published public events. eventCount is upcoming only.
 *
 * @return array<int, array{country: string, eventCount: int, totalEventCount: int}>
 */
function getCountriesWithPublishedEventCounts(): array
{
    global $db;

    $sql = 'SELECT v.country,
            SUM(CASE WHEN e.dateStart > NOW() THEN 1 ELSE 0 END) AS eventCount,
            COUNT(e.id) AS totalEventCount
        FROM events e
        INNER JOIN venues v ON e.venue = v.id
        INNER JOIN organizers o ON e.organizer = o.id
        WHERE e.published = 1
            AND v.country IS NOT NULL
            AND v.country != \'\'' . lanlistSqlPublicOrganizerVisible('o') . '
        GROUP BY v.country
        HAVING totalEventCount > 0
        ORDER BY v.country ASC';

    return $db->query($sql)->fetchAll();
}

/**
 * @return array{organizerCount: int, pastEventCount: int, upcomingEventCount: int}
 */
function fetchCountryEventStats(string $country): array
{
    global $db;

    $sql = 'SELECT
            COUNT(DISTINCT o.id) AS organizerCount,
            SUM(CASE WHEN e.dateStart <= NOW() THEN 1 ELSE 0 END) AS pastEventCount,
            SUM(CASE WHEN e.dateStart > NOW() THEN 1 ELSE 0 END) AS upcomingEventCount
        FROM events e
        INNER JOIN venues v ON e.venue = v.id
        INNER JOIN organizers o ON e.organizer = o.id
        WHERE e.published = 1
            AND v.country = :country' . lanlistSqlPublicOrganizerVisible('o');

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':country', $country);
    $stmt->execute();
    $row = $stmt->fetch() ?: [];

    return [
        'organizerCount' => (int) ($row['organizerCount'] ?? 0),
        'pastEventCount' => (int) ($row['pastEventCount'] ?? 0),
        'upcomingEventCount' => (int) ($row['upcomingEventCount'] ?? 0),
    ];
}

function getListOfNextEvents($count = 10)
{
    global $db;

    $count = intval($count);

    $sql = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, v.country, o.id AS organizerId, o.title AS organizerTitle, o.useFavicon FROM events e LEFT JOIN venues v ON e.venue = v.id LEFT JOIN organizers o ON e.organizer = o.id WHERE e.published = 1 AND e.dateFinish > now()' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY dateStart ASC LIMIT ' . $count;

    $events = $db->query($sql)->fetchAll();
    $events = normalizeEvents($events);

    $eventsByMonth = [];

    foreach ($events as $event) {
        $tag = $event['dateTag'];

        if (!isset($eventsByMonth[$tag])) {
            $eventsByMonth[$tag] = [];
        }

        $eventsByMonth[$tag][] = $event;
    }

    return $eventsByMonth;
}

function getNextEvent($organizerId = null)
{
    global $db;

    if (empty($organizerId)) {
        $sql = 'SELECT e.id, e.title, e.dateStart FROM events e INNER JOIN organizers o ON e.organizer = o.id WHERE e.published = 1 AND e.dateStart > now()' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY dateStart ASC limit 1';
        $result = $db->query($sql);

        return $result->fetchRow();
    } else {
        $sql = 'SELECT e.id, e.title FROM events e WHERE e.organizer = :organizer AND e.published = 1 AND e.dateStart > now() ORDER BY e.dateStart ASC LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':organizer', $organizerId);
        $stmt->execute();

        return $stmt->fetchRow();
    }
}

function getEventRating($eventId)
{
    global $db;

    $sql = 'SELECT ((r.rat_venue + r.rat_vfm + r.rat_activities) / 3) AS avg FROM event_reviews r  WHERE r.event = :eventId';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':eventId', $eventId);
    $stmt->execute();

    $allRatings = $stmt->fetchAll();
    $allRatings = array_flatten($allRatings);

    if (count($allRatings) === 0) {
        return 0;
    } else {
        $average = (array_sum($allRatings) / count($allRatings));
    }

    return $average;
}

function lanlistUserIdByUsername(string $username): ?int
{
    global $db;

    $username = trim($username);
    if ($username === '') {
        return null;
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    $id = (int) $row['id'];

    return $id > 0 ? $id : null;
}

function logMessageToDatabase($priority, $content, $eventType, $metadata = null)
{
    global $db;

    $relatedUser = null;
    if (is_array($metadata) && array_key_exists('relatedUser', $metadata)) {
        $v = $metadata['relatedUser'];
        if ($v !== null && $v !== '') {
            $n = (int) $v;
            if ($n > 0) {
                $relatedUser = $n;
            }
        }
    }

    if ($relatedUser === null && Session::isLoggedIn()) {
        $actorId = (int) Session::getUser()->getId();
        if ($actorId > 0) {
            $loginLogoutEventTypes = ['USER_LOGIN', 'USER_LOGOUT'];
            if (
                $priority === 'AUDIT'
                || in_array((string) $eventType, $loginLogoutEventTypes, true)
            ) {
                $relatedUser = $actorId;
            }
        }
    }

    if ($relatedUser === null && (string) $eventType === 'LOGIN_FAILURE_PASSWORD') {
        if (preg_match('/^Failed login for (.+), password wrong\.$/', (string) $content, $matches) === 1) {
            $userId = lanlistUserIdByUsername($matches[1]);
            if ($userId !== null) {
                $relatedUser = $userId;
            }
        }
    }

    $relatedOrganizer = null;
    if (is_array($metadata) && array_key_exists('relatedOrganizer', $metadata)) {
        $v = $metadata['relatedOrganizer'];
        if ($v !== null && $v !== '') {
            $n = (int) $v;
            if ($n > 0) {
                $relatedOrganizer = $n;
            }
        }
    }

    $stmtLog = $db->prepare('INSERT INTO logs (priority, content, eventType, relatedUser, relatedOrganizer, timestamp) VALUES (:priority, :content, :eventType, :relatedUser, :relatedOrganizer, now()) ');
    $stmtLog->bindValue(':priority', $priority);
    $stmtLog->bindValue(':content', $content);
    $stmtLog->bindValue(':eventType', $eventType);
    if ($relatedUser === null) {
        $stmtLog->bindValue(':relatedUser', null, \PDO::PARAM_NULL);
    } else {
        $stmtLog->bindValue(':relatedUser', $relatedUser, \PDO::PARAM_INT);
    }
    if ($relatedOrganizer === null) {
        $stmtLog->bindValue(':relatedOrganizer', null, \PDO::PARAM_NULL);
    } else {
        $stmtLog->bindValue(':relatedOrganizer', $relatedOrganizer, \PDO::PARAM_INT);
    }
    $stmtLog->execute();
}

function lanlistIsValidLogEventType(string $eventType): bool
{
    return $eventType !== '' && preg_match('/^[A-Za-z0-9_]+$/', $eventType) === 1;
}

/**
 * @return list<string>
 */
function lanlistParseExcludedLogEventTypesFromRequest(): array
{
    if (!isset($_REQUEST['excludeEventType'])) {
        return [];
    }

    $raw = $_REQUEST['excludeEventType'];
    if (!is_array($raw)) {
        $raw = [$raw];
    }

    $excluded = [];
    foreach ($raw as $type) {
        $type = trim((string) $type);
        if (lanlistIsValidLogEventType($type)) {
            $excluded[$type] = true;
        }
    }

    ksort($excluded);

    return array_keys($excluded);
}

/**
 * @param list<string> $excludedEventTypes
 * @return array{sql: string, params: array<string, string>}
 */
function lanlistBuildLogListQuery(bool $fullView, array $excludedEventTypes): array
{
    $sql = 'SELECT l.id, l.eventType, l.timestamp, l.content, l.priority, l.relatedUser, l.relatedOrganizer, u.username AS relatedUsername, o.title AS relatedOrganizerTitle FROM logs l LEFT JOIN users u ON u.id = l.relatedUser LEFT JOIN organizers o ON o.id = l.relatedOrganizer';
    $params = [];
    $conditions = [];

    if (!$fullView) {
        $conditions[] = 'l.isread = 0';
    }

    if ($excludedEventTypes !== []) {
        $placeholders = [];
        foreach ($excludedEventTypes as $i => $type) {
            $key = ':excludeEventType' . $i;
            $placeholders[] = $key;
            $params[$key] = $type;
        }
        $conditions[] = 'l.eventType NOT IN (' . implode(', ', $placeholders) . ')';
    }

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY l.id DESC';
    if ($fullView) {
        $sql .= ' LIMIT 100';
    }

    return ['sql' => $sql, 'params' => $params];
}

/**
 * @return list<array<string, mixed>>
 */
function lanlistFetchAuditLogsForUser(int $userId, int $limit = 50): array
{
    global $db;

    if ($userId <= 0) {
        return [];
    }

    $limit = max(1, min($limit, 200));

    $sql = 'SELECT l.id, l.eventType, l.timestamp, l.content, l.relatedOrganizer, o.title AS relatedOrganizerTitle
        FROM logs l
        LEFT JOIN organizers o ON o.id = l.relatedOrganizer
        WHERE l.relatedUser = :userId AND l.priority = \'AUDIT\'
        ORDER BY l.id DESC
        LIMIT ' . $limit;

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * @param list<string> $excludedEventTypes
 */
function lanlistLogListUrl(bool $fullView, array $excludedEventTypes): string
{
    $params = [];
    if ($fullView) {
        $params['full'] = '1';
    }
    foreach ($excludedEventTypes as $type) {
        $params['excludeEventType'][] = $type;
    }

    $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

    return 'listLogs.php' . ($query !== '' ? '?' . $query : '');
}

/**
 * @param list<string> $excludedEventTypes
 */
function lanlistLogListUrlWithoutExcludedEventType(bool $fullView, array $excludedEventTypes, string $removeType): string
{
    $filtered = [];
    foreach ($excludedEventTypes as $type) {
        if ($type !== $removeType) {
            $filtered[] = $type;
        }
    }

    return lanlistLogListUrl($fullView, $filtered);
}

function requirePriv($ident)
{
    if (!Session::isLoggedIn()) {
        redirect('login.php', 'You need to login to access this part of the site.');
    }

    if (!Session::getUser()->hasPriv($ident)) {
        throw new \libAllure\exceptions\SimpleFatalError('You dont have the ' . $ident . ' permission.');
    }
}

function startSidebar()
{
    define('SIDEBAROUTPUT', 1);
    echo '</div></main><aside>';
}

function redirect($url, $reason)
{
    define('REDIRECT', $url);
    if (!in_array('includes/widgets/header.php', get_included_files())) {
        require_once 'includes/widgets/header.minimal.php';
    }

    echo '<h1>Redirecting: ' . htmlspecialchars((string)$reason, ENT_QUOTES, 'UTF-8') . '</h1>';
    echo '<p style = "text-align: center">You are being redirected to <a href = "' . htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8') . '">here</a>.</p>';

    require_once 'includes/widgets/footer.minimal.php';
}

/*
 * Flattening a multi-dimensional array into a
 * single-dimensional one. The resulting keys are a
 * string-separated list of the original keys:
 *
 * a[x][y][z] becomes a[implode(sep, array(x,y,z))]
 */
function array_flatten($array)
{
    $result = array();
    $stack = array();
    array_push($stack, array("", $array));

    while (count($stack) > 0) {
        list($prefix, $array) = array_pop($stack);

        foreach ($array as $key => $value) {
            $new_key = $prefix . strval($key);

            if (is_array($value)) {
                array_push($stack, array($new_key . '.', $value));
            } else {
                $result[$new_key] = $value;
            }
        }
    }

    return $result;
}

function jsForEvents()
{
    global $db;

    $sql = 'SELECT e.id, o.id AS organizerId, o.title AS organizerTitle, e.numberOfSeats, e.title AS eventTitle, v.lat as venueLat, v.lng as venueLng, e.dateStart, e.dateFinish, o.useFavicon FROM events e LEFT JOIN (venues v) ON e.venue = v.id LEFT JOIN (organizers o) ON e.organizer = o.id WHERE e.published = 1 AND e.dateFinish > now()' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY e.dateStart DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    $events = normalizeEvents($stmt->fetchAll());

    foreach ($events as $event) {
        $json = json_encode($event);

        echo "addMarkerEvent({$json});\n";
    }
}


function jsMapMarker($event, $focus = false)
{
    $focus = intval($focus);

    $event = json_encode($event);

    return "addMarkerEvent({$event}, {$focus});";
}

function addHistoryLink($url, $title)
{
    if (!Session::isLoggedIn()) {
        return;
    }

    if (!isset($_SESSION['history'])) {
        $_SESSION['history'] = array();
    }

    $_SESSION['history'] = array_slice($_SESSION['history'], -7, 7);

    $_SESSION['history'][] = array(
        "url" => $url,
        "title" => $title
    );
}

function dataShowers() 
{
    return [
        '' => 'Unknown',
        0 => 'Not at venue',
        1 => 'Available at venue',
        2 => 'Included in private rooms',
    ];
}

function dataSmoking() 
{
    return [
        '' => 'Unknown',
        0 => 'Outside venue',
        1 => 'Smoking area in venue',
    ];
}

function dataAlcohol() {
    return [
        '' => 'Unknown',
        0 => 'Not allowed at the event',
        1 => 'Bring your own alcohol',
        2 => 'Bar at the venue',
        3 => 'Bar at the venue, and bring your own alcohol',
    ];
}

function dataSleeping() {
    return [
        '' => 'Unknown',
        0 => 'Not arranged by organizer',
        1 => 'Not an overnight Event',
        2 => 'Private rooms at venue',
        3 => 'Indoors at venue',
        4 => 'Indoors and camping at venue',
        5 => 'Indoors, camping and private rooms at venue',
        6 => 'Indoors at venue. Camping and hotels nearby',
    ];
}

function lookupField($key, $type) {
    // PHP 8.1+ deprecates null array offsets; treat null as the "Unknown" key.
    if ($key === null) {
        $key = '';
    }

    switch ($type) {
    case 'sleeping': return dataSleeping()[$key];
    case 'showers': return dataShowers()[$key];
    case 'alcohol': return dataAlcohol()[$key];
    case 'smoking': return dataSmoking()[$key];
    }

    return 'Unknown field type: ' . $type;
    var_dump($a, $b); exit;
}

function outputJson($v) {
    header('Content-Type: application/json');

    echo json_encode($v);
}

function getGeoIpCountry(): string
{
    $default = 'United Kingdom';
    $ip = null;

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = (string) $_SERVER['REMOTE_ADDR'];
    }

    if ($ip !== null && $ip !== '' && function_exists('geoip_country_name_by_name')) {
        $resolved = geoip_country_name_by_name($ip);
        if (!empty($resolved)) {
            return (string) $resolved;
        }
    }

    return $default;
}

/**
 * GeoIP-estimated country when that country has upcoming events and a supported flag emoji.
 */
function getGeoIpCountryWithUpcomingEvents(): ?string
{
    $geoCountry = getGeoIpCountry();

    if (getCountryFlagHtml($geoCountry) === '') {
        return null;
    }

    foreach (getCountriesWithUpcomingEventCounts() as $row) {
        if ((string) ($row['country'] ?? '') === $geoCountry && (int) ($row['eventCount'] ?? 0) > 0) {
            return $geoCountry;
        }
    }

    return null;
}

function canEditEvent($eventOrganizerId) {
    if (!Session::isLoggedIn()) {
        return false;
    }

    if (Session::getUser()->hasPriv('MODERATE_EVENTS')) {
        return true;
    }

    if (Session::getUser()->getData('organization') == $eventOrganizerId) {
        return true;
    }

    if (empty($eventOrganizerId)) {
        return false;
    }

    return false;
}

function getElementCurrency($val)
{
	$el = new ElementAutoSelect('currency', 'Currency', $val, 'GBP, USD, EUR, etc');
	$el->addOption('GBP (&pound; - UK, etc)', 'GBP');
	$el->addOption('USD ($ - America, etc)', 'USD');
	$el->addOption('AUD ($ - Austrialia, etc)', 'AUD');
	$el->addOption('SEK (Sweden)', 'SEK');
	$el->addOption('ISK (Iceland)', 'ISK');
	$el->addOption('EUR (&euro; - Europe, etc)', 'EUR');
	$el->addOption('CHF (Swiss franc)', 'CHF');

	return $el;
}

