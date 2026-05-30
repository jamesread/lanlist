<?php

require_once 'includes/common.php';

$eventsListMode = isset($_REQUEST['mode']) ? (string)$_REQUEST['mode'] : '';
$eventsListCountry = isset($_REQUEST['country']) ? trim((string)$_REQUEST['country']) : '';
$allowedCountries = [];

if ($eventsListMode === 'country' && $eventsListCountry !== '') {
    foreach (getCountriesWithUpcomingEventCounts() as $row) {
        $allowedCountries[(string)$row['country']] = true;
    }

    if (empty($allowedCountries[$eventsListCountry])) {
        $eventsListCountry = '';
    }
}

define('MAIN_NOPADDING', true);

$_REQUEST['mode'] = &$_REQUEST['mode'];

switch ($_REQUEST['mode']) {
    case 'perOrganizer':
        $sql = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish, v.country FROM organizers o LEFT JOIN (events e) on e.organizer = o.id RIGHT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1 AND e.dateStart > now()' . lanlistSqlPublicOrganizerVisible('o') . ' GROUP BY o.id ORDER BY e.dateStart';
        break;
    case 'everything':
        $sql = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish,  v.country FROM events e LEFT JOIN (organizers o) ON e.organizer = o.id LEFT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY e.dateStart';
        break;
    case 'country':
        $sql = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish, v.country FROM events e LEFT JOIN (organizers o) ON e.organizer = o.id LEFT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1 AND e.dateStart > now()' . lanlistSqlPublicOrganizerVisible('o');
        if ($eventsListCountry !== '') {
            $sql .= ' AND v.country = :country';
        }
        $sql .= ' ORDER BY e.dateStart';
        break;
    default:
        $sql = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish,  v.country FROM events e LEFT JOIN (organizers o) ON e.organizer = o.id LEFT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1 AND e.dateStart > now()' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY e.dateStart';
        break;
}

$stmt = $db->prepare($sql);
if ($_REQUEST['mode'] === 'country' && $eventsListCountry !== '') {
    $stmt->bindValue(':country', $eventsListCountry);
}
$stmt->execute();
$events = $stmt->fetchAll();
$pastEvents = [];

foreach ($events as $k => $event) {
    $events[$k]['dateStartHuman'] = date_format(date_create($event['dateStart']), 'D jS M Y g:ia');
    $events[$k]['dateFinishHuman'] = date_format(date_create($event['dateFinish']), 'D jS M Y g:ia');
    $events[$k]['countryFlagHtml'] = getCountryFlagHtml((string)$event['country']);
}

if ($eventsListMode === 'country' && $eventsListCountry !== '') {
    $sqlPast = 'SELECT e.id, e.numberOfSeats, e.title, v.title AS venueTitle, o.id AS organizerId, o.title AS organizerTitle, e.dateStart, e.dateFinish, v.country FROM events e LEFT JOIN (organizers o) ON e.organizer = o.id LEFT JOIN (venues v) ON e.venue = v.id WHERE e.published = 1 AND e.dateStart <= now() AND v.country = :country' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY e.dateStart DESC';
    $stmtPast = $db->prepare($sqlPast);
    $stmtPast->bindValue(':country', $eventsListCountry);
    $stmtPast->execute();
    $pastEvents = $stmtPast->fetchAll();

    foreach ($pastEvents as $k => $event) {
        $pastEvents[$k]['dateStartHuman'] = date_format(date_create($event['dateStart']), 'D jS M Y g:ia');
        $pastEvents[$k]['dateFinishHuman'] = date_format(date_create($event['dateFinish']), 'D jS M Y g:ia');
        $pastEvents[$k]['countryFlagHtml'] = getCountryFlagHtml((string)$event['country']);
    }
}

$eventsListCountryStats = null;
$eventsListCountryRelatedSites = [];
if ($eventsListMode === 'country' && $eventsListCountry !== '') {
    $eventsListCountryStats = fetchCountryEventStats($eventsListCountry);
    $eventsListCountryRelatedSites = lanlistFetchUsefulRelatedSitesForCountry($eventsListCountry);
}

switch ($eventsListMode) {
    case 'perOrganizer':
        define('TITLE', 'Events in a list');
        define(
            'META_DESCRIPTION',
            'Upcoming LAN parties grouped by organizer, with venues, countries, start dates, and seat counts.'
        );
        break;
    case 'everything':
        define('TITLE', 'Events in a list');
        define(
            'META_DESCRIPTION',
            'All published LAN parties on lanlist, including past events, with organizers, venues, dates, and seating.'
        );
        break;
    case 'country':
        if ($eventsListCountry !== '') {
            define('TITLE', seoCountryEventsPageTitle($eventsListCountry));
            define('META_DESCRIPTION', seoCountryEventsMetaDescription($eventsListCountry, $eventsListCountryStats));

            $jsonLdPayload = buildCountryEventsListJsonLd($eventsListCountry, $events, $eventsListCountryStats);
            $jsonEncodeFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jsonEncodeFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }

            $tpl->assign('structuredDataJson', json_encode($jsonLdPayload, $jsonEncodeFlags));
        } else {
            define('TITLE', 'LAN parties by country');
            define('META_DESCRIPTION', seoCountryEventsIndexMetaDescription());
        }
        break;
    default:
        define('TITLE', 'Events in a list');
        define(
            'META_DESCRIPTION',
            'Chronological list of upcoming LAN parties with organizers, venues, countries, and start dates.'
        );
        break;
}

require_once 'includes/widgets/header.php';

$tpl->assign('eventsListMode', $eventsListMode);
$tpl->assign('eventsListCountry', $eventsListCountry);
$tpl->assign(
    'eventsListCountryFlagHtml',
    ($eventsListMode === 'country' && $eventsListCountry !== '')
        ? getCountryFlagHtml($eventsListCountry)
        : ''
);
$tpl->assign('eventsListCountryStats', $eventsListCountryStats);
$tpl->assign('eventsListCountryRelatedSites', $eventsListCountryRelatedSites);
$tpl->assign('listEvents', $events);
$tpl->assign('listPastEvents', $pastEvents);
$tpl->display('eventsList.tpl');

startSidebar();

require_once 'includes/widgets/infoboxListFilter.php';
require_once 'includes/widgets/infoboxEventsListCountries.php';

$tpl->display('infobox.otherFormats.tpl');
$tpl->display('infobox.addEvents.tpl');

require_once 'includes/widgets/footer.php';
