<?php

use \libAllure\Form;
use \libAllure\Session;
use \libAllure\ElementInput;
use \libAllure\DatabaseFactory;

if (!Session::hasPriv('SUPERUSER')) {
    die('Permission denied');
}

class FormCreatePermission extends Form {
	public function __construct() {
		parent::__construct('formPrivsUser');

                $this->addElement(new ElementInput('permission', 'Permissions'));
		$this->addDefaultButtons();
        }

        public function process() {
            $stmt = DatabaseFactory::getInstance()->prepare('INSERT INTO permissions (`key`) values (:permission) ');
            $stmt->bindValue(':permission', $this->getElementValue('permission'));
            $stmt->execute();


	    redirect('account.php', 'Permission created');
        }
}

?>
