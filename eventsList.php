<?php

define('TITLE', 'Events in a list');
require_once 'includes/widgets/header.php';

$_REQUEST['mode'] = &$_REQUEST['mode'];

switch ($_REQUEST['mode']) {
	case 'perOrganizer': 
		$sql = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish, v.country FROM organizers o LEFT JOIN (events e) on e.organizer = o.id RIGHT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1 AND e.dateStart > now() GROUP BY o.id ';
		break;
	case 'everything':
		$sql = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish,  v.country FROM events e LEFT JOIN (organizers o) ON e.organizer = o.id LEFT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1 ';
		break;
	default: 
		$sql = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish,  v.country FROM events e LEFT JOIN (organizers o) ON e.organizer = o.id LEFT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1 AND e.dateStart > now()';
		break;
}

$stmt = $db->prepare($sql);
$stmt->execute();

$tpl->assign('listEvents', $stmt->fetchAll());
$tpl->display('eventsList.tpl');

startSidebar();

require_once 'includes/widgets/infoboxListFilter.php';

$tpl->display('infobox.otherFormats.tpl');
$tpl->display('infobox.addEvents.tpl');

require_once 'includes/widgets/footer.php';

?>
