<?php

use \libAllure\Form;
use \libAllure\Session;
use \libAllure\ElementSelect;
use \libAllure\ElementHtml;
use \libAllure\ElementTextbox;

class FormJoinOrganizer extends Form {
	public function __construct() {
		parent::__construct('formJoinOrganizer', 'Join organization');

		$this->addElement(new ElementHtml('description', null, 'This will send a request to an administrator for you to join onto a LAN Party Organizer. We approve these manaully, but we will do it as quickly as possible.'));
		$this->addElement($this->getElementOrganization());
		$this->addElement(new ElementTextbox('comments', 'Comments', null, 'Use the comments if there is anything you want to let us know about.'));
		$this->addDefaultButtons("Send Request");
	}

	private function getElementOrganization() {
		global $db;

		$sql = 'SELECT o.id, o.title FROM organizers o ORDER BY o.title ASC';
		$stmt = $db->query($sql);

		$el = new ElementSelect('organization', 'Organization');

		foreach ($stmt->fetchAll() as $organization) {
			$el->addOption($organization['title'], $organization['id']);
		}

		return $el;
	}

	public function process() {
		global $db;

		$sql = 'INSERT INTO organization_join_requests (user, organizer, comments) VALUES (:user, :organization, :comments) ';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':user', Session::getUser()->getId());
		$stmt->bindValue(':organization', $this->getElementValue('organization'));
		$stmt->bindValue(':comments', $this->getElementValue('comments'));
		$stmt->execute();

		redirect('account.php', 'Thanks, your request to join will be approved or denied as quickly as possible!');
	}
}

?>
