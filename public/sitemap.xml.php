<?php

/**
 * XML sitemap (published events plus main indexable URLs).
 */

require_once __DIR__ . '/includes/common.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = rtrim(SITE_BASE_URL, '/');

$h = static function ($string) {
    return htmlspecialchars((string)$string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach (
    [
        '/',
        '/eventsMap.php',
        '/eventsList.php',
        '/listOrganizers.php',
        '/listVenues.php',
        '/listNews.php',
        '/viewBlog.php',
        '/linkus.php',
        '/contact.php',
        '/usefulRelatedSites.php',
        '/licensing.php',
        '/cookies.php',
        '/jobAdverts.php',
        '/sitemap.php',
    ] as $path
) {
    echo '  <url><loc>' . $h($base . $path) . "</loc></url>\n";
}

echo '  <url><loc>' . $h($base . '/eventsList.php?mode=country') . "</loc></url>\n";

foreach (getCountriesWithUpcomingEventCounts() as $row) {
    $country = (string)$row['country'];
    if (getCountryFlagHtml($country) === '') {
        continue;
    }

    $loc = $base . '/eventsList.php?mode=country&country=' . rawurlencode($country);
    echo '  <url><loc>' . $h($loc) . "</loc></url>\n";
}

$sql = <<<SQL
SELECT e.id, e.createdDate FROM events e
INNER JOIN organizers o ON e.organizer = o.id
WHERE e.published = 1
SQL;
$sql .= lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY e.id ASC';
$stmt = $db->prepare($sql);
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $loc = $base . '/viewEvent.php?id=' . (int)$row['id'];
    $created = strtotime($row['createdDate']);

    if ($created === false) {
        echo '  <url><loc>' . $h($loc) . "</loc></url>\n";

        continue;
    }

    $lastmod = date('c', $created);

    echo '  <url><loc>' . $h($loc) . '</loc><lastmod>' . $h($lastmod) . "</lastmod></url>\n";
}

$sql = 'SELECT DISTINCT o.id FROM organizers o INNER JOIN events e ON e.organizer = o.id WHERE e.published = 1' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY o.id ASC';
$stmt = $db->prepare($sql);
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $loc = $base . '/viewOrganizer.php?id=' . (int)$row['id'];
    echo '  <url><loc>' . $h($loc) . "</loc></url>\n";
}

$sql = 'SELECT DISTINCT e.venue AS id FROM events e INNER JOIN organizers o ON e.organizer = o.id WHERE e.published = 1 AND e.venue IS NOT NULL' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY e.venue ASC';
$stmt = $db->prepare($sql);
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $loc = $base . '/viewVenue.php?id=' . (int)$row['id'];
    echo '  <url><loc>' . $h($loc) . "</loc></url>\n";
}

echo "</urlset>\n";
