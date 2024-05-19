<?php

use \libAllure\Form;
use \libAllure\Session;
use \libAllure\Logger;
use \libAllure\ElementInput;
use \libAllure\ElementDate;
use \libAllure\ElementFile;
use \libAllure\ElementCheckbox;
use \libAllure\ElementHidden;
use \libAllure\ElementTextbox;

class FormEditOrganizer extends Form {
	public function __construct() {
		parent::__construct('formEditOrganizer', 'Edit Organizer');

		$organizer = fetchOrganizer($_REQUEST['formEditOrganizer-id']);

		if (Session::getUser()->hasPriv('PUBLISH_ORGANIZERS')) {
			$this->addElement(new ElementCheckbox('published', 'Published', $organizer['published']));
		}

                $this->addElement(new ElementInput('title', 'Title', $organizer['title']));
		$this->addElement(new ElementHidden('id', null, $organizer['id']));
		$this->addElement(new ElementInput('websiteUrl', 'Website', $organizer['websiteUrl']));
		$this->addElement(new ElementDate('assumedStale', 'Assumed stale since', $organizer['assumedStale']));
		$this->addElement(new ElementInput('steamGroupUrl', 'Steam group URL', htmlify($organizer['steamGroupUrl'])));
		$this->getElement('steamGroupUrl')->setMinMaxLengths(0, 255);
		$this->addElement(new ElementTextbox('blurb', 'Blurb', $organizer['blurb']));
                $this->addElement(new ElementFile('banner', 'Banner image', null, 'Your organizer banner image. Preferably a PNG, maximum image size is 468x160'));
                $this->getElement('banner')->tempDir = UPLOAD_TEMP_DIR;
		$this->getElement('banner')->destinationDir = 'resources/images/organizer-logos/';
		$this->getElement('banner')->destinationFilename = $organizer['id'] . '.jpg';
		$this->getElement('banner')->setMaxImageBounds(810, 306);

                $this->addElement(new ElementCheckbox('useFavicon', 'Use site favicon', $organizer['useFavicon'], 'Favicons are collected periodically (about once per day). You can see which favicon the site collected for you at this URL: <a href = "resources/images/organizer-favicons/' . $organizer['id'] . '.png">HERE</a>'));

		if (!Session::hasPriv('EDIT_ORGANIZER') && Session::getUser()->getData('organization') != $organizer['id']) {
			throw new PermissionsException();
		}

                $this->addDefaultButtons('Save');
	}

	public function process() {
		global $db;

		$sql = 'UPDATE organizers SET published = :published, title = :title, websiteUrl = :websiteUrl, assumedStale = :assumedStale, steamGroupUrl = :steamGroupUrl, blurb = :blurb, useFavicon = :useFavicon WHERE id = :id LIMIT 1';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $this->getElementValue('id'));
		$stmt->bindValue(':title', $this->getElementValue('title'));
		$stmt->bindValue(':websiteUrl', $this->getElementValue('websiteUrl'));
		$stmt->bindValue(':assumedStale', $this->getElementValue('assumedStale'));
		$stmt->bindValue(':steamGroupUrl', $this->getElementValue('steamGroupUrl'));
		$stmt->bindValue(':blurb', $this->getElementValue('blurb'));
		$stmt->bindValue(':useFavicon', $this->getElementValue('useFavicon'));

		if (Session::getUser()->hasPriv('PUBLISH_ORGANIZERS')) {
			$stmt->bindValue(':published', $this->getElementValue('published'));
		} else {
			$stmt->bindValue(':published', 0);
		}

		$stmt->execute();

		$this->getElement('banner')->savePng();

		Logger::messageDebug('Organizer ' . $this->getElementValue('title') . ' (' . $this->getElementValue('id') . ') edited by: ' . Session::getUser()->getUsername(), 'EDIT_ORGANIZER');
		redirect('viewOrganizer.php?id=' . $this->getElementValue('id'), 'Organizer updated.');
	}

}

?>
