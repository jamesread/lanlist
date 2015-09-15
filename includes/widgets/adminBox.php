<?php

require_once 'includes/classes/EventsChecker.php';

$organizer = Session::getUser()->getData('organization');

$ll = new HtmlLinksCollection('Super Menu v2.1');
$ll->setDefaultIcon('go-next.png');

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

$ll->addIfPriv('ADMIN_SYSTEM', null, 'System', 'emblem-system.png');
$menuSystem = $ll->addChildCollection('System');

$issuesChecker = new EventsChecker();
$issuesChecker->checkAllEvents();
$eventIssuesNotification = $issuesChecker->getCount();
$eventIssuesNotification = empty($eventIssuesNotification) ? null : '<strong>(' . $eventIssuesNotification . ')</strong>';
$menuSystem->add('siteChecks.php', 'Site checks ' . $eventIssuesNotification);
$menuSystem->add('listSchedulerTasks.php', 'Scheduler', 'time.png');
$menuSystem->add('listUsers.php', 'Users', 'system-users.png');
$menuSystem->add('listGroups.php', 'Groups', 'system-users.png');
$menuSystem->add('listNews.php', 'News', 'news.png');

$newLogNotification = getCountUnreadLogs();
$newLogNotification = empty($newLogNotification) ? null : '<strong>(' . $newLogNotification . ')</strong>';
$menuSystem->add('listLogs.php', 'Logs ' . $newLogNotification, 'log.png');

$ll->addIfPriv('ADMIN_GOOGLE_ACCOUNTS', null, 'External');
$menuExternalLinks = $ll->addChildCollection('External');
$menuExternalLinks->add('http://mail.lanlist.org', 'lanlist.org Gmail', 'gmail.png');
$menuExternalLinks->add('http://google.com/a/lanlist.org', 'lanlist.org Google Admin', 'google.png');

$tpl->assign('linkCollection', $ll);
$tpl->display('linkCollection.tpl');

?>
