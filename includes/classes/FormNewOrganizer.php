<?php

use \libAllure\Form;
use \libAllure\Session;
use \libAllure\ElementHtml;
use \libAllure\ElementInput;
use \libAllure\ElementTextbox;

class FormNewOrganizer extends Form {
	public function __construct() {
		parent::__construct('newOrganizer', 'New Organizer');

		if (Session::getUser()->hasPriv('CREATE_ORGANIZERS')) {
			$this->addElement(new ElementHtml('description', null, 'This will appear in the organizers list as soon as you submit the form.'));
		} else {
			$currentOrganizer = Session::getUser()->getData('organization');

			if (empty($currentOrganizer) && $currentOrganizer != 0) {
				throw new PermissionException('Cannot create another organizer, you already have one aginst your account');
			}

			$this->addElement(new ElementHtml('description', null, 'This will not appear in the organizers list immidiately, it will first have to be approved by one of our smiling friendly admins - they accept bribes in the form of cake.'));
		}

		$this->addElement(new ElementInput('title', 'Title'));
		$this->addElement(new ElementInput('websiteUrl', 'Website URL'));
		$this->addElement(new ElementTextbox('blurb', 'Blurb', null, 'A blurb describes the organizer, prehaps the year you started, how experienced you are, or if you like cake. Its best to leave event specific information to when you go to create events.'));

		$this->addButtons(Form::BTN_SUBMIT);
	}

	public function validateExtended() {
		if (!$this->isOrganizerNameUnique()) {
			$this->getElement('title')->setValidationError("There is another organizer with this name.");
		}
	}

	public function isOrganizerNameUnique() {
		global $db;

		$sql = 'SELECT title FROM organizers WHERE title = :title LIMIT 1';
		$stmt = $db->prepare($sql);
		
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->execute();

		return ($stmt->numRows() == 0) ? true : false;		
	}

	public function process() {
		global $db;

		$sql = 'INSERT INTO organizers (title, websiteUrl, published, blurb, created) VALUES (:title, :websiteUrl, :published, :blurb, :created) ';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':websiteUrl', $this->getElementValue('websiteUrl'));
		$stmt->bindValue(':blurb', $this->getElementValue('blurb'));
		$stmt->bindValue(':created', date(DATE_ATOM));

		if (Session::getUser()->hasPriv('CREATE_ORGANIZERS')) {
			$stmt->bindValue(':published', 1);
			$stmt->execute();

			addHistoryLink('viewOrganizer.php?id='. $db->lastInsertId(), 'Created org: ' . $this->getElementValue('title'));

			redirect('account.php', 'Organizer created.');
		} else {
			$stmt->bindValue(':published', 0);
			$stmt->execute();

			$sql = 'UPDATE users SET organization = :organization WHERE id = :userId LIMIT 1';
			$stmt = $db->prepare($sql);
			$stmt->bindValue(':organization', $db->lastInsertId());
			$stmt->bindValue(':userId', Session::getUser()->getId());
			$stmt->execute();

			// Refresh the cached organizer.
			Session::getUser()->getData('organization', false); 

			redirect('account.php', 'Organizer assigned to you.');
		}



	}
}

?>
