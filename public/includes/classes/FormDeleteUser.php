<?php

use libAllure\Form;
use libAllure\ElementHtml;
use libAllure\Session;
use libAllure\Logger;
use libAllure\User;

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

        $uid = (int) $this->getElementValue('uid');
        $target = User::getUserById($uid);
        $targetUsername = $target ? $target->getUsername() : '(unknown)';

        $sql = 'DELETE FROM users WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $uid);
        $stmt->execute();

        Logger::messageAudit(
            'User ' . $targetUsername . ' (' . $uid . ') deleted by ' . Session::getUser()->getUsername(),
            'DELETE_USER',
            ['relatedUser' => $uid]
        );

        redirect('listUsers.php', 'Dead and gone.');
    }
}
