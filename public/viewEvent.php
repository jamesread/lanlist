<?php

require_once 'includes/common.php';

use libAllure\Session;

$eventId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$event = null;

if ($eventId > 0) {
    try {
        $event = fetchEvent($eventId);
    } catch (Exception $e) {
        $event = null;
    }
}

if (
    $event === null
    || (!lanlistEventIsPubliclyVisible($event) && !canEditEvent($event['organizerId']))
) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    define('TITLE', 'Event not found');
    define('META_DESCRIPTION', 'The requested LAN party event could not be found on lanlist.');
    define('META_ROBOTS', 'noindex, nofollow');
    require_once 'includes/widgets/header.php';
    $tpl->display('eventNotFound.tpl');
    require_once 'includes/widgets/footer.php';
}

$event['countryFlagHtml'] = empty($event['country']) ? '' : getCountryFlagHtml((string) $event['country']);

require_once __DIR__ . '/includes/functionality/site_checks.php';

addHistoryLink('viewEvent.php?id=' . $event['id'], 'View event: ' . $event['eventTitle']);

define('META_DESCRIPTION', seoEventMetaDescription($event));

$ogImageAbs = seoOrganizerOpenGraphAbsoluteImageUrl(isset($event['organizerId']) ? (int)$event['organizerId'] : null);

if ($ogImageAbs !== null) {
    define('META_OG_IMAGE', $ogImageAbs);
}

$eventCoords = null;

if (
    isset($event['venueLat'], $event['venueLng'])
    && $event['venueLat'] !== '' && $event['venueLng'] !== ''
    && is_numeric($event['venueLat']) && is_numeric($event['venueLng'])
) {
    $eventCoords = ['lat' => (float)$event['venueLat'], 'lng' => (float)$event['venueLng']];
}

$jsonLdPayload = buildEventJsonLdArray($event, $eventCoords);
$jsonEncodeFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonEncodeFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

$tpl->assign('structuredDataJson', json_encode($jsonLdPayload, $jsonEncodeFlags));

$tpl->assign('includeGoogleMaps', !empty($event['venueId']));

define('TITLE', seoEventPageTitle($event));
require_once 'includes/widgets/header.php';

$canEditEvent = canEditEvent($event['organizerId']);

$hasTicketInformation = !empty($event['tickets'])
    || (!empty($event['priceInAdv']) && (float) $event['priceInAdv'] != 0.0)
    || (!empty($event['priceOnDoor']) && (float) $event['priceOnDoor'] != 0.0);
$hasMissingTicketPrices = empty($event['tickets']) && empty($event['priceInAdv']);
$ticketWarningSilenced = $hasMissingTicketPrices && lanlistEventSilencesMissingTickets($event);
$canSilenceTicketWarning = $hasMissingTicketPrices
    && !$ticketWarningSilenced
    && lanlistNextTicketsNotReleasedUntil($event['dateStart']) !== null;

$tpl->assign('canEditEvent', $canEditEvent);
$tpl->assign('hasTicketInformation', $hasTicketInformation);
$tpl->assign('canSilenceTicketWarning', $canSilenceTicketWarning);
$tpl->assign('ticketWarningSilenced', $ticketWarningSilenced);
if ($ticketWarningSilenced) {
    $tpl->assign(
        'ticketWarningSilencedDaysRemaining',
        lanlistTicketsNotReleasedDaysRemaining($event['ticketsNotReleasedUntil'] ?? null)
    );
    $tpl->assign('ticketWarningSilencedUntil', $event['ticketsNotReleasedUntil']);
}
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

if ($canEditEvent) {
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
