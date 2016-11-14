<?php

require_once 'includes/common.php';

$event = fetchEvent(fromRequestRequireInt('id'));

define('TITLE', 'Event: ' . $event['organizerTitle'] . ' - ' . $event['eventTitle']);
require_once 'includes/widgets/header.php';

$tpl->assign('event', $event);

$marker = jsMapMarker($event['venueLat'], $event['venueLng'], true); 
$tpl->assign('markers', array($marker));
$tpl->registerFunction('boolToString', 'tplBoolToString');
$tpl->display('viewEvent.tpl');

/*
Html::h2('Rating');
if (strtotime($event['dateStart']) > time()) {
	echo 'After this event, you will be able to rate it.';
} else {
	$rating = getEventRating($event['id']);
	echo 'Rating: ' . $rating . ' / 5, with X vote(s).';
}
*/

startSidebar();

$logoUrl = getOrganizerLogoUrl($event['organizerId']);
if (strpos($logoUrl, "default") == false) {
	$tpl->assign('organizerId', $event['organizerId']);
	$tpl->assign('logoUrl', $logoUrl);
	$tpl->display('infobox.organizerLogo.tpl');
}

?>

<div class = "infobox">
	<h2>Other events...</h2>
	<p>You can view list of all other events on a <a href = "eventsMap.php">map</a> or in a <a href = "eventsList.php">list</a>.</p>

	<p>Or, you might want a <a href = "listOrganizers.php">list of organizers</a>.</p>
</div>
<?php
if (Session::isLoggedIn() && (Session::getUser()->hasPriv('MODERATE_EVENTS') || ($event['organizerId'] == Session::getUser()->getData('organization') && !empty($event['organizerId'])))) {
	echo '<div class = "infobox"><h2>Admin</h2>';
	echo '<p>With great power, comes great responsibility...</p><p>';
	echo '<strong>Created on:</strong> ' . $event['createdDate'] . '<br />';

	if (Session::hasPriv('USERLIST')) {
		echo '<strong>Created by:</strong> <a href = "viewUser.php?id=' . $event['createdBy'] . '">' . $event['createdByUsername'] . '</a><br />';
	} else {
		echo '<strong>Created by:</strong> ' . $event['createdByUsername'] . '<br />';
	}

	echo '</p><strong>Functions: </strong><ul>';
	echo '<li><a href = "formHandler.php?formClazz=FormEditEvent&amp;formEditEvent-id=' . $event['id'] . '">Edit</a></li>';
	echo '<li><a href = "misc.php?action=deleteEvent&id=' . $event['id'] . '">Delete</a></li>';
	echo '<li><a href = "misc.php?action=cloneEvent&id=' . $event['id'] . '">Clone</a></li>';

	if (Session::getUser()->hasPriv('TOGGLE_EVENT_PUBLISHED')) {
	echo '<li><a href = "misc.php?action=toggleEvent&id=' . $event['id'] . '">' . (($event['published']) ? 'Unpublish' : 'Publish') . '</a></li>';
	}
	echo '</ul></div>';

	require_once 'includes/widgets/infoboxLinkUs.php';
} else {
?>
<div class = "infobox">
	<h2>&quot;I run this event!&quot;</h2>
	<p>If you run this event then please do join us! By <a href = "loginregister.php">registering a new account</a>, you can associate your user account with this organizer and change any details, as well as add your future events.</p>
	<p>If you know any of the staff members that run this event, do let them know that they are on lanlist.org!</p>
</div>
<?php
}

require_once 'includes/widgets/footer.php';

?>
