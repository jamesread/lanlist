<?php

require_once 'includes/common.php';
require_once 'includes/functionality/lanpartydb_export.php';

use libAllure\Logger;
use libAllure\Session;

if (!Session::isLoggedIn()) {
    redirect('login.php', 'You need to login to export lanpartydb data.');
}

$organizerId = (int) Session::getUser()->getData('organization');
if ($organizerId <= 0) {
    redirect('account.php', 'Link your account to an organizer before exporting to the OrgaTalk LAN Party Database.');
}

if (!lanpartydbUserCanExportForOrganizer($organizerId)) {
    throw new Exception('You do not have permission to export data for this organizer.');
}

$organizer = fetchOrganizer($organizerId);
$events = lanpartydbFetchOrganizerEventsForExport($organizerId);
$export = lanpartydbBuildExportPackage($organizer, $events);

Logger::messageAudit(
    'OrgaTalk export page viewed for organizer ' . $organizer['title']
    . ' (' . $organizerId . ') by ' . Session::getUser()->getUsername()
    . '; ' . (int) $export['party_count'] . ' eligible party file(s)',
    'VIEW_ORGATALK_EXPORT',
    ['relatedOrganizer' => $organizerId]
);

define('TITLE', 'Export to OrgaTalk LAN Party Database');
define(
    'META_DESCRIPTION',
    'Generate TOML files for contributing your LAN party history to the OrgaTalk LAN Party Database on GitHub.'
);

require_once 'includes/widgets/header.php';

$tpl->assign('organizer', $organizer);
$tpl->assign('export', $export);
$tpl->assign('lanpartydbRepoUrl', LANPARTYDB_REPO_URL);
$tpl->assign('lanpartydbFormatUrl', LANPARTYDB_FORMAT_URL);
$tpl->display('exportLanpartydb.tpl');

startSidebar();
require_once 'includes/widgets/adminBox.php';
require_once 'includes/widgets/footer.php';
