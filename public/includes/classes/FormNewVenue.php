<?php

require_once 'includes/classes/FormHelpers.php';

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementHtml;
use libAllure\ElementInput;
use libAllure\ElementNumeric;
use libAllure\Logger;

class FormNewVenue extends Form
{
    public function __construct()
    {
        parent::__construct('newVenue', 'New Venue');

        $this->addElement(new ElementHtml('desc', null, 'A venue is a physical place where an event will be hosted, this may be a convention centre, a hall or just your house. You can specify detail such as sleeping arangements when the event is created. <br /><br /><strong>Note</strong> that organizations do not "own" venues, and other organizers can schedule their events at a venue that they did not create. For this reason, once you create a venue, only an admin can edit it.'));
        $this->addElement(new ElementInput('title', 'Title', null, 'eg: Budleigh Salterton town hall, Cheltenham Racecourse, etc.'));
        $this->addElement(FormHelpers::getElementCountry('United Kingdom'));
        $this->addElement(new ElementHtml('locationDesc', null, '<br />The geodetic (WGS84) latitude/longitude of your venue. This can be awkward, but it allows us to put a pin on the map. We cannot use post/zip codes because many countries do not have them! <a href = "https://www.latlong.net/">https://www.latlong.net/</a> will convert an address to a rough lat/lng. '));
        $this->addElement(new ElementInput('magicLocation', 'Magic Location'));
        $e = <<<EOF
const magic = document.getElementById('newVenue-magicLocation');

magic.onchange = () => {
    console.log("onc");
    if (magic.value.includes(", ")) {
        const text = magic.value;
        const regex = /([-+]?\d+\.\d+),\s*([-+]?\d+\.\d+)/;
        const match = text.match(regex);

        if (match) {
            document.getElementById('newVenue-lat').value = match[1];
            document.getElementById('newVenue-lng').value = match[2];
        }

        magic.setCustomValidity('');
    } else {
        magic.setCustomValidity('Cannot parse');
        magic.reportValidity();
    }
}
EOF;
        $this->addScript($e);
        $this->addElement(new ElementNumeric('lat', 'Latitude'))->setAllowNegative(true);
        $this->addElement(new ElementNumeric('lng', 'Longitude'))->setAllowNegative(true);
        $this->addDefaultButtons('Create Venue');

        $this->requireFields(array('title', 'lat', 'lng', 'country'));
    }

    public function validateExtended()
    {
    }

    public function process()
    {
        global $db;

        $sql = 'INSERT INTO venues (title, lat, lng, country) VALUES (:title, :lat, :lng, :country) ';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':title', $this->getElementValue('title'));
        $stmt->bindValue(':lat', $this->getElementValue('lat'));
        $stmt->bindValue(':lng', $this->getElementValue('lng'));
        $stmt->bindValue(':country', $this->getElementValue('country'));
        $stmt->execute();

        $venueId = $db->lastInsertId();

        Logger::messageAudit('Venue ' . $this->getElementValue('title') . ' created by: ' . Session::getUser()->getUsername(), 'CREATE_VENUE');

        redirect('viewVenue.php?id=' . $venueId, 'Venue created.');
    }
}
