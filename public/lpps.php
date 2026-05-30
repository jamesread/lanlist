<?php

require_once 'includes/common.php';
require_once 'includes/functionality/lpps.php';

define('TITLE', 'Lan Party Publishing Standard (LPPS)');
define(
    'META_DESCRIPTION',
    'Optional JSON feed standard for LAN party organizers. Publish events on your site and let lanlist sync them, or add events manually.'
);

require_once 'includes/widgets/header.php';

$tpl->assign('lppsStandardUrl', LANLIST_LPPS_STANDARD_URL);
$tpl->assign('lppsInfoPagePath', lanlistLppsInfoPagePath());
$tpl->display('lpps.tpl');

startSidebar();
require_once 'includes/widgets/infoboxFeaturedOrganizer.php';
require_once 'includes/widgets/footer.php';
