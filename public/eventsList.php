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

define('TITLE', $eventsListMode === 'country' && $eventsListCountry !== ''
    ? 'Upcoming events in ' . $eventsListCountry
    : 'Events in a list');
define('MAIN_NOPADDING', true);

switch ($eventsListMode) {
    case 'perOrganizer':
        define(
            'META_DESCRIPTION',
            'Upcoming LAN parties grouped by organizer, with venues, countries, start dates, and seat counts.'
        );
        break;
    case 'everything':
        define(
            'META_DESCRIPTION',
            'All published LAN parties on lanlist, including past events, with organizers, venues, dates, and seating.'
        );
        break;
    case 'country':
        if ($eventsListCountry !== '') {
            define(
                'META_DESCRIPTION',
                'Upcoming LAN parties in ' . $eventsListCountry . ' with organizers, venues, and start dates.'
            );
        } else {
            define(
                'META_DESCRIPTION',
                'Upcoming LAN parties grouped by country, with organizers, venues, and start dates.'
            );
        }
        break;
    default:
        define(
            'META_DESCRIPTION',
            'Chronological list of upcoming LAN parties with organizers, venues, countries, and start dates.'
        );
        break;
}

require_once 'includes/widgets/header.php';

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

foreach ($events as $k => $event) {
    $events[$k]['dateStartHuman'] = date_format(date_create($event['dateStart']), 'D jS M Y g:ia');
    $events[$k]['dateFinishHuman'] = date_format(date_create($event['dateFinish']), 'D jS M Y g:ia');
    $events[$k]['countryFlagHtml'] = getCountryFlagHtml((string)$event['country']);
}

$tpl->assign('eventsListMode', $eventsListMode);
$tpl->assign('eventsListCountry', $eventsListCountry);
$tpl->assign('listEvents', $events);
$tpl->display('eventsList.tpl');

startSidebar();

require_once 'includes/widgets/infoboxListFilter.php';
require_once 'includes/widgets/infoboxEventsListCountries.php';

$tpl->display('infobox.otherFormats.tpl');
$tpl->display('infobox.addEvents.tpl');

require_once 'includes/widgets/footer.php';
