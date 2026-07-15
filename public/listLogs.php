<?php

define('TITLE', 'Event log');
define('MAIN_NOPADDING', true);
require_once 'includes/common.php';

use libAllure\Session;
use libAllure\Logger;

requirePriv('VIEW_LOGS', 'You cannot view the logs!');

require_once 'includes/widgets/header.php';

$fullView = isset($_REQUEST['full']);
$excludedEventTypes = lanlistParseExcludedLogEventTypesFromRequest();

if (isset($_REQUEST['ack'])) {
    requirePriv('CLEAR_LOGS');
    $sql = 'UPDATE logs l SET l.isread = 1 ';
    $db->query($sql);

    Logger::messageAudit(
        'Unread logs dismissed by ' . Session::getUser()->getUsername(),
        'CLEAR_LOGS'
    );

    echo '<p>New logs cleared.</p>';
}

if (isset($_REQUEST['test'])) {
    Logger::messageNormal('Testing message.', 'Testing');
}

if ($fullView) {
    echo '<h2>Full Logs</h2>';
} else {
    echo '<h2>New logs</h2>';
}

$query = lanlistBuildLogListQuery($fullView, $excludedEventTypes);
$stmt = $db->prepare($query['sql']);
foreach ($query['params'] as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$logs = $stmt->fetchAll();

foreach ($logs as $k => $log) {
    $logs[$k]['class'] = strtolower($log['priority']);
}

$excludedLogEventTypeFilters = [];
foreach ($excludedEventTypes as $excludedType) {
    $excludedLogEventTypeFilters[] = [
        'name' => $excludedType,
        'removeUrl' => lanlistLogListUrlWithoutExcludedEventType($fullView, $excludedEventTypes, $excludedType),
    ];
}

$tpl->assign('listLogs', $logs);
$tpl->assign('excludedLogEventTypeFilters', $excludedLogEventTypeFilters);
$tpl->assign('excludedLogEventTypes', $excludedEventTypes);
$tpl->assign('listLogsFull', $fullView);
$tpl->assign('logListUrlUnread', lanlistLogListUrl(false, $excludedEventTypes));
$tpl->assign('logListUrlFull', lanlistLogListUrl(true, $excludedEventTypes));
$tpl->assign('logListUrlClearFilters', lanlistLogListUrl($fullView, []));
$tpl->assign('logListConfigJson', json_encode([
    'fullView' => $fullView,
    'excludedEventTypes' => $excludedEventTypes,
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
$tpl->assign('includeLogListFilters', true);
$tpl->display('listLogs.tpl');

startSidebar();

$logListUrlUnread = htmlspecialchars(lanlistLogListUrl(false, $excludedEventTypes), ENT_QUOTES, 'UTF-8');
$logListUrlFull = htmlspecialchars(lanlistLogListUrl(true, $excludedEventTypes), ENT_QUOTES, 'UTF-8');

?>
<div class = "infobox">
    <h2>Log admin</h2>
    
    <ul>
        <li><a href = "<?php echo $logListUrlFull; ?>">Full logs</a></li>
        <li><a href = "api.php?function=logs&format=csv">CSV</a></li>
        <li><a href = "<?php echo $logListUrlUnread; ?>">Unread</a></li>
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
