<?php

require_once 'includes/classes/FormHelpers.php';

class FormEditVenue extends Form {
	public function __construct() {
		parent::__construct('formEditVenue', 'Edit Venue');

		$venue = $this->getVenue();

		if (Session::getUser()->getData('organization') != $venue['organizer']) {
			Session::requirePriv('EDIT_VENUE');
		}

		$this->addElement(Element::factory('hidden', 'id', null, $venue['id']));
		$this->addElement(Element::factory('text', 'title', 'Title', $venue['title']));
		$this->addElement(Element::factory('text', 'lat', 'Lat', $venue['lat']));
		$this->getElement('lat')->setMinMaxLengths(1, 10);
		$this->addElement(Element::factory('text', 'lng', 'Lng', $venue['lng']));
		$this->getElement('lng')->setMinMaxLengths(1, 10);
		$this->addElement(FormHelpers::getElementCountry($venue['country']));
		$this->addElement(FormHelpers::getOrganizerList());
		$this->getElement('organizer')->setValue($venue['organizer']);

		$this->addButtons(Form::BTN_SUBMIT);
	}

	private function getVenue() {
		if ($this->isSubmitted()) {
			$id = intval($this->getElementValue('id'));
		} else {
			$id = intval($_REQUEST['formEditVenue-id']); 
		}
		
		$venue = fetchVenue($id);

		return $venue;
	}

	public function process() {
		global $db;

		$sql = 'UPDATE venues SET title = :title, lat = :lat, lng = :lng, country = :country, organizer = :organizer WHERE id = :id';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':lat', $this->getElementValue('lat'));
		$stmt->bindValue(':lng', $this->getElementValue('lng'));
		$stmt->bindValue(':country', $this->getElementValue('country'));
		$stmt->bindValue(':id', $this->getElementValue('id'));
		$stmt->bindValue(':organizer', $this->getElementValue('organizer'));
		$stmt->execute();

		redirect('viewVenue.php?id=' . $this->getElementValue('id'), 'Event updated.');
	}
}

?>
