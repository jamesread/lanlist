<?php
/**
Warning: This file is a massive mess.
*/

require_once 'includes/common.php';

$sanitizer = new Sanitizer();

if (!isset($_REQUEST['function'])) {
	throw new Exception('The requrest argument "function" is required.'); 
} else {
	define('FUNC', $sanitizer->filterIdentifier('function'));
}

if (!isset($_REQUEST['format'])) {
	throw new Exception('Format is required.');
} else {
	define('FORMAT', $_REQUEST['format']);
}

function csvLine(array $data) {
	$s = null;

	foreach ($data as $element) {
		$s .= '"' . $element . '", ';
	}

	return $s . "\n";
}

switch(FUNC) {
	case 'logs':
		$sql = 'SELECT l.id, l.timestamp, l.priority, l.eventType, l.content FROM logs l ORDER BY id DESC LIMIT 3000';
		$logs = $db->query($sql)->fetchAll();

		switch (FORMAT) {
			case 'csv':
				header('Content-Type: text/plain');
				header('Content-Disposition: inline; filename = "lanlist.org Logs.csv";');

				foreach ($logs as $log) {
					echo csvLine($log);
				}

				break;
			default: throw new Exception('Format not supported: ' . FORMAT);
		}

		break;
	case 'events':
		if (empty($_REQUEST['includePast'])) {
			$sql = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, o.title AS organizer FROM events e LEFT JOIN organizers o ON e.organizer = o.id WHERE e.published = 1 AND e.dateFinish > now()';
		} else {
			$sql = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, o.title AS organizer FROM events e LEFT JOIN organizers o ON e.organizer = o.id WHERE e.published = 1';
		}

		$result = $db->query($sql);
		$events = $result->fetchAll();

		switch (FORMAT) {
			case 'json':
				header('Content-Type: text/plain');
				echo json_encode($events);
				
				break;
			case 'rss':
				header('Content-Type: application/rss+xml');
				$doc = new DOMDocument('1.0');
				$doc->formatOutput = true;
				$elRss = $doc->appendChild($doc->createElement('rss'));
				$elRss->setAttribute('version', '2.0');
				$elRss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
				$elChannel = $elRss->appendChild($doc->createElement('channel'));
				$elAtomSelf = $doc->createElement('atom:link');
				$elAtomSelf->setAttribute('rel', 'self');
				$elAtomSelf->setAttribute('href', 'http://lanlist.org/api.php?function=events&format=rss');
				$elAtomSelf->setAttribute('type', 'application/rss+xml');
				$elChannel->appendChild($elAtomSelf);
				$elChannel->appendChild($doc->createElement('title'))->nodeValue = 'lanlist.org - A list of LAN parties';
				$elChannel->appendChild($doc->createElement('description'))->nodeValue = 'A list of LAN parties.';
				$elChannel->appendChild($doc->createElement('link'))->nodeValue = 'http://lanlist.org';
				$elChannel->appendChild($doc->createElement('lastBuildDate'))->nodeValue = date(DATE_RSS);
				$elChannel->appendChild($doc->createElement('pubDate'))->nodeValue = date(DATE_RSS);

				foreach ($events as $event) {
						$elItem = $elChannel->appendChild($doc->createElement('item'));

						$elItem->appendChild($doc->createElement('title'))->nodeValue = $event['title'];
						$elItem->appendChild($doc->createElement('link'))->nodeValue = 'http://www.lanlist.org/viewEvent.php?id=' . $event['id'];
						$elItem->appendChild($doc->createElement('guid'))->nodeValue = 'http://www.lanlist.org/viewEvent.php?id=' . $event['id'];
						$elItem->appendChild($doc->createElement('pubDate'))->nodeValue = date(DATE_RSS, strtotime($event['dateStart']));
				}

				echo $doc->saveXML();

				break;
			case 'ical':
				header('Content-Type: text/calendar');
				header('Content-Disposition: inline, filename="lanlist.org.ics"');
				define('X_DATE_ICAL', 'Ymd\Thi00');

				echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n";
				echo "PRODID:-//lanlist/hacks//NO\r\n";
				echo "SUMMARY:lanlist.org - A list of LAN Parties\r\n";
				echo "X-WR-CALNAME;VALUE=TEXT:lanlist.org\r\n";
				echo "METHOD:PUBLISH\r\n";
				echo "CALSCALE:GREGORIAN\r\n";

				foreach ($events as $event) {
					echo "BEGIN:VEVENT\r\n";
					echo 'DTSTAMP:' . date(X_DATE_ICAL, strtotime($event['dateStart'])) . "\r\n";
					echo 'DTSTART:' . date(X_DATE_ICAL, strtotime($event['dateStart'])) . "\r\n";
					echo 'DTEND:' . date(X_DATE_ICAL, strtotime($event['dateFinish'])) . "\r\n";
					echo 'DESCRIPTION: Title: ' . $event['title'] . ', Organizer: ' .  $event['organizer'] . ' URL: <a href = "http://www.lanlist.org/viewEvent.php?id=' . $event['id'] . '">linky</a>' . " \r\n";
					echo 'URL: http://www.lanlist.org/viewEvent.php?id=' . $event['id'] . "\r\n";
					echo 'SUMMARY:' . $event['organizer'] . ' - ' . $event['title'] . "\r\n";
					echo 'UUID:event' . $event['id'] . "@lanlist.org\r\n";
					echo "STATUS: CONFIRMED\r\n";
//					echo 'ORGANIZER: ' . $event['organizer'] . "\r\n";
					echo "END:VEVENT\r\n";
				}

				echo "END:VCALENDAR\r\n";

				break;
			case 'csv':
				header('Content-Type: text/plain');

				foreach ($events as $event) {
					echo implode(', ', $event), "\n";
				}

				break;
			default: throw new Exception('Format not supported: ' . FORMAT);
		}

		break;
	default: throw new Exception('Func not supported: ' . FUNC);
}

?>
