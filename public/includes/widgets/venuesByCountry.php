<?php

use libAllure\Inflector;

$sql = 'SELECT v.country, count(v.id) AS venueCount, count(e.id) AS eventCount FROM venues v LEFT JOIN (events e) ON e.venue = v.id AND e.dateStart > now() GROUP BY v.country ORDER BY v.country';
$stmt = $db->prepare($sql);
$stmt->execute();

echo '<div class = "infobox">';
echo '<h2>By Country</h2>';
echo '<p>The following countries have events coming up soon. Country names link to upcoming LAN parties; venue counts filter this list.</p>';
echo '<ul>';
foreach ($stmt->fetchAll() as $venueCountry) {
    $country = (string) $venueCountry['country'];
    $flag = getCountryFlagHtml($country);
    $venueCount = (int) $venueCountry['venueCount'];
    $eventCount = (int) $venueCountry['eventCount'];

    echo '<li>';
    echo '<a href = "eventsList.php?mode=country&amp;country=' . urlencode($country) . '" title = "LAN Parties in ' . htmlspecialchars($country, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">';
    if ($flag !== '') {
        echo $flag . ' ';
    }
    echo htmlspecialchars($country, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    echo '</a> - ';
    echo '<a href = "listVenues.php?country=' . urlencode($country) . '">' . $venueCount . ' ' . Inflector::quantify('venue', $venueCount) . '</a>, ';
    echo $eventCount . ' ' . Inflector::quantify('event', $eventCount);
    echo '</li>';
}

echo '</ul></div>';
