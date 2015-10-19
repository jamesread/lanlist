<?php

require_once 'includes/classes/ElementDateTime.php';
require_once 'includes/classes/FormHelpers.php';

class FormEditEvent extends Form {
	public function __construct() {
		parent::__construct('formEditEvent', 'Edit Event');

		$event = $this->getEvent();

		$isAdmin = Session::getUser()->hasPriv('MODERATE_EVENTS');
		$isOwner = Session::getUser()->getData('organization') == $event['organizer'];

		if (!$isAdmin && !$isOwner) {
			throw new Exception('You do not have the privs for this.');
		} else if ($isAdmin) {
			$el = FormHelpers::getOrganizerList(true);
			$el->setValue($event['organizer']);
			$this->addElement($el);
		}

		$this->addElement(Element::factory('hidden', 'id', 'event id', $event['id']));
		$this->addElement(Element::factory('text', 'title', 'Title', $event['title']));

		if ($isAdmin) {
				$this->addElement(FormHelpers::getVenueListElement(null, true));
		} else {
				$this->addElement(FormHelpers::getVenueListElement(Session::getUser()->getData('organization'), true));
		}

		$this->getElement('venue')->setValue($event['venue']);
		$this->addElement(Element::factory('text', 'dateStart', 'Start', $event['dateStart']));
		$this->addElement(Element::factory('text', 'dateFinish', 'Finish', $event['dateFinish']));
		$this->addScript('$("#formEditEvent-dateStart").datetime({"firstDay": 1 })');
		$this->addScript('$("#formEditEvent-dateFinish").datetime({"firstDay": 2 })');
		$this->addElement(Element::factory('numeric', 'priceOnDoor', 'Ticket price on the door', $event['priceOnDoor']));
		$this->addElement(Element::factory('numeric', 'priceInAdv', 'Ticket price in advance', $event['priceInAdv']));
		$this->addElement($this->getElementCurrency($event['currency']));
		$this->addElement(Element::factory('text', 'website', 'Event website', $event['website']));
		$this->addElement(Element::factory('checkbox', 'showers', 'Showers', $event['showers']));
		$this->addElement($this->getElementSleeping($event['sleeping']));
		$this->addElement(Element::factory('checkbox', 'alcohol', 'Bring your own alcohol?', $event['alcohol']));
		$this->addElement(Element::factory('checkbox', 'smoking', 'Smoking area?', $event['smoking']));
		$this->addElement(Element::factory('numeric', 'networkMbps', 'Network (mbps)', $event['networkMbps']));
		$this->getElement('networkMbps')->addSuggestedValue('100', 'Old 100 meg network');
		$this->getElement('networkMbps')->addSuggestedValue('1000', 'Shiny Gigabit network');
		$this->addElement(Element::factory('numeric', 'internetMbps', 'Internet (mbps)', $event['internetMbps'], 'If you have an internet connection, what speed is it? Enter 0 for no connection.'));
		$this->getElement('internetMbps')->addSuggestedValue('0', 'No internet!');
		$this->getElement('internetMbps')->addSuggestedValue('2', '2mbps');
		$this->getElement('internetMbps')->addSuggestedValue('8', '8mbps');
		$this->addElement(Element::factory('numeric', 'numberOfSeats', 'Number of seats', $event['numberOfSeats']));
		$this->addElement(Element::factory('textarea', 'blurb', 'Additional blurb', htmlify($event['blurb'])));
		$this->addButtons(Form::BTN_SUBMIT);
	}

	protected function validateExtended() {
		$this->validateCurrency();
	}

	private function validateCurrency() {
		$val = $this->getElementValue('currency');
		if (empty($val)) {
			return;
		}

		if (!preg_match('/^[A-Z]{3}$/', $this->getElementValue('currency'))) {
			$this->setElementError('currency', 'This is not a valid currency code. Please refer to ISO 4217 (3 uppercase characters).');
		}
	}

	private function getElementCurrency($val) {
		$el = new ElementAutoSelect('currency', 'autoselect', 'Currency', $val, '&pound;, $, etc');
		$el->addOption('GBP (&pound; - UK, etc)', 'GBP');
		$el->addOption('USD ($ - America, etc)', 'USD');
		$el->addOption('AUD ($ - Austrialia, etc)', 'AUD');
		$el->addOption('SEK (Sweden)', 'SEK');
		$el->addOption('ISK (Iceland)', 'ISK');
		$el->addOption('EUR (&euro; - Europe, etc)', 'EUR');
		$el->addOption('CHF (Swiss franc)', 'CHF');

		return $el;
	}

	private function getElementSleeping($val) {
		$el = Element::factory('select', 'sleeping', 'Sleeping');

		$el->addOption('Not aranged by organizer');
		$el->addOption('Not an overnight event');
		$el->addOption('Private rooms at venue');
		$el->addOption('Indoors at venue');
		$el->addOption('Indoors and camping at venue');
		$el->addOption('Indoors, camping and private rooms at venue');
		$el->addOption('Indoors at venue. Camping and hotels nearby.');
		$el->setValue($val);

		return $el;
	}

	private function getEvent() {
		global $db;

		$sql = 'SELECT e.* FROM events e WHERE e.id = :id LIMIT 1';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $_REQUEST['formEditEvent-id']);
		$stmt->execute();

		if ($stmt->numRows() === 0) {
			throw new Exception('Event not found.');
		}

		$event = $stmt->fetchRow();

		return $event;
	}

	public function process() {
		global $db;

		$sql = 'UPDATE events SET title = :title, venue = :venue, dateStart = :dateStart, dateFinish = :dateFinish, priceOnDoor = :priceOnDoor, priceInAdv = :priceInAdv, website = :website, showers = :showers, sleeping = :sleeping, currency = :currency, smoking = :smoking, alcohol = :alcohol, numberOfSeats = :numberOfSeats, networkMbps = :networkMbps, internetMbps = :internetMbps, blurb = :blurb, organizer = :organizer WHERE id = :id';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $this->getElementValue('id'));
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':dateStart', $this->getElementValue('dateStart'));
		$stmt->bindValue(':dateFinish', $this->getElementValue('dateFinish'));
		$stmt->bindValue(':priceOnDoor', $this->getElementvalue('priceOnDoor'));
		$stmt->bindValue(':priceInAdv', $this->getElementvalue('priceInAdv'));
		$stmt->bindValue(':currency', $this->getElementvalue('currency'));
		$stmt->bindValue(':website', $this->getElementvalue('website'));
		$stmt->bindValue(':showers', $this->getElementvalue('showers'));
		$stmt->bindValue(':sleeping', $this->getElementvalue('sleeping'));
		$stmt->bindValue(':alcohol', $this->getElementValue('alcohol'));
		$stmt->bindValue(':smoking', $this->getElementValue('smoking'));
		$stmt->bindValue(':networkMbps', $this->getElementValue('networkMbps'));
		$stmt->bindValue(':internetMbps', $this->getElementValue('internetMbps'));
		$stmt->bindValue(':numberOfSeats', $this->getElementValue('numberOfSeats'));
		$stmt->bindValue(':blurb', $this->getElementValue('blurb'));
		$stmt->bindValue(':venue', $this->getElementValue('venue'));

		if (Session::getUser()->hasPriv('MODERATE_EVENTS')) {
			$stmt->bindValue(':organizer', $this->getElementvalue('organizer'));
		} else {
			$event = $this->getEvent();
			$stmt->bindValue(':organizer', $event['organizer']);
		}

		$stmt->execute();

		Logger::messageDebug('Event ' . $this->getElementValue('title') . ' (' . $this->getElementValue('id') . ') edited by: ' . Session::getUser()->getUsername(), LocalEventType::EDIT_EVENT);

		redirect('viewEvent.php?id=' . $this->getElementValue('id'), 'Event updated.');
	}
}

?>
