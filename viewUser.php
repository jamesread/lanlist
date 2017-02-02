<?php

require_once 'includes/common.php';

use \libAllure\Session;
use \libAllure\HtmlLinksCollection;

requirePriv('USERLIST');

$id = fromRequestRequireInt('id');
$sql = 'SELECT u.id, u.username, u.usernameSteam, g.title AS groupTitle, u.email, u.lastLogin, u.registered, o.id AS organizerId, o.title AS organizerTitle FROM users u JOIN groups g ON u.group = g.id LEFT JOIN organizers o ON u.organization = o.id WHERE u.id = :id';
$stmt = $db->prepare($sql);
$stmt->bindValue(':id', $id);
$stmt->execute();

if ($stmt->numRows() == 0) {
	throw new Exception('user not found');
}

$user = $stmt->fetchRow();

define('TITLE', 'User: ' . $user['username']);
require_once 'includes/widgets/header.php';

echo '<h2>User: ' . $user['username'] . '</h2>';
echo 'Steam username: ' . (empty($user['usernameSteam']) ? '???' : $user['usernameSteam']) . '<br />';
echo 'Primary group: ' . $user['groupTitle'] . '<br />';
echo 'Last login: ' . $user['lastLogin'] . '<br />';
echo 'Registered: ' . issetor($user['registered']) . '<br />';
echo 'Email: ' . issetor($user['email']) . '<br />';

if (!empty($user['organizerId'])) {
	echo 'Organizer: <a href = "viewOrganizer.php?id=' . $user['organizerId'] . '">' . $user['organizerTitle'] . '</a>';
} else {
	echo 'Organizer: None<br />';
}

if (Session::getUser()->hasPriv('USER_EMAIL_LOG')) {
	$sql = 'SELECT l.id, l.sent, l.subject FROM email_log l WHERE l.emailAddress = :emailAddress ORDER BY l.sent DESC LIMIT 10';
	$stmt = stmt($sql);
	$stmt->bindValue(':emailAddress', $user['email']);
	$stmt->execute();

	$tpl->assign('loggedEmails', $stmt->fetchAll());
	$tpl->display('viewUser.tpl');
}

startSidebar();

if (Session::getUser()->hasPriv('EDIT_USERS')) {
	$menu = new HtmlLinksCollection('User management');
	$menu->add('listUsers.php', 'List Users');
	$menu->add('formHandler.php?formClazz=FormEditUser&amp;formEditUser-uid=' . $user['id'], 'Edit user');
	$menu->add('formHandler.php?formClazz=FormDeleteUser&formDeleteUser-uid=' . $user['id'], 'Delete user');
	$menu->add('formHandler.php?formClazz=FormPrivsUser&formPrivsUser-uid=' . $user['id'], 'User privileges');
	
	if (empty($user['email'])) {
		$menu->add(null, 'Send email - no email address for this user');
	} else {
		$menu->add('formHandler.php?formClazz=FormSendEmailToUser&formSendEmailToUser-uid=' . $user['id'], 'Send email');

		$menuEmail = $menu->addChildCollection('Send email');
		$menuEmail->add('formHandler.php?formClazz=FormSendEmailToUser&formSendEmailToUser-uid=' . $user['id'] . '&template=addYourRecentEvents', 'Template: Nag to add recent events');
	}

	$tpl->assign('linkCollection', $menu);
	$tpl->display('linkCollection.tpl');
}


require_once 'includes/widgets/footer.php';

?>
