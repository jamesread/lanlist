<?php

require_once 'includes/widgets/header.php';

use \libAllure\Session;
use \libAllure\HtmlLinksCollection;

$id = fromRequestRequireInt('id');
$venue = fetchVenue($id);

addHistoryLink('viewVenue.php?id=' . $id, 'View venue: ' . $venue['title']);

$tpl->assign('eventsAtVenue', fetchEventsFromVenueId($id));
$tpl->assign('venue', $venue);
$tpl->display('viewVenue.tpl');
startSidebar();

require_once 'includes/widgets/infoboxListFilter.php';

if (Session::isLoggedIn()) {
	$organizer = Session::getUser()->getData('organization');

	if (Session::hasPriv('EDIT_VENUE')) {
		$menu = new HtmlLinksCollection('Venue admin');
		$menu->add('formHandler.php?formClazz=FormEditVenue&amp;formEditVenue-id=' . $venue['id'], 'Edit');
		$tpl->assign('linkCollection', $menu);
		$tpl->display('linkCollection.tpl');
	}
}

require_once 'includes/widgets/footer.php';

?>
