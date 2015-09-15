<?php

require_once 'jwrCommonsPhp/Inflector.php';

$sql = 'SELECT v.country, count(v.id) AS venueCount, count(e.id) AS eventCount FROM venues v LEFT JOIN (events e) ON e.venue = v.id AND e.dateStart > now() GROUP BY v.country ORDER BY v.country';
$stmt = $db->prepare($sql);
$stmt->execute();

echo '<div class = "infobox">';
echo '<h2>By Country</h2>';
echo '<p>The following countries have events coming up soon...</p>';
echo '<ul>';
foreach ($stmt->fetchAll() as $venueCountry) {
	echo '<li><a href = "listVenues.php?country=' . $venueCountry['country'] . '">' . $venueCountry['country'] . '</a> - ' . $venueCountry['venueCount'] . ' ' . Inflector::quantify('venue', $venueCountry['venueCount']) . ', ' . $venueCountry['eventCount'] . ' ' . Inflector::quantify('event', $venueCountry['eventCount']) . '</li>';
}

echo '</ul></div>';

?>
