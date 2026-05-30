<?php

require_once 'includes/common.php';

use libAllure\Session;

$organizer = fetchOrganizer(fromRequestRequireInt('id'));
addHistoryLink('viewOrganizer.php?id=' . $organizer['id'], 'Viewed: ' . $organizer['title']);

$canSeeOrganizerInternals = Session::isLoggedIn() && (
    Session::getUser()->hasPriv('SUPERUSER')
    || Session::getUser()->hasPriv('MODERATOR')
    || Session::getUser()->getData('organization') == $organizer['id']
);

if (!lanlistOrganizerIsPubliclyVisible($organizer) && !$canSeeOrganizerInternals) {
    throw new Exception('Organizer not found');
}

define('TITLE', 'Organizer: ' . $organizer['title']);
define('META_DESCRIPTION', seoOrganizerMetaDescription($organizer));

$ogImageAbs = seoOrganizerOpenGraphAbsoluteImageUrl((int)$organizer['id']);

if ($ogImageAbs !== null) {
    define('META_OG_IMAGE', $ogImageAbs);
}

$tpl->assign('includeInlineEdit', Session::hasPriv('MODERATOR'));

require_once 'includes/widgets/header.php';

$organizer['logoUrl'] = !empty((int) ($organizer['validBanner'] ?? 0))
    ? getOrganizerLogoUrl($organizer['id'])
    : null;
applyOrganizerPlatformInviteHrefs($organizer);

if (Session::hasPriv('MODERATOR')) {
    require_once __DIR__ . '/includes/functionality/moderation.php';
    require_once __DIR__ . '/includes/functionality/async_jobs.php';

    $organizer = lanlistEnrichOrganizerForModeratorView($organizer);
    $organizer['published'] = (int) ($organizer['published'] ?? 0);
    $oid = (int) $organizer['id'];
    $tpl->assign('latestFaviconAsyncJob', lanlistFetchLatestOrganizerFaviconAsyncJobForDisplay($oid));
    $tpl->assign('hasActiveFaviconAsyncJob', lanlistSelectActiveOrganizerFaviconJob($oid) !== false);
}

$tpl->assign('organizer', $organizer);

$events = fetchEventsFromOrganizerId($organizer['id']);
$tpl->assign('events', $events);

if ($canSeeOrganizerInternals) {
    $sql = 'SELECT u.id, u.username, u.lastLogin FROM users u WHERE u.organization = :organizer';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':organizer', $organizer['id']);
    $stmt->execute();

    $tpl->assign('associatedUsers', $stmt->fetchAll());
} else {
    $organizer['genericEmail'] = null;
}

$tpl->assign('userlist', Session::hasPriv('USERLIST'));
$tpl->display('viewOrganizer.tpl');

startSidebar();

$nextEvent = getNextEvent($organizer['id']);

echo '<div class = "infobox"><h2>Next event for ' . $organizer['title'] . '</h2>';
if (empty($nextEvent)) {
    echo '<p style = "color:red">To the best of our knowledge, ' . $organizer['title'] . ' has nothing planned... :(</p>';
} else {
    echo '<p>Next event is <a href = "viewEvent.php?id=' . $nextEvent['id'] . '">' . $nextEvent['title'] . '</a></p>';
}

echo '<p>You may find another organizer near you on the <a href = "eventsMap.php">map</a> or from the <a href = "listOrganizers.php">list of organizers</a>.</p>';
echo '</div>';

if (Session::isLoggedIn() && Session::getUser()->hasPriv('EDIT_ORGANIZER') || Session::isLoggedIn() && Session::getUser()->getData('organization') == $organizer['id']) {
    echo '<div class = "infobox">';
    echo '<h2>Organizer admin</h2>';
    echo '<ul>';
    echo '<li><a href = "formHandler.php?formClazz=FormNewEvent&formNewEvent-organizer=' . $organizer['id'] .  '">New Event</a></li>';
    echo '<li><a href = "formHandler.php?formClazz=FormNewVenue&formNewVenue-organizer=' . $organizer['id'] .  '">New Venue</a></li>';
    echo '<li><a href = "misc.php?action=deleteOrganizer&id=' . $organizer['id'] . '">Delete</a>';
    echo '<li><a href = "formHandler.php?formClazz=FormEditOrganizer&amp;formEditOrganizer-id=' . $organizer['id'] . '">Edit organizer details</a></li>';
    echo '<li><a href = "listOrganizers.php">Organizer list</a></li>';
    echo '</ul></div>';
}

if (Session::hasPriv('MODERATOR') && empty($nextEvent)) {
    $organizerId = (int) $organizer['id'];
    echo '<div class="infobox">';
    echo '<h2>Moderation</h2>';
    echo '<ul>';
    echo '<li><a href="misc.php?action=updateOrganizerLastChecked&amp;id=' . $organizerId . '&amp;return=organizer">No next events</a></li>';
    echo '</ul></div>';
}

if (Session::hasPriv('MODERATOR')) {
    $tpl->assign('includeInlineEdit', true);
}

require_once 'includes/widgets/footer.php';
