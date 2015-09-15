<?php

set_include_path(get_include_path() . PATH_SEPARATOR . '../');

require_once 'includes/common.php';
require_once 'includes/classes/EventsChecker.php';

$checker = new EventsChecker();
$checker->checkAllEvents();

$events = $checker->getEventsList();

echo json_encode($events);


?>
