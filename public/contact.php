<?php

define('TITLE', 'Contact');
define('META_DESCRIPTION', 'Contact the lanlist team for help with listings, corrections, or general questions about the LAN party directory.');
require_once 'includes/widgets/header.php';

$tpl->display('contact.tpl');

startSidebar();

require_once 'includes/widgets/infoboxFeaturedOrganizer.php';

require_once 'includes/widgets/footer.php';
