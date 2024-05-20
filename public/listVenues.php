<?php

define('TITLE', 'List of venues that have LAN Parties');
require_once 'includes/widgets/header.php';

if (isset($_REQUEST['country'])) {
    $sql = 'SELECT v.id, v.title, v.country, count(e.id) AS upcommingEvents FROM venues v LEFT JOIN events e ON e.venue = v.id AND e.dateStart > now() AND e.venue = v.id WHERE v.country = :country GROUP BY v.id ORDER BY v.title';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':country', $_REQUEST['country']);

    echo '<p>Showing all venues known to host events in: <strong>' . htmlentities($_REQUEST['country']) . '</strong></p>';
} else {
    $sql = 'SELECT v.id, v.title, v.country, count(e.id) AS upcommingEvents FROM venues v LEFT JOIN events e ON e.venue = v.id AND e.dateStart > now() AND e.venue = v.id GROUP BY v.id ORDER BY v.title';
    $stmt = $db->prepare($sql);
}

$stmt->execute();

$tpl->assign('listVenues', $stmt->fetchAll());
$tpl->display('listVenues.tpl');

startSidebar();

require_once 'includes/widgets/venuesByCountry.php';

if (libAllure\Session::hasPriv('CREATE_VENUE')) {
    echo '<div class = "infobox"><h2>Venue admin</h2>';
    echo '<a href = "formHandler.php?formClazz=FormNewVenue">Create Venue</a>';
    echo '</div>';
}

require_once 'includes/widgets/footer.php';
