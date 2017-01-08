<?php

use \libAllure\Form;
use \libAllure\Session;

class FormChangePassword extends Form {
	public function __construct() {
		parent::__construct('formChangePassword', 'Change password');

		if (!Session::isLoggedIn()) {
			throw new Exception('You need to be logged in to change your password.');
		}

		$this->addElement(Element::factory('password', 'password1', 'New password'));
		$this->addElement(Element::factory('password', 'password2', 'Password (confirm)'));
		
		$this->addButtons(Form::BTN_SUBMIT);
	}

	protected function validateExtended() {
		$this->validatePassword();
	}

	private function validatePassword() {
		if (strlen($this->getElementValue('password1')) < 6) {
			$this->setElementError('password1', 'Please enter a password that is at least 6 characters long.');
		}

		if ($this->getElementValue('password1') != $this->getElementValue('password2')) {
			$this->setElementError('password2', 'The passwords do not match.');
		}
	}

	public function process() {
		Session::getUser()->setData('password', sha1($this->getElementValue('password1')));

		redirect('account.php', 'Your password has been changed.');
	}
}

?>
