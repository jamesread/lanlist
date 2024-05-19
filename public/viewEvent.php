<?php

require_once 'includes/common.php';

use libAllure\Session;

$event = fetchEvent(fromRequestRequireInt('id'));

addHistoryLink('viewEvent.php?id=' . $event['id'], 'View event: ' . $event['eventTitle']);

define('TITLE', 'Event: ' . $event['organizerTitle'] . ' - ' . $event['eventTitle']);
require_once 'includes/widgets/header.php';

$tpl->assign('event', $event);

$marker = jsMapMarker($event, true);
$tpl->assign('markers', array($marker));
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

require_once 'includes/widgets/infoboxOtherEvents.php';

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
    require_once 'includes/widgets/infoboxClaimEvent.php';
}

require_once 'includes/widgets/footer.php';
