<?php

class FormEditOrganizer extends Form {
	public function __construct() {
		parent::__construct('formEditOrganizer', 'Edit Organizer');

		$organizer = fetchOrganizer($_REQUEST['formEditOrganizer-id']);

		if (Session::getUser()->hasPriv('PUBLISH_ORGANIZERS')) {
			$this->addElement(Element::factory('checkbox', 'published', 'Published', $organizer['published']));
		}

		$this->addElement(Element::factory('text', 'title', 'Title', $organizer['title']));
		$this->addElement(Element::factory('hidden', 'id', null, $organizer['id']));
		$this->addElement(Element::factory('text', 'websiteUrl', 'Website', $organizer['websiteUrl']));
		$this->addElement(Element::factory('text', 'steamGroupUrl', 'Steam group URL', htmlify($organizer['steamGroupUrl'])));
		$this->getElement('steamGroupUrl')->setMinMaxLengths(0, 255);
		$this->addElement(Element::factory('textarea', 'blurb', 'Blurb', $organizer['blurb']));
		$this->addElement(Element::factory('file', 'banner', 'Banner image', null, 'Your organizer banner image. Preferably a PNG, maximum image size is 468x160'));
		$this->getElement('banner')->destinationDir = 'resources/images/organizer-logos/';
		$this->getElement('banner')->destinationFilename = $organizer['id'] . '.jpg';
		$this->getElement('banner')->setMaxImageBounds(468, 160);

		if (!Session::hasPriv('EDIT_ORGANIZER') && Session::getUser()->getData('organization') != $organizer['id']) {
			throw new PermissionsException();
		}

		$this->addButtons(Form::BTN_SUBMIT);
	}

	public function process() {
		global $db;

		$sql = 'UPDATE organizers SET published = :published, title = :title, websiteUrl = :websiteUrl, steamGroupUrl = :steamGroupUrl, blurb = :blurb WHERE id = :id LIMIT 1';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $this->getElementValue('id'));
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':websiteUrl', $this->getElementValue('websiteUrl'));
		$stmt->bindValue(':steamGroupUrl', $this->getElementValue('steamGroupUrl'));
		$stmt->bindValue(':blurb', $this->getElementValue('blurb'));

		if (Session::getUser()->hasPriv('PUBLISH_ORGANIZERS')) {
			$stmt->bindValue(':published', $this->getElementValue('published'));
		} else {
			$stmt->bindValue(':published', 0);
		}

		$stmt->execute();

		$this->getElement('banner')->savePng();

		Logger::messageDebug('Organizer ' . $this->getElementValue('title') . ' (' . $this->getElementValue('id') . ') edited by: ' . Session::getUser()->getUsername(), LocalEventType::EDIT_ORGANIZER);
		redirect('viewOrganizer.php?id=' . $this->getElementValue('id'), 'Organizer updated.');
	}

}

?>
