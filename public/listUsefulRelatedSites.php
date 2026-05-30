<?php

require_once 'includes/common.php';

use libAllure\Session;

requirePriv('MANAGE_LINKS');

define('TITLE', 'Related site links');
define('META_DESCRIPTION', 'Manage useful related site links shown on usefulRelatedSites.php.');
require_once 'includes/widgets/header.php';

$tpl->assign('relatedSites', lanlistFetchUsefulRelatedSitesForAdmin());
$tpl->display('listUsefulRelatedSites.tpl');

startSidebar();

echo '<div class="infobox"><h2>Link admin</h2><ul>';
echo '<li><a href="formHandler.php?formClazz=FormNewUsefulRelatedSite">Add link</a></li>';
echo '<li><a href="usefulRelatedSites.php">View public page</a></li>';
echo '<li><a href="account.php">Return to account</a></li>';
echo '</ul></div>';

require_once 'includes/widgets/footer.php';
