<?php

use \libAllure\Form;
use \libAllure\ElementHtml;

class FormPrivsUser extends Form {
	public function __construct() {
		parent::__construct('formPrivsUser');

		$this->addElement(new ElementHtml('Notice', '', 'Cannot change user permissions at the moment. Users get group permissions.'));
		$this->addDefaultButtons();
	}
}

?>
