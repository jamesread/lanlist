<?php

use \libAllure\ElementNumeric;
use \libAllure\ElementInput;
use \libAllure\Session;

class FormEditTicket extends \libAllure\Form {
	private $event;

	public function __construct() {
		parent::__construct('editTicket', 'Edit Ticket For Event');

		if (!Session::isLoggedIn()) {
			redirect('login.php', 'You need to login before editing a ticket.');
		}

		$tid = 0;
		if (isset($_REQUEST['editTicket-id']) && $_REQUEST['editTicket-id'] !== '') {
			$tid = (int) $_REQUEST['editTicket-id'];
		}

		if ($tid <= 0) {
			throw new \libAllure\exceptions\SimpleFatalError(
				'Ticket id is required to edit a ticket. Open edit from the event page.'
			);
		}

		$ticket = $this->getTicket($tid);
		if ($ticket === false || empty($ticket['id'])) {
			throw new \libAllure\exceptions\SimpleFatalError('Ticket not found.');
		}

		$this->event = $ticket['event'];

		if (!canEditEvent($ticket['organizerId'])) {
			throw new \libAllure\exceptions\SimpleFatalError('You do not have permission to edit this ticket.');
		}

		$this->addElementReadOnly('Ticket', $ticket['id'], 'id'); 
		$this->addElement(new ElementInput('title', 'Title', $ticket['title']));
		$this->addElement(new ElementNumeric('cost', 'Cost', $ticket['cost']));
		$this->getElement('cost')->setMinMaxLengths(1, 10);

		$this->addElement(getElementCurrency($ticket['currency']));

		$this->addDefaultButtons('Save');
	}

	public function process() {
		global $db;

		if (!canEditEvent($this->getTicket((int) $this->getElementValue('id'))['organizerId'] ?? null)) {
			throw new \libAllure\exceptions\SimpleFatalError('You do not have permission to edit this ticket.');
		}

		$sql = 'UPDATE tickets t SET t.cost = :cost, t.currency := :currency, t.title = :title WHERE t.id = :id LIMIT 1';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':cost', $this->getElementValue('cost'));
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':currency', $this->getElementValue('currency'));
		$stmt->bindValue(':id', $this->getElementValue('id'));
		$stmt->execute();

		redirect('viewEvent.php?id=' . $this->event, 'Ticket updated.');
	}

	private function getTicket($id) {
		global $db;

		$sql = 'SELECT t.id, t.title, t.event, t.currency, t.cost, t.event, o.id AS organizerId FROM tickets t LEFT JOIN events e ON t.event = e.id LEFT JOIN organizers o ON e.organizer = o.id WHERE t.id = :id';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $id);
		$stmt->execute();

		return $stmt->fetch();
	}
}
