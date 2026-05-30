<?php

define('TITLE', 'List of venues that have LAN Parties');
define(
    'META_DESCRIPTION',
    'Venues that host LAN parties listed on lanlist — find locations and see which events use each venue.'
);
require_once 'includes/widgets/header.php';

$eventJoinOn = 'e.venue = v.id AND e.dateStart > NOW() AND ' . lanlistSqlPublicVisibleEventJoinConditions('e');

if (isset($_REQUEST['country'])) {
    $sql = 'SELECT v.id, v.title, v.country, count(e.id) AS upcommingEvents FROM venues v LEFT JOIN events e ON ' . $eventJoinOn . ' WHERE v.country = :country GROUP BY v.id ORDER BY v.title';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':country', $_REQUEST['country']);

    $country = (string) $_REQUEST['country'];
    $countryFlagHtml = getCountryFlagHtml($country);

    echo '<p>Showing all venues known to host events in: <strong>';
    if ($countryFlagHtml !== '') {
        echo '<span class="eventsList-countryFlag" aria-hidden="true">' . $countryFlagHtml . '</span> ';
    }
    echo htmlentities($country, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</strong>. ';
    echo '<a href = "eventsList.php?mode=country&amp;country=' . urlencode($country) . '">View upcoming events in this country</a>.</p>';
} else {
    $sql = 'SELECT v.id, v.title, v.country, count(e.id) AS upcommingEvents FROM venues v LEFT JOIN events e ON ' . $eventJoinOn . ' GROUP BY v.id ORDER BY v.title';
    $stmt = $db->prepare($sql);
}

$stmt->execute();

$venues = $stmt->fetchAll();
foreach ($venues as $k => $venue) {
    $venues[$k]['countryFlagHtml'] = getCountryFlagHtml((string) $venue['country']);
}

$tpl->assign('listVenues', $venues);
$tpl->display('listVenues.tpl');

startSidebar();

require_once 'includes/widgets/venuesByCountry.php';

if (libAllure\Session::hasPriv('CREATE_VENUE')) {
    echo '<div class = "infobox"><h2>Venue admin</h2>';
    echo '<a href = "formHandler.php?formClazz=FormNewVenue">Create Venue</a>';
    echo '</div>';
}

require_once 'includes/widgets/footer.php';
