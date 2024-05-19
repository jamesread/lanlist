<?php

use libAllure\Form;
use libAllure\ElementHtml;
use libAllure\Session;

class FormDeleteUser extends Form
{
    public function __construct()
    {
        parent::__construct('formDeleteUser', 'Delete user?');

        requirePriv('USER_DELETE');

        $this->addElementReadOnly('User', $_REQUEST['formDeleteUser-uid'], 'uid');
        $this->addElement(new ElementHtml('msg', null, 'Sure?'));
                $this->addDefaultButtons('Delete user');
    }

    public function process()
    {
        global $db;

        $sql = 'DELETE FROM users WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $this->getElementValue('uid'));
        $stmt->execute();

        redirect('listUsers.php', 'Dead and gone.');
    }
}
