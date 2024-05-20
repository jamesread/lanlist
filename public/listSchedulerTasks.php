<?php

require_once 'includes/common.php';

requirePriv('SCHEDULER_LIST');

require_once 'includes/widgets/header.php';

$sql = 'SELECT className, frequency, lastRunTime FROM scheduler_tasks';
$stmt = $db->prepare($sql);
$stmt->execute();

$tpl->assign('listScheduledTasks', $stmt->fetchAll());
$tpl->display('listScheduledTasks.tpl');

startSidebar();
require_once 'includes/widgets/adminBox.php';
require_once 'includes/widgets/footer.php';
