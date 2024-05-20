<?php

require_once 'includes/common.php';

use libAllure\Session;

if (!Session::isLoggedIn()) {
    redirect('login.php', 'You need to login to view your account.');
}

define('TITLE', 'My Account');
require_once 'includes/widgets/header.php';

$organizer = Session::getUser()->getData('organization');
if (!empty($organizer)) {
    $organization = fetchOrganizer(Session::getUser()->getData('organization'));
    $tpl->assign('organization', $organization);
}

$tpl->assign('userEmail', Session::getUser()->getData('email'));
$tpl->assign('usernameSteam', Session::getUser()->getData('usernameSteam'));
$tpl->assign('usernameDiscord', Session::getUser()->getData('discordUser'));
$tpl->display('account.tpl');

    startSidebar();
    require_once 'includes/widgets/adminBox.php';


require_once 'includes/widgets/footer.php';
