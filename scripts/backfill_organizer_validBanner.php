#!/usr/bin/env php
<?php
/**
 * One-time backfill: set organizers.validBanner = 1 where banner JPG exists on disk.
 *
 * Example (from repo root):
 *   php scripts/backfill_organizer_validBanner.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Forbidden: CLI only.\n");
    exit(1);
}

$repoRoot = dirname(__DIR__);
$publicDir = $repoRoot . '/public';
$logoDir = $publicDir . '/resources/images/organizer-logos';

if (!is_dir($publicDir)) {
    fwrite(STDERR, "ERROR: public/ not found at {$publicDir}\n");
    exit(1);
}

chdir($publicDir);
require_once 'includes/common.php';

if (!is_dir($logoDir)) {
    fwrite(STDOUT, "Logo directory missing ({$logoDir}); no banners to backfill.\n");
    exit(0);
}

$organizerIds = [];
foreach (glob($logoDir . '/*.jpg') ?: [] as $path) {
    if (!is_file($path)) {
        continue;
    }
    $base = basename($path, '.jpg');
    if ($base !== '' && ctype_digit($base)) {
        $organizerIds[(int) $base] = true;
    }
}

if ($organizerIds === []) {
    fwrite(STDOUT, "No organizer banner files found in {$logoDir}\n");
    exit(0);
}

$ids = array_keys($organizerIds);
sort($ids, SORT_NUMERIC);

$stmt = $db->prepare('UPDATE organizers SET validBanner = 1 WHERE id = :id LIMIT 1');
$updated = 0;
$missingOrganizer = 0;

foreach ($ids as $organizerId) {
    $stmt->bindValue(':id', $organizerId, \libAllure\Database::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $updated++;
    } else {
        $missingOrganizer++;
        fwrite(STDERR, "WARN: banner file for unknown organizer id={$organizerId}\n");
    }
}

fwrite(
    STDOUT,
    'Backfill complete: ' . $updated . ' organizer(s) marked validBanner=1'
    . ' (' . count($ids) . ' file(s) scanned'
    . ($missingOrganizer > 0 ? ", {$missingOrganizer} orphan file(s)" : '')
    . ").\n"
);
