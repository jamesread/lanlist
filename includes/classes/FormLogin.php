<?php

use \libAllure\Form;
use \libAllure\Session;
use \libAllure\ElementInput;
use \libAllure\ElementPassword;

class FormLogin extends Form {
	public function __construct() {
		parent::__construct('formLogin', 'Login');

		$this->addElement(new ElementInput('username', 'Username'));
		$this->addElement(new ElementPassword('password', 'Password'));

		$this->addButtons(Form::BTN_SUBMIT);
		$this->getElement('submit')->setCaption('Login');
	}

	public function process() {
	}
}

?>
