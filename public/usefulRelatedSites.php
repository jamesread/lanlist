<?php

define('TITLE', 'Useful related sites');
define('META_DESCRIPTION', 'Other websites, wikis, and community resources related to LAN parties and local gaming events.');
require_once 'includes/widgets/header.php';

$tpl->assign('relatedSiteGroups', lanlistFetchUsefulRelatedSiteGroupsForDisplay());
$tpl->display('usefulRelatedSites.tpl');

startSidebar();

if (libAllure\Session::hasPriv('MANAGE_LINKS')) {
    echo '<div class="infobox"><h2>Link admin</h2>';
    echo '<p><a href="listUsefulRelatedSites.php">Manage related site links</a></p>';
    echo '</div>';
}

require_once 'includes/widgets/infoboxFeaturedOrganizer.php';

require_once 'includes/widgets/footer.php';
