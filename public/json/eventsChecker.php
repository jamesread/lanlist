<?php

die("disabled until we have auth");

set_include_path(get_include_path() . PATH_SEPARATOR . '../');

require_once 'includes/common.php';
require_once 'includes/classes/EventsChecker.php';

requirePriv('SITE_CHECKS');

$checker = new EventsChecker();
$checker->checkAllEvents();

$events = $checker->getEventsList();

outputJson($events);

