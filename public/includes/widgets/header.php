<?php

require_once 'includes/common.php';

global $tpl;

if (defined('TITLE')) {
    $tpl->assign('title', TITLE);
}

$tpl->assign('mainNopadding', defined('MAIN_NOPADDING'));

use libAllure\Session;

$tpl->assign('alertMessage', ALERT_MESSAGE);
$tpl->assign('siteTitle', SITE_TITLE);
$tpl->assign('siteTitleDomain', SITE_TITLE_DOMAIN);
$tpl->assign('siteTitleTld', SITE_TITLE_TLD);
$tpl->assign('isLoggedIn', Session::isLoggedIn());
$tpl->assign('isModerator', Session::hasPriv('MODERATOR'));
$tpl->assign('username', Session::isLoggedIn() ? Session::getUser()->getUsername() : 'Guest');
$tpl->assign('mapsApiKey', MAPS_API_KEY);
//$tpl->register_modifier('floatToMoney', 'floatToMoney');
$tpl->display('header.tpl');
