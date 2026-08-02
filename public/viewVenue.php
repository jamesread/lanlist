<?php

require_once 'includes/common.php';

use libAllure\Session;
use libAllure\HtmlLinksCollection;

$venueId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$venue = null;

if ($venueId > 0) {
    try {
        $venue = fetchVenue($venueId);
    } catch (Exception $e) {
        $venue = null;
    }
}

if ($venue === null) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    define('TITLE', 'Venue not found');
    define('META_DESCRIPTION', 'The requested LAN party venue could not be found on lanlist.');
    define('META_ROBOTS', 'noindex, nofollow');
    require_once 'includes/widgets/header.php';
    $tpl->display('venueNotFound.tpl');
    require_once 'includes/widgets/footer.php';
}

$venue['countryFlagHtml'] = empty($venue['country']) ? '' : getCountryFlagHtml((string) $venue['country']);

addHistoryLink('viewVenue.php?id=' . $venueId, 'View venue: ' . $venue['title']);

define('INCLUDE_GOOGLE_MAPS', true);
define('TITLE', 'Venue: ' . $venue['title']);
define('META_DESCRIPTION', seoVenueMetaDescription($venue));
require_once 'includes/widgets/header.php';

$associatedOrganizers = [];

if (Session::isLoggedIn() && Session::hasPriv('SUPERUSER')) {
    $sql = 'SELECT DISTINCT e.organizer, o.title AS organizerTitle FROM events e LEFT JOIN organizers o ON e.organizer = o.id WHERE e.venue = :id';
    $stmt = \libAllure\DatabaseFactory::getInstance()->prepare($sql);
    $stmt->bindValue(':id', $venue['id']);
    $stmt->execute();

    $associatedOrganizers = $stmt->fetchAll();
}

$tpl->assign('associatedOrganizers', $associatedOrganizers);
$tpl->assign('eventsAtVenue', fetchEventsFromVenueId($venueId));
$tpl->assign('venue', $venue);
$tpl->display('viewVenue.tpl');
startSidebar();

require_once 'includes/widgets/infoboxListFilter.php';

if (Session::isLoggedIn()) {
    if (Session::hasPriv('EDIT_VENUE') || Session::hasPriv('SUPERUSER')) {
        $menu = new HtmlLinksCollection('Venue admin');
        if (Session::hasPriv('EDIT_VENUE')) {
            $menu->add('formHandler.php?formClazz=FormEditVenue&amp;formEditVenue-id=' . $venue['id'], 'Edit');
        }
        if (Session::hasPriv('SUPERUSER')) {
            $menu->add('formHandler.php?formClazz=FormDeleteVenue&amp;formDeleteVenue-id=' . $venue['id'], 'Delete');
        }
        $tpl->assign('linkCollection', $menu);
        $tpl->display('linkCollection.tpl');
    }

    $canCreateOrganizer = Session::hasPriv('CREATE_ORGANIZER')
        || Session::hasPriv('NEW_ORGANIZER')
        || Session::hasPriv('CREATE_ORGANIZERS')
        || empty(Session::getUser()->getData('organization'));

    if ($canCreateOrganizer) {
        echo '<div class = "infobox"><h2>Organizer admin</h2>';
        echo '<a href = "formHandler.php?formClazz=FormNewOrganizer">Create organizer</a>';
        echo '</div>';
    }
}

require_once 'includes/widgets/footer.php';
