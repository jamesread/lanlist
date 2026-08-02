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

        if (!Session::isLoggedIn()) {
            redirect('login.php', 'You need to login before editing a venue.');
        }

        if (!Session::hasPriv('EDIT_VENUE') && !Session::hasPriv('SUPERUSER')) {
            throw new \libAllure\exceptions\SimpleFatalError('You do not have permission to edit venues.');
        }

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
        $id = 0;
        if (isset($_REQUEST['formEditVenue-id']) && $_REQUEST['formEditVenue-id'] !== '') {
            $id = (int) $_REQUEST['formEditVenue-id'];
        }

        if ($id <= 0) {
            throw new \libAllure\exceptions\SimpleFatalError(
                'Venue id is required to edit a venue. Open edit from the venue page.'
            );
        }

        return fetchVenue($id);
    }

    public function process()
    {
        global $db;

        if (!Session::hasPriv('EDIT_VENUE') && !Session::hasPriv('SUPERUSER')) {
            throw new \libAllure\exceptions\SimpleFatalError('You do not have permission to edit venues.');
        }

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
