<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementInput;
use libAllure\DatabaseFactory;
use libAllure\Logger;

if (!Session::hasPriv('SUPERUSER')) {
    die('Permission denied');
}

class FormCreatePermission extends Form
{
    public function __construct()
    {
        parent::__construct('formPrivsUser');

                $this->addElement(new ElementInput('permission', 'Permissions'));
        $this->addDefaultButtons();
    }

    public function process()
    {
        $stmt = DatabaseFactory::getInstance()->prepare('INSERT INTO permissions (`key`) values (:permission) ');
        $permKey = $this->getElementValue('permission');
        $stmt->bindValue(':permission', $permKey);
        $stmt->execute();

        Logger::messageAudit(
            'Permission key "' . $permKey . '" created by ' . Session::getUser()->getUsername(),
            'CREATE_PERMISSION'
        );

        redirect('account.php', 'Permission created');
    }
}
