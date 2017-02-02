<?php

require_once 'includes/widgets/header.php';
$groupId = san()->filterUint('id');

$sql = 'SELECT g.id, g.title FROM groups g WHERE g.id = :id';
$stmt = stmt($sql);
$stmt->bindValue(':id', $groupId);
$stmt->execute();

$tpl->assign('itemGroup', $stmt->fetchRow());

$sql = 'SELECT u.id, "secondary" as source, u.username FROM group_memberships m LEFT JOIN users u ON m.user = u.id WHERE m.group = :id1 UNION SELECT u.id, "primary" as source, u.username FROM users u WHERE u.group = :id2';
$stmt = stmt($sql);
$stmt->bindValue(':id1', $groupId);
$stmt->bindValue(':id2', $groupId);
$stmt->execute();

$tpl->assign('listMembers', $stmt->fetchAll());

$sql = 'SELECT p.`key`, p.description FROM privileges_g gp LEFT JOIN permissions p ON gp.permission = p.id WHERE gp.group = :gid';
$stmt = stmt($sql);
$stmt->bindValue(':gid', $groupId);
$stmt->execute();

$tpl->assign('listPrivileges', $stmt->fetchAll());
$tpl->display('viewGroup.tpl');

require_once 'includes/widgets/footer.php';

?>
