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

$sql = <<<SQL
SELECT id, createdDate FROM events WHERE published = 1 ORDER BY id ASC
SQL;
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

$sql = 'SELECT DISTINCT organizer AS id FROM events WHERE published = 1 AND organizer IS NOT NULL ORDER BY organizer ASC';
$stmt = $db->prepare($sql);
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $loc = $base . '/viewOrganizer.php?id=' . (int)$row['id'];
    echo '  <url><loc>' . $h($loc) . "</loc></url>\n";
}

$sql = 'SELECT DISTINCT venue AS id FROM events WHERE published = 1 AND venue IS NOT NULL ORDER BY venue ASC';
$stmt = $db->prepare($sql);
$stmt->execute();

foreach ($stmt->fetchAll() as $row) {
    $loc = $base . '/viewVenue.php?id=' . (int)$row['id'];
    echo '  <url><loc>' . $h($loc) . "</loc></url>\n";
}

echo "</urlset>\n";
