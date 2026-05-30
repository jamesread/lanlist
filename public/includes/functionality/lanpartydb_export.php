<?php

declare(strict_types=1);

require_once __DIR__ . '/inline_edit.php';

const LANPARTYDB_REPO_URL = 'https://github.com/lanpartydb/data';
const LANPARTYDB_FORMAT_URL = 'https://github.com/lanpartydb/data/blob/main/FORMAT.md';

/**
 * Lanlist venue country name → ISO 3166-1 alpha-2 for lanpartydb.
 *
 * @return array<string, string>
 */
function lanpartydbCountryNameToCodeMap(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $codeToName = [
        'gb' => 'United Kingdom',
        'us' => 'United States',
        'ie' => 'Ireland',
        'de' => 'Germany',
        'fr' => 'France',
        'nl' => 'Netherlands',
        'be' => 'Belgium',
        'au' => 'Australia',
        'nz' => 'New Zealand',
        'ca' => 'Canada',
        'se' => 'Sweden',
        'no' => 'Norway',
        'dk' => 'Denmark',
        'fi' => 'Finland',
        'pl' => 'Poland',
        'at' => 'Austria',
        'ch' => 'Switzerland',
        'es' => 'Spain',
        'it' => 'Italy',
        'pt' => 'Portugal',
        'cz' => 'Czech Republic',
        'sk' => 'Slovakia',
        'si' => 'Slovenia',
        'hu' => 'Hungary',
        'ro' => 'Romania',
        'bg' => 'Bulgaria',
        'hr' => 'Croatia',
        'ee' => 'Estonia',
        'lv' => 'Latvia',
        'lt' => 'Lithuania',
        'lu' => 'Luxembourg',
        'is' => 'Iceland',
        'jp' => 'Japan',
        'kr' => 'Korea, South',
        'tw' => 'Taiwan',
        'br' => 'Brazil',
        'mx' => 'Mexico',
        'ru' => 'Russia',
        'ua' => 'Ukraine',
        'tr' => 'Turkey',
        'gr' => 'Greece',
        'rs' => 'Serbia and Montenegro',
        'sg' => 'Singapore',
        'hk' => 'Hong Kong',
        'za' => 'South Africa',
    ];

    $map = [];
    foreach ($codeToName as $code => $name) {
        $map[$name] = $code;
    }

    return $map;
}

function lanpartydbUserCanExportForOrganizer(int $organizerId): bool
{
    return lanlistUserCanEditOrganizer($organizerId);
}

/**
 * @return list<array<string, mixed>>
 */
function lanpartydbFetchOrganizerEventsForExport(int $organizerId): array
{
    global $db;

    $sql = <<<SQL
SELECT
    e.id,
    e.title,
    e.dateStart,
    e.dateFinish,
    e.website,
    e.numberOfSeats,
    e.published,
    v.id AS venueId,
    v.title AS venueTitle,
    v.lat AS venueLat,
    v.lng AS venueLng,
    v.country AS venueCountry
FROM events e
LEFT JOIN venues v ON e.venue = v.id
WHERE e.organizer = :organizerId
ORDER BY e.dateStart ASC
SQL;

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':organizerId', $organizerId, \PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

function lanpartydbSlugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim((string) $text, '-');

    return $text !== '' ? substr($text, 0, 64) : 'organizer';
}

function lanpartydbTomlString(string $value): string
{
    if ($value === '') {
        return '""';
    }
    if (preg_match('~^[A-Za-z0-9][A-Za-z0-9 _.\-#/]*$~', $value)) {
        return $value;
    }

    return '"' . str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', ' ', ' '], $value) . '"';
}

function lanpartydbTomlDate(?string $datetime): ?string
{
    if ($datetime === null || trim($datetime) === '') {
        return null;
    }

    $ts = strtotime($datetime);
    if ($ts === false) {
        return null;
    }

    return date('Y-m-d', $ts);
}

function lanpartydbCountryCode(?string $countryName): ?string
{
    $name = trim((string) $countryName);
    if ($name === '') {
        return null;
    }

    $map = lanpartydbCountryNameToCodeMap();

    return $map[$name] ?? null;
}

/**
 * @param array<string, mixed> $organizer
 * @param list<array<string, mixed>> $events
 * @return array<string, mixed>
 */
function lanpartydbBuildExportPackage(array $organizer, array $events): array
{
    $seriesSlug = lanpartydbSlugify((string) ($organizer['title'] ?? 'organizer'));
    $countryCodes = [];
    $parties = [];
    $notEligible = [];
    $usedPartySlugs = [];
    $now = time();
    $orgWebsite = trim((string) ($organizer['websiteUrl'] ?? ''));

    foreach ($events as $event) {
        $venueCountry = trim((string) ($event['venueCountry'] ?? ''));
        $cc = lanpartydbCountryCode($venueCountry);
        if ($cc !== null) {
            $countryCodes[$cc] = true;
        }
    }

    foreach ($events as $event) {
        $eventId = (int) ($event['id'] ?? 0);
        $title = trim((string) ($event['title'] ?? ''));
        $finishTs = strtotime((string) ($event['dateFinish'] ?? ''));
        $isPast = $finishTs !== false && $finishTs < $now;
        $isPublished = !empty((int) ($event['published'] ?? 0));

        if (!$isPast) {
            $notEligible[] = [
                'id' => $eventId,
                'title' => $title,
                'reason' => 'Event has not finished yet (lanpartydb only archives past public LANs).',
            ];
            continue;
        }

        if (!$isPublished) {
            $notEligible[] = [
                'id' => $eventId,
                'title' => $title,
                'reason' => 'Event is not published on lanlist (only public events are exported).',
            ];
            continue;
        }

        $startOn = lanpartydbTomlDate((string) ($event['dateStart'] ?? ''));
        $endOn = lanpartydbTomlDate((string) ($event['dateFinish'] ?? ''));
        if ($startOn === null || $endOn === null) {
            $notEligible[] = [
                'id' => $eventId,
                'title' => $title,
                'reason' => 'Missing or invalid start/finish dates.',
            ];
            continue;
        }

        $venueCountry = trim((string) ($event['venueCountry'] ?? ''));
        $countryCode = lanpartydbCountryCode($venueCountry);
        if ($countryCode === null) {
            $notEligible[] = [
                'id' => $eventId,
                'title' => $title,
                'reason' => 'Venue country is missing or not mapped to ISO alpha-2 (' . ($venueCountry !== '' ? $venueCountry : 'empty') . ').',
            ];
            continue;
        }

        if (empty((int) ($event['venueId'] ?? 0))) {
            $notEligible[] = [
                'id' => $eventId,
                'title' => $title,
                'reason' => 'No venue linked (location section required).',
            ];
            continue;
        }

        $partySlug = lanpartydbSlugify($seriesSlug . '-' . $title);
        if ($partySlug === $seriesSlug) {
            $partySlug = $seriesSlug . '-' . $startOn;
        }
        $partySlug = substr($partySlug, 0, 64);
        while (isset($usedPartySlugs[$partySlug])) {
            $partySlug = substr($partySlug . '-' . $eventId, 0, 64);
        }
        $usedPartySlugs[$partySlug] = true;

        $website = trim((string) ($event['website'] ?? ''));
        if ($website === '') {
            $website = $orgWebsite;
        }
        if ($website === '' || !preg_match('#^https?://#i', $website)) {
            $notEligible[] = [
                'id' => $eventId,
                'title' => $title,
                'reason' => 'No event or organizer website URL (required for [links.website]).',
            ];
            continue;
        }

        $lines = [];
        $lines[] = 'slug = "' . $partySlug . '"';
        $lines[] = 'title = ' . lanpartydbTomlString($title);
        $lines[] = 'series_slug = "' . $seriesSlug . '"';
        $organizerTitle = trim((string) ($organizer['title'] ?? ''));
        if ($organizerTitle !== '') {
            $lines[] = 'organizer_entity = ' . lanpartydbTomlString($organizerTitle);
        }
        $lines[] = 'start_on = ' . $startOn;
        $lines[] = 'end_on = ' . $endOn;

        $seats = $event['numberOfSeats'] ?? null;
        if ($seats !== null && $seats !== '' && (int) $seats > 0) {
            $lines[] = 'seats = ' . (int) $seats;
        }

        $lines[] = '';
        $lines[] = '[location]';
        $venueTitle = trim((string) ($event['venueTitle'] ?? ''));
        if ($venueTitle !== '') {
            $lines[] = 'name = ' . lanpartydbTomlString($venueTitle);
        }
        $lines[] = 'country_code = "' . $countryCode . '"';
        $lines[] = 'city = ' . lanpartydbTomlString('TBC');

        $lat = $event['venueLat'] ?? null;
        $lng = $event['venueLng'] ?? null;
        if ($lat !== null && $lat !== '' && $lng !== null && $lng !== '') {
            $lines[] = 'latitude = ' . round((float) $lat, 5);
            $lines[] = 'longitude = ' . round((float) $lng, 5);
        }

        $lines[] = '';
        $lines[] = '[links.website]';
        $lines[] = 'url = ' . lanpartydbTomlString($website);

        $parties[] = [
            'id' => $eventId,
            'title' => $title,
            'filename' => 'data/parties/' . $seriesSlug . '/' . $partySlug . '.toml',
            'toml' => implode("\n", $lines),
            'start_on' => $startOn,
        ];
    }

    ksort($countryCodes);
    $codesList = array_keys($countryCodes);

    $seriesLines = [];
    $seriesLines[] = 'slug = "' . $seriesSlug . '"';
    $seriesLines[] = 'title = ' . lanpartydbTomlString((string) ($organizer['title'] ?? 'Organizer'));
    if ($codesList !== []) {
        $seriesLines[] = 'country_codes = [' . implode(', ', array_map(static fn (string $c): string => '"' . $c . '"', $codesList)) . ']';
    } else {
        $seriesLines[] = 'country_codes = ["gb"]';
    }

    if ($orgWebsite !== '' && preg_match('#^https?://#i', $orgWebsite)) {
        $seriesLines[] = '';
        $seriesLines[] = '[links.website]';
        $seriesLines[] = 'url = ' . lanpartydbTomlString($orgWebsite);
    }

    $seriesToml = implode("\n", $seriesLines);

    $allToml = "# OrgaTalk LAN Party Database export from lanlist.info\n";
    $allToml .= "# Repository: " . LANPARTYDB_REPO_URL . "\n";
    $allToml .= "# Format: " . LANPARTYDB_FORMAT_URL . "\n\n";
    $allToml .= "# --- data/series/" . $seriesSlug . ".toml ---\n\n";
    $allToml .= $seriesToml . "\n";

    foreach ($parties as $party) {
        $allToml .= "\n# --- " . $party['filename'] . " ---\n\n";
        $allToml .= $party['toml'] . "\n";
    }

    return [
        'series_slug' => $seriesSlug,
        'series_filename' => 'data/series/' . $seriesSlug . '.toml',
        'series_toml' => $seriesToml,
        'parties' => $parties,
        'party_count' => count($parties),
        'not_eligible' => $notEligible,
        'all_toml' => $allToml,
        'country_codes' => $codesList,
    ];
}
