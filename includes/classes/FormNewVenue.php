<?php

require_once 'includes/classes/FormHelpers.php';

use \libAllure\Form;
use \libAllure\Session;
use \libAllure\ElementHtml;

class FormNewVenue extends Form {
	public function __construct() {
		parent::__construct('newVenue', 'New Venue');

		$this->addElement(new ElementHtml('desc', null, 'A venue is a physical place where an event will be hosted, this may be a convention centre, a hall or just your house. You can specify detail such as sleeping arangements when the event is created.'));
		$this->addElement(new ElementInput('title', 'Title', null, 'eg: Budleigh Salterton town hall, Cheltenham Racecourse, etc.'));
		$this->addElement(FormHelpers::getElementCountry('United Kingdom'));
		$this->addElement(new ElementHtml('locationDesc', null, '<br />The geodetic (WGS84) latitude/longitude of your venue. This can be awkward, but it allows us to put a pin on the map. We cannot use post/zip codes because many countries do not have them! <a href = "http://www.getlatlon.com/">http://getlatlong.com</a> will convert an address to a rough lat/lng. '));
		$this->addElement(Element::factory('numeric', 'lat', 'Latitude'))->setAllowNegative(true);
		$this->addElement(Element::factory('numeric', 'lng', 'Longitude'))->setAllowNegative(true);

		if (Session::hasPriv('NEW_VENUE')) {
			$this->addElement(FormHelpers::getOrganizerList());

			if (isset($_REQUEST['formNewVenue-organizer'])) {
				$this->getElement('organizer')->setValue($_REQUEST['formNewVenue-organizer']);
			}
		}

		$this->addButtons(Form::BTN_SUBMIT);

		$this->requireFields(array('title', 'lat', 'lng', 'country'));
	}

	public function validateExtended() {
	}	

	public function process() {
		global $db;

		$sql = 'INSERT INTO venues (title, lat, lng, organizer, country) VALUES (:title, :lat, :lng, :organizer, :country) ';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':lat', $this->getElementValue('lat'));
		$stmt->bindValue(':lng', $this->getElementValue('lng'));
		$stmt->bindValue(':country', $this->getElementValue('country'));

		if (Session::hasPriv('NEW_VENUE')) {
			$stmt->bindValue(':organizer', $this->getElementValue('organizer'));
		} else {
			$stmt->bindValue('organizer', Session::getUser()->getData('organization'));
		}

		$stmt->execute();

		Logger::messageDebug('Venue ' . $this->getElementValue('title') . ' created by: ' . Session::getUser()->getUsername(), LocalEventType::CREATE_VENUE);
		redirect('account.php', 'Venue created.');
	}
}

?>
