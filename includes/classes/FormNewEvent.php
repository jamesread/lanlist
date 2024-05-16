<?php

require_once 'includes/classes/FormHelpers.php';
require_once 'includes/classes/ElementDateTime.php';

use \libAllure\Form;
use \libAllure\Session;
use \libAllure\ElementHtml;
use \libAllure\ElementInput;
use \libAllure\ElementHidden;
use \libAllure\Logger;

class FormNewEvent extends Form {
	public function __construct() {
		parent::__construct('formNewEvent', 'New Event');

		if (Session::getUser()->hasPriv('CREATE_EVENTS')) {
			$this->addElement(new ElementHtml('msg', null, 'Hi superuser.'));

			if (isset($_REQUEST['formNewEvent-organizer'])) {
				$organizerId = intval($_REQUEST['formNewEvent-organizer']);
				$this->addElement(new ElementHidden('organizer', 'Organizer', $organizerId));
				$this->addElement(FormHelpers::getVenueListElement($organizerId));
			} else {
				$this->addElement(FormHelpers::getOrganizerList(true));
				$this->addElement(FormHelpers::getVenueListElement());
			}
		} else if (Session::getUser()->getData('organization')) {
			$organizer = fetchOrganizer(Session::getUser()->getData('organization'));

			if ($organizer['published']) {
				$this->addElement(new ElementHtml('msg', null, 'You are authorized to create public events for your organization.'));
			} else {
				$this->addElement(new ElementHtml('msg', null, 'Your event will be linked to your organization, but will not be public until your organization has been approved.'));
			}

			try {
				$this->addElement(FormHelpers::getVenueListElement(Session::getUser()->getData('organization')));
			} catch (Exception $e) {
				redirect('account.php', 'Create a venue first!');
			}
		} else {
			$this->addElement(new ElementHtml('msg', null, 'You can create events, but they will not appear in public lists until approved.'));
		}

		$this->addElement(new ElementInput('title', 'Title', null, 'eg: MyLan 2011'));
		$this->addElement(new ElementInput('eventWebsite', 'Event specific URL', null, 'A URL to the event webpage on the organizer website would be useful.'));
		$this->getElement('eventWebsite')->setMinMaxLengths(0, 256);
		$this->addElement(new ElementInput('dateStart', 'Start date'));
		$this->addElement(new ElementInput('dateFinish', 'Finish date'));
		$this->addElement(new ElementHtml('protip', null, '<strong style = "text-decoration: blink; color: red;">Protip:</strong> You can edit this event and add much more detail after you have created it. '));

		$this->addScript('$("#formNewEvent-dateStart").datetime({chainTo: "#formNewEvent-dateFinish", stepMins: 15, first: 3})');

		$this->addButtons(Form::BTN_SUBMIT);
		$this->requireFields(array('title'));
	}

	public function process() {
		global $db;

		$sql = 'INSERT INTO events (title, dateStart, dateFinish, organizer, venue, published, website, createdDate, createdBy) VALUES (:title, :dateStart, :dateFinish, :organizer, :venue, :published, :website, now(), :createdBy)';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':dateStart', $this->getElementValue('dateStart'));
		$stmt->bindValue(':dateFinish', $this->getElementValue('dateFinish'));
		$stmt->bindValue(':website', $this->getElementValue('eventWebsite'));
		$stmt->bindValue(':createdBy', Session::getUser()->getId());

		if (Session::getUser()->hasPriv('CREATE_EVENTS')) {
			$this->addElement(new ElementHtml('msg', null, 'Hi superuser.'));
			$stmt->bindValue(':organizer', $this->getElementValue('organizer'));
			$stmt->bindValue(':published', 1);
			$stmt->bindValue(':venue', $this->getElementValue('venue'));
		} else if (Session::getUser()->getData('organization') != null) {
			$stmt->bindValue(':venue', $this->getElementValue('venue'));

			$organizer = fetchOrganizer(Session::getUser()->getData('organization'));

			if ($organizer['published']) {
				$this->addElement(new ElementHtml('msg', null, 'You are authorized to create public events for your organization.'));
				$stmt->bindValue(':organizer', $organizer['id']);
				$stmt->bindValue(':published', 1);
			} else {
				$this->addElement(new ElementHtml('msg', null, 'Your event will be linked to your organization, but will not be public until your organization has been approved.'));
				$stmt->bindValue(':organizer', $organizer['id']);
				$stmt->bindValue(':published', 0);
			}
		} else {
			$this->addElement(new ElementHtml('msg', null, 'You can create events, but they will not appear in public lists until approved.'));
			$stmt->bindValue(':organizer', null);
			$stmt->bindValue(':published', 0);
			$stmt->bindValue(':venue', null);
		}

		$stmt->execute();
		$eventId = $db->lastInsertId();

		Logger::messageDebug('Event ' . $this->getElementValue('title') . ' created by: ' . Session::getUser()->getUsername(), LocalEventType::CREATE_EVENT);
		redirect('viewEvent.php?id=' . $eventId, 'Event created.');
	}
}

?>
