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
$tpl->assign('siteBaseUrl', SITE_BASE_URL);
$canonicalUrl = seoCurrentPageUrl();
$tpl->assign('canonicalUrl', $canonicalUrl);
$socialBanner = rtrim(SITE_BASE_URL, '/') . '/resources/images/social-banner.png';
$tpl->assign(
    'socialImageUrl',
    defined('META_OG_IMAGE') ? META_OG_IMAGE : $socialBanner
);
$tpl->assign(
    'metaDescription',
    defined('META_DESCRIPTION') ? META_DESCRIPTION : seoDefaultMetaDescription()
);
$tpl->assign('ogType', defined('META_OG_TYPE') ? META_OG_TYPE : 'website');
$tpl->assign(
    'metaRobots',
    defined('META_ROBOTS') ? META_ROBOTS : null
);
$tpl->assign('isLoggedIn', Session::isLoggedIn());
$tpl->assign('isModerator', Session::hasPriv('MODERATOR'));
$tpl->assign(
    'canPublishOrganizer',
    Session::isLoggedIn()
        && (Session::hasPriv('PUBLISH_ORGANIZERS') || Session::hasPriv('MODERATOR'))
);
$tplVars = $tpl->getTemplateVars();
$pageIncludeInlineEdit = is_array($tplVars) && !empty($tplVars['includeInlineEdit']);
$tpl->assign(
    'includeInlineEdit',
    $pageIncludeInlineEdit || Session::hasPriv('MODERATOR')
);
$tpl->assign('username', Session::isLoggedIn() ? Session::getUser()->getUsername() : 'Guest');

$includeMaps = defined('INCLUDE_GOOGLE_MAPS') ? (bool)INCLUDE_GOOGLE_MAPS : false;
$tplVars = $tpl->getTemplateVars();
if (is_array($tplVars) && array_key_exists('includeGoogleMaps', $tplVars)) {
    $includeMaps = (bool)$tplVars['includeGoogleMaps'];
}
$tpl->assign('includeGoogleMaps', $includeMaps);
if ($includeMaps) {
    $tpl->assign('mapsApiKey', MAPS_API_KEY);
}

//$tpl->register_modifier('floatToMoney', 'floatToMoney');
$tpl->display('header.tpl');
