<?php

require_once 'includes/common.php';

use libAllure\Session;
use libAllure\Logger;

require_once __DIR__ . '/includes/functionality/moderation.php';

$tpl->assign('includeInlineEdit', true);
require_once 'includes/widgets/header.php';

Session::requirePriv('MODERATOR');

$moderatorUsername = Session::getUser()->getUsername();

$stale = isset($_GET['stale']) ? intval($_GET['stale']) : null;

if ($stale) {
	$org = fetchOrganizer($stale);
	$sql = 'UPDATE organizers SET assumedStale = now() WHERE id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute(['id' => $stale]);
	Logger::messageNormal(
		'Organizer ' . $org['title'] . ' (' . $stale . ') marked stale by ' . $moderatorUsername,
		'MODERATION_MARK_STALE',
		['relatedOrganizer' => $stale]
	);
}

$updateLastChecked = isset($_GET['updateLastChecked']) ? intval($_GET['updateLastChecked']) : null;

if ($updateLastChecked) {
	$org = fetchOrganizer($updateLastChecked);
	$sql = 'UPDATE organizers SET lastChecked = now() WHERE id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute(['id' => $updateLastChecked]);
	Logger::messageNormal(
		'Organizer ' . $org['title'] . ' (' . $updateLastChecked . ') lastChecked updated (no events) by '
		. $moderatorUsername,
		'MODERATION_NO_EVENTS',
		['relatedOrganizer' => $updateLastChecked]
	);
}

$sql = 'SELECT o.id, o.title, o.websiteUrl, o.assumedStale, o.lastChecked, o.discordInviteUrl, o.steamGroupUrl, o.useFavicon FROM organizers o WHERE (o.lastChecked < (now() - INTERVAL 45 day) OR o.lastChecked is null) AND o.assumedStale is NULL AND NOT EXISTS (SELECT 1 FROM events e WHERE e.organizer = o.id AND e.dateStart > now()) ORDER BY rand() LIMIT 1';
$stmt = $db->prepare($sql);
$stmt->execute();

$organizers = $stmt->fetchAll();

if (count($organizers) == 0) {
	$tpl->assign('message', 'No organizers need moderation at this time!!');
	$tpl->display('message.tpl');
	require_once 'includes/widgets/footer.php';
	exit;
}
$selectedOrganizer = $organizers[0];

applyOrganizerPlatformInviteHrefs($selectedOrganizer);

require_once __DIR__ . '/includes/functionality/async_jobs.php';

$oid = (int) $selectedOrganizer['id'];

$latestFaviconAsyncJob = lanlistFetchLatestOrganizerFaviconAsyncJobForDisplay($oid);
$hasActiveFaviconAsyncJob = lanlistSelectActiveOrganizerFaviconJob($oid) !== false;

$selectedOrganizer = lanlistEnrichOrganizerForModeratorView($selectedOrganizer);

$events = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, u.id as uid, u.username, v.id AS venueId, v.title AS venueTitle FROM events e LEFT JOIN venues v ON e.venue = v.id LEFT JOIN users u on e.createdBy = u.id WHERE e.organizer = :organizer ORDER BY e.dateStart DESC';
$stmt = $db->prepare($events);
$stmt->execute(['organizer' => $selectedOrganizer['id']]);

$events = $stmt->fetchAll();

$pastEvents = [];
$futureEvents = [];
$now = new DateTime();

foreach ($events as $event) {
	$startDate = $event['dateStart'] ? new DateTime($event['dateStart']) : null;

	if ($startDate && $startDate < $now) {
		$pastEvents[] = $event;
	} else {
		$futureEvents[] = $event;
	}
}

$selectedOrganizer['pastEvents'] = $pastEvents;
$selectedOrganizer['futureEvents'] = $futureEvents;

$tpl->assign('latestFaviconAsyncJob', $latestFaviconAsyncJob);
$tpl->assign('hasActiveFaviconAsyncJob', $hasActiveFaviconAsyncJob);
$tpl->assign('organizer', $selectedOrganizer);
$tpl->display('moderation.tpl');

startSidebar();

?>
<div class = "infobox">
<ul>

<li><a href = "moderation-rando.php" class = "button">SKIP</a></li>
<li><a href = "formHandler.php?formClazz=FormEditOrganizer&amp;formEditOrganizer-id=<?php echo (int) $selectedOrganizer['id']; ?>" class = "button">Edit organizer</a></li>
<li><a href = "formHandler.php?formClazz=FormNewEvent&amp;formNewEvent-organizer=<?php echo (int) $selectedOrganizer['id']; ?>" class = "button">Create new event</a></li>
<li><a href = "moderation-rando.php?updateLastChecked=<?php echo $selectedOrganizer['id']; ?>" class = "button">NO EVENTS</a></li>
<li><a href = "moderation-rando.php?stale=<?php echo $selectedOrganizer['id']; ?>">MARK STALE</a></li>
<li><a href = "siteChecks.php">Moderator control panel</a></li>

</ul>

</div>
<?php

require_once 'includes/widgets/footer.php';

?>
