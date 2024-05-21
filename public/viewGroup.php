<?php

require_once 'includes/common.php';
use libAllure\Shortcuts;
use libAllure\Session;
use libAllure\HtmlLinksCollection;

requirePriv('VIEW_GROUP');

require_once 'includes/widgets/header.php';

$groupId = \libAllure\Sanitizer::getInstance()->filterUint('id');

$sql = 'SELECT g.id, g.title FROM groups g WHERE g.id = :id';
$stmt = Shortcuts::stmt($sql);
$stmt->bindValue(':id', $groupId);
$stmt->execute();

$tpl->assign('itemGroup', $stmt->fetchRow());

$sql = 'SELECT u.id, "secondary" as source, u.username FROM group_memberships m LEFT JOIN users u ON m.user = u.id WHERE m.group = :id1 UNION SELECT u.id, "primary" as source, u.username FROM users u WHERE u.group = :id2';
$stmt = Shortcuts::stmt($sql);
$stmt->bindValue(':id1', $groupId);
$stmt->bindValue(':id2', $groupId);
$stmt->execute();

$tpl->assign('listMembers', $stmt->fetchAll());

$sql = 'SELECT p.`key`, p.description FROM privileges_g gp LEFT JOIN permissions p ON gp.permission = p.id WHERE gp.group = :gid';
$stmt = Shortcuts::stmt($sql);
$stmt->bindValue(':gid', $groupId);
$stmt->execute();

$tpl->assign('listPrivileges', $stmt->fetchAll());
$tpl->display('viewGroup.tpl');

startSidebar();

if (Session::getUser()->hasPriv('GROUP_ADMIN')) {
    $menu = new HtmlLinksCollection('Group management');
    $menu->add('formHandler.php?formClazz=FormAddPermissionToGroup&formPrivsAddGroup-usergroup=' . $groupId, 'Add Permission');

    $tpl->assign('linkCollection', $menu);
    $tpl->display('linkCollection.tpl');
}


require_once 'includes/widgets/footer.php';
