<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\Shortcuts;
use libAllure\ElementInput;

class FormAddTicket extends Form {
    private $eventId;

    public function __construct() {
        parent::__construct('addticket', 'Add Ticket');

        if (!Session::isLoggedIn()) {
            redirect('login.php', 'You need to login before adding a ticket.');
        }

        $this->eventId = 0;
        if (isset($_REQUEST['addticket-eventId']) && $_REQUEST['addticket-eventId'] !== '') {
            $this->eventId = (int) Shortcuts::san()->filterUint('addticket-eventId');
        }

        if ($this->eventId <= 0) {
            throw new \libAllure\exceptions\SimpleFatalError(
                'Event id is required to add a ticket. Open add ticket from the event page.'
            );
        }

        $event = fetchEvent($this->eventId);

        if (!canEditEvent($event['organizerId'] ?? null)) {
            throw new \libAllure\exceptions\SimpleFatalError('You do not have permission to add tickets for this event.');
        }

        $this->addElementReadOnly('Event title', $event['eventTitle']);

        $this->addElementReadOnly('Event ID', $this->eventId, 'eventId');

        $elTicketTitle = new ElementInput('ticketTitle', 'Ticket title', '');
		$elTicketTitle->setMinMaxLengths(1, 128);
        $this->addElement($elTicketTitle);

        $elTicketCost = new ElementInput('ticketCost', 'Ticket cost', '');
		$elTicketCost->setMinMaxLengths(1, 10);
        $this->addElement($elTicketCost);

        $elTicketCurrency = getElementCurrency('GBP');
        $this->addElement($elTicketCurrency);

        $this->addDefaultButtons('Add ticket');
    }

    public function process() {
        if (!canEditEvent(fetchEvent($this->eventId)['organizerId'] ?? null)) {
            throw new \libAllure\exceptions\SimpleFatalError('You do not have permission to add tickets for this event.');
        }

        $sql = 'INSERT INTO tickets (event, cost, currency, title) values (:event, :cost, :currency, :title)';
        $stmt = Shortcuts::stmt($sql);
        $stmt->bindValue(':event', $this->eventId);
		$stmt->bindValue(':title', $this->getElementValue('ticketTitle'));
        $stmt->bindValue(':cost', $this->getElementValue('ticketCost'));
		$stmt->bindValue(':currency', $this->getElementValue('currency'));
		$stmt->execute();

        redirect('viewEvent.php?id=' . $this->eventId, 'Event updated.');
    }
}
