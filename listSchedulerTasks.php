<?php

require_once 'includes/widgets/header.php';

use \libAllure\Session;

Session::requirePriv('SCHEDULER_LIST');

$sql = 'SELECT className, frequency, lastRunTime FROM scheduler_tasks';
$stmt = $db->prepare($sql);
$stmt->execute();

$tpl->assign('listScheduledTasks', $stmt->fetchAll());
$tpl->display('listScheduledTasks.tpl');

startSidebar();
require_once 'includes/widgets/adminBox.php';
require_once 'includes/widgets/footer.php';

?>
