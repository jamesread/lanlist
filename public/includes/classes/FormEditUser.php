<?php

require_once 'includes/classes/FormHelpers.php';

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementInput;
use libAllure\ElementHidden;
use libAllure\ElementSelect;
use libAllure\ElementHtml;
use libAllure\ElementPassword;
use libAllure\DatabaseFactory;

class FormEditUser extends Form
{
    public function __construct()
    {
        parent::__construct('formEditUser', 'Edit User');

        $user = $this->getUser();

        $this->addElementReadOnly('Username', $user['username']);
        $this->addElement(new ElementHidden('uid', null, $user['id']));

        $this->addElement(new ElementInput('email', 'Email Address', $user['email']));
        $this->getElement('email')->setMinMaxLengths(0, 64);

        $steam = $this->addElement(new ElementInput('usernameSteam', 'Steam Username', $user['usernameSteam'], 'Plaese do include your Steam username - its a good way for us to get in contact.'));
        $steam->setMinMaxLengths(0, 64);
        $steam->description = 'Message each other like it is 1999!';

        $discord = $this->addElement(new ElementInput('discordUser', 'Discord User ID', $user['discordUser']));
        $discord->setMinMaxLengths(0, 128);
        $discord->description = 'Open Discord, click your profile icon in the bottom-left, and click "Copy User ID". This field is visible by admins, so they can message you.';

        if (Session::hasPriv('EDIT_USER')) {
            $this->addElement(new ElementHtml(null, null, 'Admin fields'));

            $this->addElement($this->getGroupSelectionElement($user['group']));
            $this->addElement(FormHelpers::getOrganizerList(true));
            $this->getElement('organizer')->setValue($user['organization']);
            $this->addElement(new ElementPassword('password', 'New Password'));
            $this->getElement('password')->setOptional(true);
        }

        $this->addDefaultButtons('Save user');
    }

    private function getGroupSelectionElement($currentGroup)
    {
        global $db;

        $el = new ElementSelect('group', 'Primary group');

        $sql = 'SELECT g.id, g.title FROM groups g';
        $stmt = $db->prepare($sql);
        $stmt->execute();

        foreach ($stmt->fetchAll() as $group) {
            $el->addOption($group['title'], $group['id']);
        }

        $el->setValue($currentGroup);

        return $el;
    }

    private function getUser()
    {
        global $db;

        if (Session::getUser()->hasPriv('EDIT_USER') && isset($_REQUEST['formEditUser-uid'])) {
            $id = intval($_REQUEST['formEditUser-uid']);
        } else {
            $id = Session::getUser()->getId();
        }

        $sql = 'SELECT u.* FROM users u WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $user = $stmt->fetchRow();

        return $user;
    }

    public function changePassword($newPassword)
    {
        global $authBackend;

        $sql = 'UPDATE users SET password = :password WHERE id = :id';
        $stmt = DatabaseFactory::getInstance()->prepare($sql);
        $stmt->bindValue(':id', $this->getElementValue('uid'));
        $stmt->bindValue(':password', $authBackend->hashPassword($newPassword));
        $stmt->execute();
        echo 'password changed for uid: ' . $this->getElementValue('uid');
    }

    public function process()
    {
        global $db;

        $sql = 'UPDATE users SET `group` = :group, email = :email, organization = :organizer, usernameSteam = :usernameSteam, discordUser = :discordUser WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $this->getElementValue('uid'));
        $stmt->bindValue(':email', $this->getElementValue('email'));
        $stmt->bindValue(':usernameSteam', $this->getElementValue('usernameSteam'));
        $stmt->bindValue(':discordUser', $this->getElementValue('discordUser'));

        if (Session::getUser()->hasPriv('EDIT_USER')) {
            $stmt->bindValue(':organizer', $this->getElementValue('organizer'));
            $stmt->bindValue(':group', $this->getElementValue('group'));
            $stmt->execute();

            $newPassword = $this->getElementValue('password');

            if (!empty($newPassword)) {
                $this->changePassword($newPassword);
            }

            redirect('viewUser.php?id=' . $this->getElementValue('uid'), 'User edited.');
        } else {
            $user = $this->getUser();
            $stmt->bindValue(':organizer', $user['organization']);
            $stmt->bindValue(':group', $user['group']);
            $stmt->execute();

            Session::getUser()->getData('username', false);

            redirect('account.php?', 'Updated profile');
        }
    }
}
