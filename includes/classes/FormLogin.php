<?php

require_once 'jwrCommonsPhp/Form.php';

class FormLogin extends Form {
	public function __construct() {
		parent::__construct('formLogin', 'Login');

		$this->addElement(Element::factory('text', 'username', 'Username'));
		$this->addElement(Element::factory('password', 'password', 'Password'));

		$this->addButtons(Form::BTN_SUBMIT);
		$this->getElement('submit')->setCaption('Login');
	}

	public function process() {
	}
}

?>
