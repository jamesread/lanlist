<?php

use \libAllure\ElementNumeric;
use \libAllure\ElementInput;

class FormEditTicket extends \libAllure\Form {
	private $event;

	public function __construct() {
		parent::__construct('editTicket', 'Edit Ticket For Event');

		$tid = $_REQUEST['editTicket-id'];

		$ticket = $this->getTicket($tid);
		$this->event = $ticket['event'];

		$canEditEvent = canEditEvent($this->event);

		if (!$canEditEvent) {
			throw new \libAllure\FormException('You do not have permission to edit this ticket.');
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

		$sql = 'SELECT t.id, t.title, t.event, t.currency, t.cost FROM tickets t WHERE t.id = :id';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $id);
		$stmt->execute();

		return $stmt->fetch();
	}
}

?>
