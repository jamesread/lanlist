<?php

require_once 'includes/widgets/header.php';

use \libAllure\Session;

Session::requirePriv('MODERATOR');

$stale = isset($_GET['stale']) ? intval($_GET['stale']) : null;

if ($stale) {
	$sql = 'UPDATE organizers SET assumedStale = now() WHERE id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute(['id' => $stale]);
}

$updateLastChecked = isset($_GET['updateLastChecked']) ? intval($_GET['updateLastChecked']) : null;

if ($updateLastChecked) {
	$sql = 'UPDATE organizers SET lastChecked = now() WHERE id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute(['id' => $updateLastChecked]);
}

$sql = 'SELECT o.id, o.title, o.websiteUrl, o.assumedStale, o.lastChecked FROM organizers o WHERE (o.lastChecked < (now() - INTERVAL 45 day) OR o.lastChecked is null) AND o.assumedStale is NULL AND NOT EXISTS (SELECT 1 FROM events e WHERE e.organizer = o.id AND e.dateStart > now()) ORDER BY rand() LIMIT 1';
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

$events = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, u.id as uid, u.username FROM events e LEFT JOIN users u on e.createdBy = u.id WHERE e.organizer = :organizer ORDER BY e.dateStart DESC';
$stmt = $db->prepare($events);
$stmt->execute(['organizer' => $selectedOrganizer['id']]);

$events = $stmt->fetchAll();

foreach ($events as $k => $event) {
	$startDate = $event['dateStart'] ? new DateTime($event['dateStart']) : null;

	$inPast = $startDate && $startDate < new DateTime();

	$events[$k]['inPast'] = $inPast;
}

$selectedOrganizer['events'] = $events;

$tpl->assign('organizer', $selectedOrganizer);
$tpl->display('moderation.tpl');

startSidebar();

?>
<div class = "infobox">
<ul>

<li><a href = "moderation.php" class = "button">SKIP</a></li>
<li><a href = "moderation.php?updateLastChecked=<?php echo $selectedOrganizer['id']; ?>" class = "button">NO EVENTS</a></li>
<li><a href = "moderation.php?stale=<?php echo $selectedOrganizer['id']; ?>">MARK STALE</a></li>

</ul>

</div>
<?php

require_once 'includes/widgets/footer.php';

?>
