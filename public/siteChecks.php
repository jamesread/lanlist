<?php

require_once 'includes/common.php';
require_once 'includes/functionality/site_checks.php';
require_once 'includes/functionality/moderator_metrics.php';

use libAllure\Session;

requirePriv('MODERATOR');

define('TITLE', 'Moderator control panel');
$tpl->assign('includeInlineEdit', true);
require_once 'includes/widgets/header.php';

$panel = lanlistFetchModeratorPanelData();
$moderatorImpact = lanlistFetchModeratorImpactMetrics(
    (int) Session::getUser()->getId(),
    Session::getUser()->getUsername(),
    $panel
);
$tpl->assign('moderatorImpact', $moderatorImpact);
$tpl->display('moderatorControlPanel.tpl');
$tpl->display('moderatorImpactPanel.tpl');

$tpl->assign('listEventsWithIssues', $panel['eventsWithIssues']);
$tpl->display('eventsWithIssues.tpl');

$tpl->assign('listEventsWithSilencedTicketWarning', $panel['eventsWithSilencedTicketWarning']);
$tpl->display('eventsWithSilencedTicketWarning.tpl');

$tpl->assign('listUnpublishedOrganizers', $panel['unpublishedOrganizers']);
$tpl->display('unpublishedOrganizers.tpl');

$tpl->assign('listOrganizers', $panel['organizersWithNoEvents']);
$tpl->display('organizersWithNoEvents.tpl');

startSidebar();
require_once 'includes/widgets/adminBox.php';
require_once 'includes/widgets/footer.php';
