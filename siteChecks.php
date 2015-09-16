<?php

require_once 'includes/common.php';

define('TITLE', 'Site Checks' . $organizer['title']);

require_once 'includes/widgets/header.php';
require_once 'includes/classes/EventsChecker.php';

if (!Session::getUser()->hasPriv('SUPERUSER')) {
	throw new PermissionException();
}

$checker = new EventsChecker();
$checker->checkAllEvents();
$events = $checker->getEventsList();

$tpl->assign('listEventsWithIssues', $events);
$tpl->display('eventsWithIssues.tpl');

$sql = 'SELECT o.id, o.title, o.websiteUrl, o.lastChecked, count(u.id) assUserCount FROM organizers o LEFT JOIN users u ON u.organization = o.id AND u.email IS NOT NULL LEFT JOIN events e ON o.id = e.organizer AND e.dateFinish > now() WHERE e.id IS null GROUP BY o.id ORDER BY o.title';
$result = $db->query($sql);
$orgies = $result->fetchAll();

$tpl->assign('listOrganizers', $orgies);
$tpl->display('organizersWithNoEvents.tpl');

startSidebar();
require_once 'includes/widgets/adminBox.php';
require_once 'includes/widgets/footer.php';

?>
