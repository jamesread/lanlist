<?php

require_once 'includes/common.php';

requirePriv('USERLIST');

require_once 'includes/widgets/header.php';

$sql = 'SELECT u.id, u.username, u.email, u.group, u.organization, u.lastLogin, u.registered, g.title AS groupTitle, o.title AS organizationTitle FROM users u LEFT JOIN groups g ON u.group = g.id LEFT JOIN organizers o ON u.organization = o.id';
$stmt = $db->prepare($sql);
$stmt->execute();

$tpl->assign('listUsers', $stmt->fetchAll());
$tpl->display('listUsers.tpl');

startSidebar();
require_once 'includes/widgets/adminBox.php';
require_once 'includes/widgets/footer.php';
