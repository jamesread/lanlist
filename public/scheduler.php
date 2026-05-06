<?php


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
