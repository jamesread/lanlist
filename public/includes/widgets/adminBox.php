<?php

require_once 'includes/classes/EventsChecker.php';

use libAllure\Session;
use libAllure\HtmlLinksCollection;

$organizer = Session::getUser()->getData('organization');

$ll = new HtmlLinksCollection('Site links');
$ll->setDefaultIcon('go-next.png');
$ll->add('#', 'History');

$menuHistory = $ll->addChildCollection('History');

if (isset($_SESSION['history']) && is_array($_SESSION['history'])) {
    foreach (array_reverse($_SESSION['history']) as $link) {
        $menuHistory->add($link['url'], $link['title']);
    }
}

$ll->addIfPriv('MODERATE_ORGANIZERS', 'listOrganizers.php', 'Organizers', 'system-users.png');
$menuOrganizer = $ll->addChildCollection('Organizers');
$menuOrganizer->add('listOrganizers.php', 'List Organizers', 'edit-find.png');
$menuOrganizer->addIfPriv('CREATE_ORGANIZER', 'formHandler.php?formClazz=FormNewOrganizer', 'Create organizer', 'create.png');

$countJoinRequests = getCountJoinRequests();
$joinRequestsNotification = ($countJoinRequests == 0) ? null : '<strong>(' . $countJoinRequests . ')</strong>';
$menuOrganizer->addIfPriv('JOIN_REQUESTS', 'joinRequests.php', 'Join requests ' . $joinRequestsNotification);

$ll->addIfPriv('MODERATE_VENUES', 'listVenues.php', 'Venues', 'go-home.png');
$menuVenues = $ll->addChildCollection('Venues');
$menuVenues->addIf(!empty($organizer), 'viewOrganizer.php?id=' . $organizer, 'My venues', 'edit-find.png');
$menuVenues->add('listVenues.php', 'List venues', 'edit-find.png');
$menuVenues->add('formHandler.php?formClazz=FormNewVenue', 'Create venue', 'create.png');

$ll->addIfPriv('MODERATE_EVENTS', 'eventsList.php', 'Events', 'office-calendar.png');
$menuEvents = $ll->addChildCollection('Events');
$menuEvents->addIf(!empty($organizer), 'viewOrganizer.php?id=' . $organizer, 'My events', 'edit-find.png');
$menuEvents->add('eventsList.php', 'List events', 'edit-find.png');
$menuEvents->add('formHandler.php?formClazz=FormNewEvent', 'Create event', 'create.png');

$ll->addIfPriv('SYSTEM_MENU', null, 'System', 'emblem-system.png');
$menuSystem = $ll->addChildCollection('System');

$issuesChecker = new EventsChecker();
$issuesChecker->checkAllEvents();
$eventIssuesNotification = $issuesChecker->getCount();
$eventIssuesNotification = empty($eventIssuesNotification) ? null : '<strong>(' . $eventIssuesNotification . ')</strong>';
$menuSystem->addIfPriv('SITE_CHECKS', 'siteChecks.php', 'Site checks ' . $eventIssuesNotification);
$menuSystem->addIfPriv('SCHEDULER_VIEW', 'listSchedulerTasks.php', 'Scheduler', 'time.png');
$menuSystem->addIfPriv('USERLIST', 'listUsers.php', 'Users', 'system-users.png');
$menuSystem->addIfPriv('GROUPLIST', 'listGroups.php', 'Groups', 'system-users.png');
$menuSystem->addIfPriv('SUPERUSER', 'formHandler.php?formClazz=FormCreatePermission', 'Create Permission');
$menuSystem->addIfPriv('NEWSLIST', 'listNews.php', 'News', 'news.png');

$newLogNotification = getCountUnreadLogs();
$newLogNotification = empty($newLogNotification) ? null : '<strong>(' . $newLogNotification . ')</strong>';
$menuSystem->add('listLogs.php', 'Logs ' . $newLogNotification, 'log.png');

$ll->addIfPriv('ADMIN_GOOGLE_ACCOUNTS', null, 'External');
$menuExternalLinks = $ll->addChildCollection('External');
$menuExternalLinks->add('http://mail.lanlist.org', 'lanlist.org Gmail', 'gmail.png');
$menuExternalLinks->add('http://google.com/a/lanlist.org', 'lanlist.org Google Admin', 'google.png');

$tpl->assign('linkCollection', $ll);
$tpl->display('linkCollection.tpl');
