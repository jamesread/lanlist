<?php

require_once 'includes/classes/FormHelpers.php';

use libAllure\ElementInput;
use libAllure\ElementHidden;
use libAllure\ElementPassword;

use libAllure\Form;
use libAllure\Session;

class FormEditVenue extends Form
{
    public function __construct()
    {
        parent::__construct('formEditVenue', 'Edit Venue');

        $venue = $this->getVenue();

        $this->addElement(new ElementHidden('id', null, $venue['id']));
        $this->addElement(new ElementInput('title', 'Title', $venue['title']));
        $this->addElement(new ElementInput('lat', 'Lat', $venue['lat']));
        $this->getElement('lat')->setMinMaxLengths(1, 10);
        $this->addElement(new ElementInput('lng', 'Lng', $venue['lng']));
        $this->getElement('lng')->setMinMaxLengths(1, 10);
        $this->addElement(FormHelpers::getElementCountry($venue['country']));

        $this->addDefaultButtons();
    }

    private function getVenue()
    {
        if (isset($_REQUEST['formEditVenue-id'])) {
            $id = intval($_REQUEST['formEditVenue-id']);
        } else {
            $id = intval($this->getElementValue('id'));
        }

        $venue = fetchVenue($id);

        return $venue;
    }

    public function process()
    {
        global $db;

        $sql = 'UPDATE venues SET title = :title, lat = :lat, lng = :lng, country = :country WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':title', $this->getElementValue('title'));
        $stmt->bindValue(':lat', $this->getElementValue('lat'));
        $stmt->bindValue(':lng', $this->getElementValue('lng'));
        $stmt->bindValue(':country', $this->getElementValue('country'));
        $stmt->bindValue(':id', $this->getElementValue('id'));
        $stmt->execute();

        redirect('viewVenue.php?id=' . $this->getElementValue('id'), 'Venue updated.');
    }
}
