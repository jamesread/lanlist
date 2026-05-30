<?php

require_once 'includes/common.php';

use libAllure\Session;
use libAllure\HtmlLinksCollection;

$id = fromRequestRequireInt('id');
$venue = fetchVenue($id);
$venue['countryFlagHtml'] = empty($venue['country']) ? '' : getCountryFlagHtml((string) $venue['country']);

addHistoryLink('viewVenue.php?id=' . $id, 'View venue: ' . $venue['title']);

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
$tpl->assign('eventsAtVenue', fetchEventsFromVenueId($id));
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
