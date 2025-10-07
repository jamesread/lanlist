<?php

use libAllure\Form;
use libAllure\Shortcuts;
use libAllure\ElementInput;

class FormAddTicket extends Form {
    private $eventId;

    public function __construct() {
        parent::__construct('addticket', 'Add Ticket');

		$this->eventId = Shortcuts::san()->filterUint('addticket-eventId');

		$event = fetchEvent($this->eventId);

        $this->addElementReadOnly('Event title', $event['eventTitle']);
        $this->addElementReadOnly('Event ID', $this->eventId, 'eventId');

        $elTicketTitle = new ElementInput('ticketTitle', 'Ticket title', '');
        $this->addElement($elTicketTitle);

        $elTicketCost = new ElementInput('ticketCost', 'Ticket cost', '');
        $this->addElement($elTicketCost);

        $elTicketCurrency = getElementCurrency('GBP');
        $this->addElement($elTicketCurrency);

        $this->addDefaultButtons('Add ticket');
    }

    public function process() {
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
