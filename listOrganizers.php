<?php

define('TITLE', 'Organizers of LAN Parties');
require_once 'includes/widgets/header.php';

use \libAllure\Session;

if (Session::isLoggedIn() && Session::getUser()->hasPriv('SUPERUSER')) {
	$sql = 'SELECT o.id, o.published, o.title, o.websiteUrl, count(e.id) AS eventCount, u.username, u.id AS userId FROM organizers o LEFT JOIN events e ON e.organizer = o.id LEFT JOIN users u ON u.organization = o.id GROUP BY o.id ORDER BY o.title';
} else {
	$sql = 'SELECT o.id, o.published, o.title, o.websiteUrl, count(e.id) AS eventCount, u.username, u.id AS userId FROM organizers o LEFT JOIN events e ON e.organizer = o.id LEFT JOIN users u ON u.organization = o.id WHERE o.published = 1 GROUP BY o.id ORDER BY o.title';
}

$stmt = $db->prepare($sql);
$stmt->execute();

$tpl->assign('listOrganizers', $stmt->fetchAll());
$tpl->display('listOrganizers.tpl');

startSidebar();
require_once 'includes/widgets/infoboxListFilter.php';
require_once 'includes/widgets/infoboxFeaturedOrganizer.php';
$tpl->display('infobox.otherFormats.tpl');

if (Session::hasPriv('NEW_ORGANIZER')) {
	echo '<div>';
	echo '<a href = "formHandler.php?formClazz=FormNewOrganizer">New Organizer</a>';
	echo '</div>';
}

require_once 'includes/widgets/footer.php';

?>
