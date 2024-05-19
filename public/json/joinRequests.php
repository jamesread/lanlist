<?php

set_include_path(get_include_path() . PATH_SEPARATOR . '../');

require_once 'includes/common.php';
require_once 'includes/classes/EventsChecker.php';

$sql = 'SELECT r.organizer AS organization, r.user AS uid FROM organization_join_requests r';
$stmt = $db->prepare($sql);
$stmt->execute();

$joinRequests = $stmt->fetchAll();

echo json_encode($joinRequests);
