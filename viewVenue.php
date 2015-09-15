<?php

require_once 'includes/widgets/header.php';

$id = fromRequestRequireInt('id');
$venue = fetchVenue($id);

$tpl->assign('organizersAtVenue', fetchOrganizersFromVenueId($id));
$tpl->assign('eventsAtVenue', fetchEventsFromVenueId($id));
$tpl->assign('venue', $venue);
$tpl->display('viewVenue.tpl');
startSidebar();

require_once 'includes/widgets/infoboxListFilter.php';

if (Session::isLoggedIn()) {
	$organizer = Session::getUser()->getData('organization');

	if (Session::hasPriv('EDIT_VENUE') || $organizer == $venue['organizer']) {
		$menu = new HtmlLinksCollection('Venue admin');
		$menu->add('formHandler.php?formClazz=FormEditVenue&amp;formEditVenue-id=' . $venue['id'], 'Edit');
		$tpl->assign('linkCollection', $menu);
		$tpl->display('linkCollection.tpl');
	}
}

require_once 'includes/widgets/footer.php';

?>
