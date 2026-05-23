<?php

require_once 'includes/common.php';
require_once 'includes/functionality/async_jobs.php';
require_once 'includes/functionality/olivetin.php';

requirePriv('SCHEDULER_LIST');

require_once 'includes/widgets/header.php';

$newsletterWatermark = lanlistFetchNewsletterLastRunTime();

$tpl->assign('listAsyncJobs', lanlistFetchAsyncJobsForAdminList(100));
$tpl->assign('newsletterWatermark', $newsletterWatermark);
$tpl->assign('oliveTinConnection', lanlistOliveTinConnectionTest());
$tpl->display('listAsyncJobs.tpl');

startSidebar();
require_once 'includes/widgets/adminBox.php';
require_once 'includes/widgets/footer.php';
