<?php

function moderationDiscordRowClass(?string $url): string
{
    $u = trim((string) $url);
    if ($u === '') {
        return 'bad';
    }
    $l = strtolower($u);
    if (
        strpos($l, 'discord.gg') !== false
        || strpos($l, 'discord.com') !== false
        || strpos($l, 'discordapp.com') !== false
    ) {
        return '';
    }

    return 'warn';
}

function moderationSteamRowClass(?string $url): string
{
    $u = trim((string) $url);
    if ($u === '') {
        return 'bad';
    }
    $l = strtolower($u);
    if (
        strpos($l, 'steamcommunity.com') !== false
        || strpos($l, 'steampowered.com') !== false
        || strpos($l, 's.team') !== false
        || strpos($l, 'steam://') !== false
    ) {
        return '';
    }

    return 'warn';
}

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

$oid = (int) $selectedOrganizer['id'];
$logoFs = __DIR__ . '/resources/images/organizer-logos/' . $oid . '.jpg';
$faviconFs = __DIR__ . '/resources/images/organizer-favicons/' . $oid . '.png';
$selectedOrganizer['logoFileExists'] = is_file($logoFs);
$selectedOrganizer['faviconFileExists'] = is_file($faviconFs);
$selectedOrganizer['useFaviconEnabled'] = !empty((int) ($selectedOrganizer['useFavicon'] ?? 0));
$selectedOrganizer['discordInviteRowClass'] = moderationDiscordRowClass($selectedOrganizer['discordInviteUrl'] ?? null);
$selectedOrganizer['steamGroupRowClass'] = moderationSteamRowClass($selectedOrganizer['steamGroupUrl'] ?? null);
$selectedOrganizer['logoRowClass'] = $selectedOrganizer['logoFileExists'] ? '' : 'bad';
$selectedOrganizer['faviconRowClass'] = '';
if ($selectedOrganizer['useFaviconEnabled'] && !$selectedOrganizer['faviconFileExists']) {
    $selectedOrganizer['faviconRowClass'] = 'bad';
}

$events = 'SELECT e.id, e.title, e.dateStart, e.dateFinish, u.id as uid, u.username, v.id AS venueId, v.title AS venueTitle FROM events e LEFT JOIN venues v ON e.venue = v.id LEFT JOIN users u on e.createdBy = u.id WHERE e.organizer = :organizer ORDER BY e.dateStart DESC';
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
<li><a href = "formHandler.php?formClazz=FormEditOrganizer&amp;formEditOrganizer-id=<?php echo (int) $selectedOrganizer['id']; ?>" class = "button">Edit organizer</a></li>
<li><a href = "formHandler.php?formClazz=FormNewEvent&amp;formNewEvent-organizer=<?php echo (int) $selectedOrganizer['id']; ?>" class = "button">Create new event</a></li>
<li><a href = "moderation.php?updateLastChecked=<?php echo $selectedOrganizer['id']; ?>" class = "button">NO EVENTS</a></li>
<li><a href = "moderation.php?stale=<?php echo $selectedOrganizer['id']; ?>">MARK STALE</a></li>

</ul>

</div>
<?php

require_once 'includes/widgets/footer.php';

?>
