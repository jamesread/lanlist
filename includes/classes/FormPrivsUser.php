<?php

use \libAllure\Form;

class FormPrivsUser extends Form {
	public function __construct() {
		parent::__construct('formPrivsUser');

		$this->addElement(Element::factory('text', 'uid', null));
	}
}

?>
