<?php

require_once 'includes/classes/FormHelpers.php';

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementHidden;
use libAllure\ElementInput;
use libAllure\ElementNumeric;
use libAllure\ElementAutoSelect;
use libAllure\ElementCheckbox;
use libAllure\ElementSelect;
use libAllure\ElementTextbox;
use libAllure\ElementDate;
use libAllure\Logger;

class FormEditEvent extends Form
{
    public function __construct()
    {
        parent::__construct('formEditEvent', 'Edit Event');

        $event = $this->getEvent();

        $isAdmin = Session::getUser()->hasPriv('MODERATE_EVENTS');
        $isOwner = Session::getUser()->getData('organization') == $event['organizer'];

        if (!$isAdmin && !$isOwner) {
            throw new Exception('You do not have the privs for this.');
        } elseif ($isAdmin) {
            $el = FormHelpers::getOrganizerList(true);
            $el->setValue($event['organizer']);
            $this->addElement($el);
        }

        $this->addElement(new ElementHidden('id', 'event id', $event['id']));
        $this->addElement(new ElementInput('title', 'Title', $event['title']));
                $this->getElement('title')->setMinMaxLengths(5, 128);

        if ($isAdmin) {
                $this->addElement(FormHelpers::getVenueListElement(null, true));
        } else {
                $this->addElement(FormHelpers::getVenueListElement(Session::getUser()->getData('organization'), true));
        }

        // Trim seconds from dates as it messes up browser 
        $event['dateStart'] = date_create($event['dateStart'])->format('Y-m-d H:i');
        $event['dateFinish'] = date_create($event['dateFinish'])->format('Y-m-d H:i');
        
        $this->getElement('venue')->setValue($event['venue']);
        $this->addElement(new ElementDate('dateStart', 'Start', $event['dateStart']));
        $this->addElement(new ElementDate('dateFinish', 'Finish', $event['dateFinish']));
            $s = <<<EOF
const dateStart = document.getElementById('formEditEvent-dateStart');
const dateFinish = document.getElementById('formEditEvent-dateFinish');

dateStart.onchange = () => {
    dateFinish.value = dateStart.value;
}
EOF;
        $this->addScript($s);

        $this->addElement(getElementCurrency($event['currency']));
        $this->addElement(new ElementInput('website', 'Event website', $event['website']));
        $this->addElementAgeRestrictions($event['ageRestrictions']);
        $this->addElement(new ElementSelect('showers', 'Showers', $event['showers']))->addOptions(dataShowers());
        $this->addElement(new ElementSelect('sleeping', 'Sleeping', $event['sleeping']))->addOptions(dataSleeping());
        $this->addElement(new ElementSelect('alcohol', 'Bring your own alcohol?', $event['alcohol']))->addOptions(dataAlcohol());
        $this->addElement(new ElementSelect('smoking', 'Smoking area?', $event['smoking']))->addOptions(dataSmoking());
        $this->addElement(new ElementNumeric('networkMbps', 'Network (mbps)', $event['networkMbps']));
        $this->getElement('networkMbps')->addSuggestedValue('100', 'Old 100 meg network');
        $this->getElement('networkMbps')->addSuggestedValue('1000', 'Shiny Gigabit network');
        $this->addElement(new ElementNumeric('internetMbps', 'Internet (mbps)', $event['internetMbps'], 'If you have an internet connection, what speed is it? Enter 0 for no connection.'));
        $this->getElement('internetMbps')->addSuggestedValue('0', 'No internet!');
        $this->getElement('internetMbps')->addSuggestedValue('2', '2mbps');
        $this->getElement('internetMbps')->addSuggestedValue('8', '8mbps');
        $this->addElement(new ElementNumeric('numberOfSeats', 'Number of seats', $event['numberOfSeats']));
        $this->addElement(new ElementTextbox('blurb', 'Additional blurb', htmlify($event['blurb'])));

        $this->addDefaultButtons('Save');
    }

    protected function addElementAgeRestrictions($value)
    {
        $el = $this->addElement(new ElementSelect('ageRestrictions', 'Age Restrictions'));
        $el->addOption('Not known', '');
        $el->addOption('Over 18s Only');
        $el->addOption('Under 18s require parents consent');
        $el->setValue($value);
    }

    protected function validateExtended()
    {
        $this->validateCurrency();
    }

    private function validateCurrency()
    {
        $val = $this->getElementValue('currency');
        if (empty($val)) {
            return;
        }

        if (!preg_match('/^[A-Z]{3}$/', $this->getElementValue('currency'))) {
            $this->setElementError('currency', 'This is not a valid currency code. Please refer to ISO 4217 (3 uppercase characters).');
        }
    }

    private function getEvent()
    {
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

    public function process()
    {
        global $db;

        $sql = 'UPDATE events SET title = :title, venue = :venue, dateStart = :dateStart, dateFinish = :dateFinish, website = :website, showers = :showers, sleeping = :sleeping, currency = :currency, ageRestrictions = :ageRestrictions, smoking = :smoking, alcohol = :alcohol, numberOfSeats = :numberOfSeats, networkMbps = :networkMbps, internetMbps = :internetMbps, blurb = :blurb, organizer = :organizer WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $this->getElementValue('id'));
        $stmt->bindValue(':title', $this->getElementValue('title'));
        $stmt->bindValue(':dateStart', $this->getElementValue('dateStart'));
        $stmt->bindValue(':dateFinish', $this->getElementValue('dateFinish'));
        $stmt->bindValue(':currency', $this->getElementvalue('currency'));
        $stmt->bindValue(':ageRestrictions', $this->getElementvalue('ageRestrictions'));
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

        Logger::messageAudit('Event ' . $this->getElementValue('title') . ' (' . $this->getElementValue('id') . ') edited by: ' . Session::getUser()->getUsername(), 'EDIT_EVENT');

        redirect('viewEvent.php?id=' . $this->getElementValue('id'), 'Event updated.');
    }
}
