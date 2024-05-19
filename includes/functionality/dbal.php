<?php

use \libAllure\Session;
use \libAllure\Database;

function fetchEventsFromOrganizerId($id) {
	global $db;

	if (Session::isLoggedIn() && (Session::getUser()->hasPriv('SUPERUSER') || Session::getUser()->getData('organization') == $id)) {
		$sql = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, e.published FROM events e WHERE e.organizer = :id ORDER BY e.dateStart';
	} else {
		$sql = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, e.published FROM events e WHERE e.organizer = :id AND e.published = 1 ORDER BY e.dateStart';
	}

	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id', $id);
	$stmt->execute();

        $events = normalizeEvents($stmt->fetchAll());

	return $events;
}

function fetchEventsFromVenueId($id) {
	global $db;

	$sql = 'SELECT e.title, e.dateStart, e.dateFinish, e.id FROM events e LEFT JOIN venues v ON v.id = e.venue WHERE e.venue = v.id AND e.dateStart >= now() AND v.id = :venueId';
	$stmt = $db->prepare($sql);
	$stmt->bindValue('venueId', $id);
        $stmt->execute();

        $events = normalizeEvents($stmt->fetchAll());

	return $events;
}

function fromRequestRequireInt($name) {
	if (isset($_REQUEST[$name])) {
		return intval($_REQUEST[$name]);
	} else {
		throw new Exception('Required variable not set.');
	}
}

function fetchOrganizer($id) {
	global $db;
	$sql = <<<SQL
SELECT 
	o.id,
	o.title,
	o.published,
	o.websiteUrl,
	o.assumedStale,
	o.steamGroupUrl,
	o.blurb,
        o.useFavicon
FROM 
	organizers o
WHERE 
	o.id = :id
GROUP BY 
	o.id
LIMIT 1
SQL;
	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id', intval($id));
	$stmt->execute();
	
	if ($stmt->numRows() == 0) {
		throw new Exception('Organizer not found');
	} else {
		return $stmt->fetchRow();
	}
}

function fetchVenue($id) {
	global $db;

	$sql = <<<SQL
SELECT 
	v.id,
	v.title,
	v.lat,
	v.lng,
	v.country
FROM 
	venues v
WHERE 
	v.id = :id
LIMIT 1
SQL;

	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id', intval($id), Database::PARAM_INT);
	$stmt->execute();

	if ($stmt->numRows() == 0) {
		throw new Exception('Venue not found ' . $id);
	}

	return $stmt->fetchRow();
}

function fetchEvent($id) {
	global $db;

	$id = intval($id);

	$sql = <<<SQL
SELECT 
	e.id, 
	e.published, 
	e.title AS eventTitle, 
	e.website, 
	e.dateStart, 
	e.dateFinish, 
	e.priceOnDoor,
	e.priceInAdv,
	e.currency,
	e.showers,
	e.sleeping,
	e.smoking,
	e.alcohol,
	e.networkMbps,
	e.internetMbps,
	e.numberOfSeats,
	e.blurb,
	e.createdBy,
        e.createdDate,
        e.ageRestrictions,
	u.username AS createdByUsername,
	o.title AS organizerTitle, 
	o.id AS organizerId, 
	v.id AS venueId,
	v.title AS venueTitle,
	v.lat AS venueLat,
	v.lng AS venueLng
FROM 
	events e 
LEFT JOIN (users u) ON 
	e.createdBy = u.id
LEFT JOIN (organizers o) ON 
	e.organizer = o.id 
LEFT JOIN (venues v) ON 
	e.venue = v.id 
WHERE 
	e.id = :id LIMIT 1
SQL;

	$stmt = $db->prepare($sql);
	$stmt->bindValue(':id', $id, Database::PARAM_INT);
	$stmt->execute();

	if ($stmt->numRows() == 0) {
		throw new Exception('Event not found.');
        }

        $event = $stmt->fetchRow();
        $event['dateStartHuman'] = date_format(date_create($event['dateStart']), 'D jS M Y g:ia');
        $event['dateFinishHuman'] = date_format(date_create($event['dateFinish']), 'D jS M Y g:ia');

	return $event;
}

?>
