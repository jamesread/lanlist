<?php

require_once 'includes/widgets/header.php';

requirePriv('GROUPLIST');

$sql = 'SELECT g.id, g.title FROM groups g';
$stmt = DatabaseFactory::getInstance()->prepare($sql);
$stmt->execute();
$listGroups = $stmt->fetchAll();

$tpl->assign('listGroups', $listGroups);
$tpl->display('listGroups.tpl');

require_once 'includes/widgets/footer.php';

?>
