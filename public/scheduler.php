<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    exit;
}

require_once 'includes/common.php';
require_once 'includes/classes/ScheduledTaskNewsletter.php';
require_once 'includes/classes/ScheduledTaskKeepalive.php';

use libAllure\Scheduler;

$s = new Scheduler($db);

if (in_array('--force', $_SERVER['argv'])) {
    $s->executeEverything();
} else {
    $s->executeOverdueJobs();
}
