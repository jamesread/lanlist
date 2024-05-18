<?php

use \libAllure\Session;
use \libAllure\DatabaseFactory;
use \libAllure\ErrorHandler;
use \libAllure\Logger;

function getCountJoinRequests() {
    $sql = 'SELECT count(j.id) as count FROM organization_join_requests j';
    $stmt = DatabaseFactory::getInstance()->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetchRowNotNull();
    $countJoinRequests = $row['count'];

    return $countJoinRequests;
}

function htmlify($input) {
    if ($input == null) {
        return '';
    }

    return nl2br(htmlentities(stripslashes($input)));
}

function sendEmailToAdmins($content, $subject) {
    return sendEmailToGroup(99, $content, $subject);
}

function sendEmailToGroup($groupId, $content, $subject) {
    global $db;

    $sql = 'SELECT id, username, email FROM users WHERE `group` = :group';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':group', intval($groupId));
    $stmt->execute();

    foreach ($stmt->fetchAll() as $user) {
        sendEmail($user['email'], $content, $subject);
    }	
}

function sendEmail($recipient, $content, $subject = 'Notification', $includeStandardFooter = true) {
    $subject = SITE_TITLE . ' - ' . $subject;

    if (empty($content)) {
        throw new Exception('Cannot send a blank email');
    }

    $content = wordwrap($content);

    if ($includeStandardFooter) {
        $content .= "\n\n- " . SITE_TITLE;
    }

    ErrorHandler::getInstance()->beLazy();

    if (SEND_EMAIL) {
        require_once 'Mail.php';
        require_once 'Mail/smtp.php';

        $smtp = new Mail_smtp([
            'host' => SMTP_HOST,
            'port' => SMTP_PORT,
            'auth' => true,
            'username' => SMTP_USERNAME,
            'password' => SMTP_PASSWORD,
        ]);

        $headers = array(
            'From' => '"' . SITE_TITLE . '" <' . EMAIL_ADDRESS . '>',
            'To' => '<' . $recipient . '>',
            'Subject' => $subject,
            'Content-Type' => 'text/html'
        );

        $smtp->send('<' . $recipient . '>', $headers, $content);
    }

    ErrorHandler::getInstance()->beGreedy();
    Logger::messageDebug('Sending email to ' . $recipient . ', subject: ' . $subject);

    $sql = 'INSERT INTO email_log (subject, emailAddress, sent) VALUES (:subject, :emailAddress, now())';
    $stmt = DatabaseFactory::getInstance()->prepare($sql);
    $stmt->bindValue(':emailAddress', $recipient);
    $stmt->bindValue(':subject', $subject);
    $stmt->execute();
}

function normalizeEvents($events) {
    foreach ($events as $k => $event) {
        $events[$k]['dateStartHuman'] = date_format(date_create($event['dateStart']), 'D jS M Y g:ia');
    }

    return $events;
}

function getOrganizerLogoUrl($organizerId) {
    $organizerId = intval($organizerId);
    $baseUrl = 'resources/images/organizer-logos/';

    return $baseUrl . (file_exists($baseUrl . $organizerId . '.jpg') ? $organizerId . '.jpg' : 'default.jpg');
}

function floatToMoney($value, $currency = '£') {
    if (empty($value) || $value == 0) {
        return '?';
    } else {
        $value = number_format($value, 2);
    }

    switch ($currency) {
    case '': 
        $currency = 'GBP';
    case 'SEK':
        case 'GBP';
    default:
        return $value . ' ' . $currency;
    }
}

function issetor(&$v, $default = 'Unknown') {
    return empty($v) ? $default : $v;
}

function tplBoolToString($arguments, $smarty) {
    if (!isset($arguments['test'])) {
        $smarty->trigger_error('The test argument is required.');
    }

    $onTrue = isset($arguments['onTrue']) ? $arguments['onTrue'] : 'Yes';
    $onFalse = isset($arguments['onFalse']) ? $arguments['onFalse'] : 'No';
    $onNull = isset($arguments['onNull']) ? $arguments['onNull'] : 'Unknown';

    return boolToString($arguments['test'], $onTrue, $onFalse, $onNull);

}

function boolToString($test, $onTrue = 'Yes', $onFalse = 'No', $onNull = 'Unknown') {
    if ($test == null || strlen($test) == 0) {
        return $onNull;
    }

    if ($test) {
        return $onTrue;
    } else {
        return $onFalse;
    }
}

function getCountUnreadLogs() {
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

function getListOfNextEvents($count = 10) {
    global $db;

    $count = intval($count);

    $sql = 'SELECT e.id, e.title, e.dateStart, v.country FROM events e LEFT JOIN venues v ON e.venue = v.id WHERE e.published = 1 AND e.dateFinish > now() ORDER BY dateStart ASC LIMIT ' . $count;

    $events = $db->query($sql)->fetchAll();
    $events = normalizeEvents($events);

    return $events;
}

function getNextEvent($organizerId = null) {
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

function getEventRating($eventId) {
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

function logMessageToDatabase($priority, $content, $eventId) {
    global $db;

    $stmtLog = $db->prepare('INSERT INTO logs (priority, content, eventType, timestamp) VALUES (:priority, :content, :eventType, now()) ');
    $stmtLog->bindValue(':priority', $priority);
    $stmtLog->bindValue(':content', $content);
    $stmtLog->bindValue(':eventType', $eventId);
    $stmtLog->execute();
}

function requirePriv($ident) {
    if (!Session::getUser()->hasPriv($ident)) {
        throw new Exception('You dont have the privs to do this.');
    }
}

function startSidebar() {
    define('SIDEBAROUTPUT', 1);
    echo '</div><div id = "sidebar">';
}

function redirect($url, $reason) {
    define('REDIRECT', $url);
    if (!in_array('includes/widgets/header.php', get_included_files())) {
        require_once 'includes/widgets/header.minimal.php';
    }

    echo '<h1>Redirecting: '  . $reason.  '</h1>';
    echo '<p>You are being redirected to <a href = "' . $url . '">here</a>.</p>';

    require_once 'includes/widgets/footer.minimal.php';
}

/*
 * Flattening a multi-dimensional array into a
 * single-dimensional one. The resulting keys are a
 * string-separated list of the original keys:
 *
 * a[x][y][z] becomes a[implode(sep, array(x,y,z))]
 */
function array_flatten($array) {
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

function jsForEvents() {
    global $db;

    $sql = 'SELECT e.id, o.id AS organizerId, o.title AS organizerTitle, e.numberOfSeats, e.title AS eventTitle, v.lat, v.lng, e.dateStart, e.dateFinish FROM events e LEFT JOIN (venues v) ON e.venue = v.id LEFT JOIN (organizers o) ON e.organizer = o.id WHERE e.published = 1 AND e.dateFinish > now() ORDER BY e.dateStart DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute();

    foreach ($stmt->fetchAll() as $event) {
        $event['bannerUrl'] = getOrganizerLogoUrl($event['organizerId']);
        $json = json_encode($event);

        echo "addEvent({$json});\n";
    }
}


function jsMapMarker($lat, $lng, $focus = false) {
    $focus = intval($focus);

    return "addMarker({$lat}, {$lng}, '', {$focus});";
}

function addHistoryLink($url, $title) {
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
?>
