<?php

require_once 'includes/widgets/header.php';

use \libAllure\Session;
use \libAllure\ErrorHandler;

switch ($_REQUEST['action']) {
	case 'toggleEvent':
		requirePriv('TOGGLE_EVENT_PUBLISHED');

		$sql = 'UPDATE events SET published = !published WHERE id = :id';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $_REQUEST['id']);
		$stmt->execute();

		$sql = 'SELECT u.id, u.username, u.email, e.id AS eventId, e.title AS eventTitle FROM users u JOIN organizers o ON u.organization = o.id JOIN events e ON e.organizer = o.id AND e.id = :eventId';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':eventId', $_REQUEST['id']);
		$stmt->execute();

		foreach ($stmt->fetchAll() as $orgieUser) {
			$tpl->assign('user', $orgieUser);
			$content = $tpl->fetch('email.eventToggled.tpl');

			sendEmail($orgieUser['email'], $content, 'Event has been published or unpublished.');
		}

		redirect('viewEvent.php?id=' . $_REQUEST['id'], 'Event toggled. Email sent to organizers.');
		break;
	case 'cloneEvent':
		$event = fetchEvent(fromRequestRequireInt('id'));

		if ($event == null) {
			throw new Exception('event not found');
		}

		if (!Session::getUser()->hasPriv('EVENT_CLONE')) {
			if ($event['organizerId'] != Session::getUser()->getData('organization')) {
				throw new PermissionsException('You cannot clone that event, because you are not the organizer.');
			}
		}

		$sql = 'INSERT INTO events (title, organizer, venue, urlImage, website, priceOnDoor, priceInAdv, showers, sleeping, currency, alcohol, smoking, numberOfSeats, networkMbps, internetMbps, blurb) ';
		$sql .= 'SELECT title, organizer, venue, urlImage, website, priceOnDoor, priceInAdv, showers, sleeping, currency, alcohol, smoking, numberOfSeats, networkMbps, internetMbps, blurb FROM events e2 WHERE e2.id = :id ';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $event['id']);
		$stmt->execute();

		$newEventId = $db->lastInsertId();

		$sql = 'UPDATE events SET title = concat(title, " (cloned)"), createdDate = now(), createdBy = :user WHERE id = :id ';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $newEventId);
		$stmt->bindValue(':user', Session::getUser()->getId());
		$stmt->execute();

		redirect('viewEvent.php?id=' . $newEventId, 'Event Cloned');

		break;
	case 'deleteEvent':
		$event = fetchEvent(fromRequestRequireInt('id'));

		if ($event == null) {
			throw new Exception('event not found');
		}

		if (!Session::getUser()->hasPriv('EVENT_DELETE')) {
			if ($event['organizerId'] != Session::getUser()->getData('organization')) {
				throw new PermissionsException('You cannot delete that event, because you are not the organizer.');
			}
		}


		$sql = 'DELETE FROM events WHERE id = :id';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $event['id']);
		$stmt->execute();

		redirect('index.php', 'Event deleted');
		break;
	case 'updateOrganizerLastChecked':
		$organizer = fetchOrganizer(fromRequestRequireInt('id'));

		$sql = 'UPDATE organizers SET lastChecked = now() WHERE id = :id';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $organizer['id']);
		$stmt->execute();

		redirect('siteChecks.php', 'Updated last checked field for organizer: ' . $organizer['title']);
	default:
		throw new InvalidArgumentException('action not handled.');
}

require_once 'includes/widgets/footer.php';

?>
