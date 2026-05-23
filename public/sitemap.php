<?php

define('TITLE', 'Sitemap');
define('META_DESCRIPTION', 'Human-readable sitemap of lanlist pages with links to events, lists, account pages, and the XML sitemap.');
require_once 'includes/widgets/header.php';

$eventsListCountries = [];
foreach (getCountriesWithUpcomingEventCounts() as $row) {
    $country = (string)$row['country'];
    if (getCountryFlagHtml($country) !== '') {
        $eventsListCountries[] = $country;
    }
}
$tpl->assign('eventsListCountries', $eventsListCountries);

$tpl->display('sitemap.tpl');

startSidebar();

require_once 'includes/widgets/infoboxFeaturedOrganizer.php';

require_once 'includes/widgets/footer.php';
