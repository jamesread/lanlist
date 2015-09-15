<?php

define('TITLE', 'List of venues that have LAN Parties');
require_once 'includes/widgets/header.php';

if (isset($_REQUEST['country'])) {
	$sql = 'SELECT v.id, v.title, v.country, o.id AS organizerId, count(e.id) AS upcommingEvents, o.title AS organizerTitle FROM venues v LEFT JOIN organizers o ON v.organizer = o.id LEFT JOIN events e ON o.id = e.organizer AND e.dateStart > now() AND e.venue = v.id WHERE v.country = :country AND o.published = 1 GROUP BY v.id ORDER BY v.title';
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':country', $_REQUEST['country']);

	echo '<p>Showing all venues known to host events in: <strong>' . htmlentities($_REQUEST['country']) . '</strong></p>';
} else {
	$sql = 'SELECT v.id, v.title, v.country, o.id AS organizerId, count(e.id) AS upcommingEvents, o.title AS organizerTitle FROM venues v LEFT JOIN organizers o ON v.organizer = o.id LEFT JOIN events e ON o.id = e.organizer AND e.dateStart > now() AND e.venue = v.id WHERE o.published = 1 GROUP BY v.id ORDER BY v.title';
	$stmt = $db->prepare($sql);
}

$stmt->execute();

$tpl->assign('listVenues', $stmt->fetchAll());
$tpl->display('listVenues.tpl');

startSidebar();

require_once 'includes/widgets/venuesByCountry.php';

require_once 'includes/widgets/footer.php';

?>
