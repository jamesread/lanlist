<?php

require_once 'includes/common.php';

$organizer = fetchOrganizer(fromRequestRequireInt('id'));

define('TITLE', 'Organizer: ' . $organizer['title']);
require_once 'includes/widgets/header.php';

$organizer['logoUrl'] = getOrganizerLogoUrl($organizer['id']);
$tpl->assign('organizer', $organizer);

$events = fetchEventsFromOrganizerId($organizer['id']);
$tpl->assign('events', $events);

if (Session::isLoggedIn() && (Session::getUser()->hasPriv('SUPERUSER') || Session::getUser()->getData('organization') == $organizer['id'])) {
	$sql = 'SELECT u.id, u.username, u.lastLogin FROM users u WHERE u.organization = :organizer';
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':organizer', $organizer['id']);
	$stmt->execute();

	$tpl->assign('associatedUsers', $stmt->fetchAll());

	$sql = 'SELECT v.id, v.title, count(e.id) AS eventCount FROM venues v LEFT JOIN events e ON e.venue = v.id WHERE v.organizer = :organizer GROUP BY v.id';
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':organizer', $organizer['id']);
	$stmt->execute();

	$tpl->assign('associatedVenues', $stmt->fetchAll());
}

$tpl->assign('userlist', Session::hasPriv('USERLIST'));
$tpl->display('viewOrganizer.tpl');

startSidebar();

$nextEvent = getNextEvent($organizer['id']);

echo '<div class = "infobox"><h2>Next event for ' . $organizer['title'] . '</h2>';
if (empty($nextEvent)) {
	echo '<p style = "color:red">To the best of our knowledge, ' . $organizer['title'] . ' has nothing planned... :(</p>';
} else {
	echo '<p>Next event is <a href = "viewEvent.php?id=' . $nextEvent['id'] . '">' . $nextEvent['title'] . '</a></p>';
}

echo '<p>You may find another organizer near you on the <a href = "eventsMap.php">map</a> or from the <a href = "listOrganizers.php">list of organizers</a>.</p>';
echo '</div>';

if (Session::isLoggedIn() && Session::getUser()->hasPriv('EDIT_ORGANIZER') || Session::isLoggedIn() && Session::getUser()->getData('organization') == $organizer['id']) {
	echo '<div class = "infobox">';
	echo '<h2>Organizer admin</h2>';
	echo '<ul>';
	if ($organizer['venueCount'] == 0) {
		echo '<li>New Event - <em>Make a venue first</em></li>';
	} else {
		echo '<li><a href = "formHandler.php?formClazz=FormNewEvent&formNewEvent-organizer=' . $organizer['id'] .  '">New Event</a></li>';
	}

	echo '<li><a href = "formHandler.php?formClazz=FormNewVenue&formNewVenue-organizer=' . $organizer['id'] .  '">New Venue</a></li>';
	echo '<li><a href = "formHandler.php?formClazz=FormEditOrganizer&amp;formEditOrganizer-id=' . $organizer['id'] . '">Edit organizer details</a></li>';
	echo '</ul></div>';
}

require_once 'includes/widgets/footer.php';

?>
