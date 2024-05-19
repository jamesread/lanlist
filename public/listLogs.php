<?php

define('TITLE', 'Event log');
require_once 'includes/common.php';

use \libAllure\Session;
use \libAllure\Logger;

requirePriv('VIEW_LOGS', 'You cannot view the logs!');

require_once 'includes/widgets/header.php';

if (isset($_REQUEST['ack'])) {
        requirePriv('CLEAR_LOGS');
	$sql = 'UPDATE logs l SET l.isread = 1 ';
	$db->query($sql);

	echo '<p>New logs cleared.</p>';
}

if (isset($_REQUEST['test'])) {
	Logger::messageNormal('Testing message.', 'Testing');
}

if (isset($_REQUEST['full'])) {
	echo '<h2>Full Logs</h2>';

	$sql = 'SELECT l.id, l.eventType, l.timestamp, l.content, l.priority FROM logs l ORDER BY l.id DESC LIMIT 100';
} else {
	echo '<h2>New logs</h2>';

	$sql = 'SELECT l.id, l.eventType, l.timestamp, l.content, l.priority FROM logs l WHERE l.isread = 0 ORDER BY l.id DESC';
}

$logs = $db->query($sql)->fetchAll();

foreach ($logs as $k => $log) {
    $logs[$k]['class'] = strtolower($log['priority']);
}

$tpl->assign('listLogs', $logs);
$tpl->display('listLogs.tpl');

startSidebar();

?>
<div class = "infobox">
	<h2>Log admin</h2>
	
	<ul>
		<li><a href = "listLogs.php?full">Full logs</a></li>
		<li><a href = "api.php?function=logs&format=csv">CSV</a></li>
                <li><a href = "listLogs.php">Unread</a></li>
<?php 

if (Session::hasPriv('CLEAR_LOGS')) {

		echo '<li><a href = "listLogs.php?ack">Dismiss new logs</a></li>';
}
?>
		<li><a href = "account.php">Return to account</a></li>
		<li><a href = "listLogs.php?test">Test message</a></li>
	</ul>
</div>
<?php

require_once 'includes/widgets/footer.php';

?>
