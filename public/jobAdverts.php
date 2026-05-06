<?php

define('TITLE', 'Job adverts');
define('META_DESCRIPTION', 'Job and volunteer opportunities shared by the LAN party community via lanlist.');
require_once 'includes/widgets/header.php';

$tpl->display('jobAdverts.tpl');

startSidebar();
require_once 'includes/widgets/infoboxFeaturedOrganizer.php';
require_once 'includes/widgets/footer.php';
