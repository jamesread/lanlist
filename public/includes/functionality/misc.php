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

function sendEmail($recipient, $content, $subject = 'Notification', $includeStandardFooter = true)
{
    $subject = SITE_TITLE . ' - ' . $subject;

    if (empty($content)) {
        throw new Exception('Cannot send a blank email');
    }

    $content = wordwrap($content);

    if ($includeStandardFooter) {
        $content .= "\n\n- " . SITE_TITLE;
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
    Logger::messageDebug('Sending email to ' . $recipient . ', subject: ' . $subject, 'SEND_EMAIL');

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

    return $event;
}

function getOrganizerLogoUrl($organizerId)
{
    $organizerId = intval($organizerId);
    $baseUrl = 'resources/images/organizer-logos/';

    return $baseUrl . $organizerId . '.jpg';
}

function floatToMoney($value, $currency = '�')
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

function getListOfNextEvents($count = 10)
{
    global $db;

    $count = intval($count);

    $sql = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, v.country, o.id AS organizerId FROM events e LEFT JOIN venues v ON e.venue = v.id LEFT JOIN organizers o ON e.organizer = o.id WHERE e.published = 1 AND e.dateFinish > now() ORDER BY dateStart ASC LIMIT ' . $count;

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
        $sql = 'SELECT e.id, e.title, e.dateStart FROM events e WHERE e.published = 1 AND e.dateStart > now() ORDER BY dateStart ASC limit 1';
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

function logMessageToDatabase($priority, $content, $eventId)
{
    global $db;

    $stmtLog = $db->prepare('INSERT INTO logs (priority, content, eventType, timestamp) VALUES (:priority, :content, :eventType, now()) ');
    $stmtLog->bindValue(':priority', $priority);
    $stmtLog->bindValue(':content', $content);
    $stmtLog->bindValue(':eventType', $eventId);
    $stmtLog->execute();
}

function requirePriv($ident)
{
    if (Session::isLoggedIn()) {
        if (!Session::getUser()->hasPriv($ident)) {
            throw new \libAllure\exceptions\SimpleFatalError('You dont have the ' . $ident . ' permission.');
        }
    } else {
        throw new \libAllure\exceptions\SimpleFatalError('You are not logged in.');
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

    echo '<h1>Redirecting: '  . $reason .  '</h1>';
    echo '<p style = "text-align: center">You are being redirected to <a href = "' . $url . '">here</a>.</p>';

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

    $sql = 'SELECT e.id, o.id AS organizerId, o.title AS organizerTitle, e.numberOfSeats, e.title AS eventTitle, v.lat as venueLat, v.lng as venueLng, e.dateStart, e.dateFinish, o.useFavicon FROM events e LEFT JOIN (venues v) ON e.venue = v.id LEFT JOIN (organizers o) ON e.organizer = o.id WHERE e.published = 1 AND e.dateFinish > now() ORDER BY e.dateStart DESC';
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
        null => 'Unknown',
        0 => 'Not at venue',
        1 => 'Available at venue',
        2 => 'Included in private rooms',
    ];
}

function dataSmoking() 
{
    return [
        null => 'Unknown',
        0 => 'Outside venue',
        1 => 'Smoking area in venue',
    ];
}

function dataAlcohol() {
    return [
        null => 'Unknown',
        0 => 'Not allowed at the event',
        1 => 'Bring your own alcohol',
        2 => 'Bar at the venue',
        3 => 'Bar at the venue, and bring your own alcohol',
    ];
}

function dataSleeping() {
    return [
        null => 'Unknown',
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

function getGeoIpCountry() {
    $default = 'United Kingdom';
    $country = $default;

    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

        $country = geoip_country_name_by_name($ip);
    }

    if (empty($country)) {
        return $default;
    }

    return $country;
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

