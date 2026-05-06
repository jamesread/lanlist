<?php

define('TITLE', 'Link to us');
define('META_DESCRIPTION', 'Buttons, badges, and HTML snippets to link to lanlist from your LAN or community site.');
require_once 'includes/widgets/header.php';

$tpl->display('linkUs.tpl');

startSidebar();

require_once 'includes/widgets/infoboxFeaturedOrganizer.php';


require_once 'includes/widgets/footer.php';
