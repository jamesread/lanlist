<?php

require_once 'includes/classes/FormHelpers.php';

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementHtml;
use libAllure\ElementInput;
use libAllure\ElementHidden;
use libAllure\ElementDate;
use libAllure\Logger;

class FormNewEvent extends Form
{
    public function __construct()
    {
        parent::__construct('formNewEvent', 'New Event');

        if (!Session::isLoggedIn()) {
            redirect('login.php', 'You should login before creating events!');
        }

        $organizerIdForVenues = null;
        $hasOrganizerSelect = false;

        if (Session::getUser()->hasPriv('CREATE_EVENTS')) {
            if (isset($_REQUEST['formNewEvent-organizer'])) {
                $organizerIdForVenues = (int) $_REQUEST['formNewEvent-organizer'];
            } else {
                $hasOrganizerSelect = true;
            }
        } elseif (Session::getUser()->getData('organization')) {
            $organizerIdForVenues = (int) Session::getUser()->getData('organization');
        }

        if (isset($_REQUEST['formNewEvent-venue'])) {
            $this->addElementReadOnly('venue', $_REQUEST['formNewEvent-venue'], 'venue');
        } else {
            $venueEl = FormHelpers::getVenueListElement();
            $this->addElement($venueEl);

            if ($organizerIdForVenues || $hasOrganizerSelect) {
                $venueEl->description .= '<br /><span id="formNewEvent-recentVenues" class="subtle"></span>';
                $this->addOrganizerVenueQuickPickScript($organizerIdForVenues, $hasOrganizerSelect);
            }
        }

        if (Session::getUser()->hasPriv('CREATE_EVENTS')) {
            $this->addElement(new ElementHtml('msg', null, 'Hi superuser.'));

            if (isset($_REQUEST['formNewEvent-organizer'])) {
                $organizerId = (int) $_REQUEST['formNewEvent-organizer'];
                $this->addPresetOrganizerFields($organizerId);
            } else {
                $this->addElement(FormHelpers::getOrganizerList(true, true));
            }
        } elseif (Session::getUser()->getData('organization')) {
            $organizer = fetchOrganizer(Session::getUser()->getData('organization'));

            if ($organizer['published']) {
                $this->addElement(new ElementHtml('msg', null, 'You are authorized to create public events for your organization.'));
            } else {
                $this->addElement(new ElementHtml('msg', null, 'Your event will be linked to your organization, but will not be public until your organization has been approved.'));
            }
        } else {
            $this->addElement(new ElementHtml('msg', null, 'You can create events, but they will not appear in public lists until approved.'));
        }

        $this->addElement(new ElementInput('title', 'Title', null, 'eg: MyLan 2011'));
                $this->getElement('title')->setMinMaxLengths(5, 128);

        $this->addElement(new ElementInput('eventWebsite', 'Event specific URL', null, 'A URL to the event webpage on the organizer website would be useful.'));
        $this->getElement('eventWebsite')->setMinMaxLengths(0, 256);

        $now = date_format(date_create(), 'Y-m-d');

        $this->addElement(new ElementDate('dateStart', 'Start date', "$now 00:00"));
        
        $this->addElement(new ElementDate('dateFinish', 'Finish date', "$now 00:00"));

            $s = <<<EOF
const dateStart = document.getElementById('formNewEvent-dateStart');
const dateFinish = document.getElementById('formNewEvent-dateFinish');

dateStart.onchange = () => {
    dateFinish.value = dateStart.value;
}
EOF;
        $this->addScript($s);

        $this->addElement(new ElementHtml('protip', null, '<strong style = "text-decoration: blink; color: red;">Protip:</strong> You can edit this event and add much more detail after you have created it. '));

                $this->requireFields('title', 'dateStart', 'dateFinish');

        $this->addDefaultButtons('Create event');
    }

    private function addPresetOrganizerFields(int $organizerId): void
    {
        $organizer = fetchOrganizer($organizerId);

        $this->addElement(new ElementHidden('organizer', 'Organizer', $organizerId));

        $organizerEl = $this->addElementReadOnly(
            'Organizer',
            htmlspecialchars((string) $organizer['title'], ENT_QUOTES, 'UTF-8')
        );

        if (!empty($organizer['websiteUrl'])) {
            $websiteUrl = htmlspecialchars((string) $organizer['websiteUrl'], ENT_QUOTES, 'UTF-8');
            $organizerEl->description = '<a href="' . $websiteUrl . '" target="_blank" rel="noopener noreferrer">' . $websiteUrl . '</a>';
        }
    }

    private function addOrganizerVenueQuickPickScript(?int $organizerIdForVenues, bool $hasOrganizerSelect): void
    {
        $organizerVenuesJson = json_encode(
            FormHelpers::fetchVenuesUsedByOrganizers(),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
        $initialOrganizerId = $organizerIdForVenues ? (string) $organizerIdForVenues : 'null';

        $script = <<<JS
(function () {
    const venueSelect = document.getElementById('formNewEvent-venue');
    const recentVenuesEl = document.getElementById('formNewEvent-recentVenues');
    if (!venueSelect || !recentVenuesEl) {
        return;
    }

    const organizerVenues = {$organizerVenuesJson};
    const organizerSelect = document.getElementById('formNewEvent-organizer');
    const fixedOrganizerId = {$initialOrganizerId};

    function renderRecentVenues(organizerId) {
        const venues = organizerVenues[String(organizerId)] || [];
        recentVenuesEl.replaceChildren();

        if (venues.length === 0) {
            recentVenuesEl.hidden = true;
            return;
        }

        recentVenuesEl.hidden = false;
        recentVenuesEl.append('Previously used venues: ');

        venues.forEach((venue, index) => {
            if (index > 0) {
                recentVenuesEl.append(', ');
            }

            const link = document.createElement('a');
            link.href = '#';
            link.className = 'venue-quick-pick';
            link.dataset.venueId = String(venue.id);
            link.textContent = venue.country + ', ' + venue.title;
            recentVenuesEl.append(link);
        });
    }

    recentVenuesEl.addEventListener('click', (event) => {
        const link = event.target.closest('.venue-quick-pick');
        if (!link) {
            return;
        }

        event.preventDefault();
        venueSelect.value = link.dataset.venueId;
        venueSelect.dispatchEvent(new Event('change', { bubbles: true }));
    });

    function updateRecentVenues() {
        const organizerId = organizerSelect
            ? parseInt(organizerSelect.value, 10)
            : fixedOrganizerId;

        if (!organizerId) {
            recentVenuesEl.replaceChildren();
            recentVenuesEl.hidden = true;
            return;
        }

        renderRecentVenues(organizerId);
    }

    if (organizerSelect) {
        organizerSelect.addEventListener('change', updateRecentVenues);
    }

    updateRecentVenues();
})();
JS;

        $this->addScript($script);
    }

    public function process()
    {
        global $db;

        $organizerId = null;

        $sql = 'INSERT INTO events (title, dateStart, dateFinish, organizer, venue, published, website, createdDate, createdBy) VALUES (:title, :dateStart, :dateFinish, :organizer, :venue, :published, :website, now(), :createdBy)';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':title', $this->getElementValue('title'));
        $stmt->bindValue(':dateStart', $this->getElementValue('dateStart'));
        $stmt->bindValue(':dateFinish', $this->getElementValue('dateFinish'));
        $stmt->bindValue(':website', $this->getElementValue('eventWebsite'));
        $stmt->bindValue(':createdBy', Session::getUser()->getId());

        if (Session::getUser()->hasPriv('CREATE_EVENTS')) {
            $this->addElement(new ElementHtml('msg', null, 'Hi superuser.'));
            $organizerId = (int) $this->getElementValue('organizer');
            $stmt->bindValue(':organizer', $organizerId ?: null);
            $stmt->bindValue(':published', 1);
            $stmt->bindValue(':venue', $this->getElementValue('venue'));
        } elseif (Session::getUser()->getData('organization') != null) {
            $stmt->bindValue(':venue', $this->getElementValue('venue'));

            $organizer = fetchOrganizer(Session::getUser()->getData('organization'));
            $organizerId = (int) $organizer['id'];

            if ($organizer['published']) {
                $this->addElement(new ElementHtml('msg', null, 'You are authorized to create public events for your organization.'));
                $stmt->bindValue(':organizer', $organizerId);
                $stmt->bindValue(':published', 1);
            } else {
                $this->addElement(new ElementHtml('msg', null, 'Your event will be linked to your organization, but will not be public until your organization has been approved.'));
                $stmt->bindValue(':organizer', $organizerId);
                $stmt->bindValue(':published', 0);
            }
        } else {
            $this->addElement(new ElementHtml('msg', null, 'You can create events, but they will not appear in public lists until approved.'));
            $stmt->bindValue(':organizer', null);
            $stmt->bindValue(':published', 0);
            $stmt->bindValue(':venue', null);
        }

        $stmt->execute();
        $eventId = (int) $db->lastInsertId();

        if ($organizerId) {
            require_once __DIR__ . '/../functionality/edit_notifications.php';
            lanlistSendNewEventNotifications(
                $eventId,
                (int) Session::getUser()->getId(),
                Session::getUser()->getUsername(),
                (int) Session::getUser()->getData('group')
            );
        }

        Logger::messageDebug('Event ' . $this->getElementValue('title') . ' created by: ' . Session::getUser()->getUsername(), 'CREATE_EVENT');
        redirect('viewEvent.php?id=' . $eventId, 'Event created.');
    }
}
