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
use libAllure\Logger;

class FormEditUser extends Form
{
    private $showModeratorNewsletterFrequency = false;
    private $showOrganizerEmailPreferences = false;

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

        if ((int) $user['group'] === MODERATOR_GID) {
            $this->showModeratorNewsletterFrequency = true;

            $newsletterFrequency = new ElementSelect(
                'moderatorNewsletterFrequency',
                'Moderator newsletter',
                $user['moderatorNewsletterFrequency'] ?? 'daily'
            );
            foreach (lanlistModeratorNewsletterFrequencyOptions() as $value => $label) {
                $newsletterFrequency->addOption($label, $value);
            }
            $newsletterFrequency->description = 'How often you receive the automated moderator site-checks email.';
            $this->addElement($newsletterFrequency);
        }

        if ((int) ($user['organization'] ?? 0) > 0) {
            $this->showOrganizerEmailPreferences = true;

            $this->addElement(new ElementHtml(null, null, 'Email notifications'));

            $organizerUpdates = new ElementSelect(
                'organizerUpdateEmails',
                'Organizer update emails',
                $user['organizerUpdateEmails'] ?? 'always'
            );
            foreach (lanlistOrganizerUpdateEmailOptions() as $value => $label) {
                $organizerUpdates->addOption($label, $value);
            }
            $organizerUpdates->description = 'When someone edits your organizer profile, or for occasional post-event reminders.';
            $this->addElement($organizerUpdates);

            $eventUpdates = new ElementSelect(
                'eventUpdateEmails',
                'Event update emails',
                $user['eventUpdateEmails'] ?? 'always'
            );
            foreach (lanlistEventUpdateEmailOptions() as $value => $label) {
                $eventUpdates->addOption($label, $value);
            }
            $eventUpdates->description = 'When someone edits an event for your organizer.';
            $this->addElement($eventUpdates);
        }

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

        $targetGroup = Session::getUser()->hasPriv('EDIT_USER')
            ? (int) $this->getElementValue('group')
            : (int) $this->getUser()['group'];

        $sql = 'UPDATE users SET `group` = :group, email = :email, organization = :organizer, usernameSteam = :usernameSteam, discordUser = :discordUser';
        if ($this->showModeratorNewsletterFrequency && $targetGroup === MODERATOR_GID) {
            $frequency = $this->getElementValue('moderatorNewsletterFrequency');
            if (!array_key_exists($frequency, lanlistModeratorNewsletterFrequencyOptions())) {
                $frequency = 'daily';
            }

            $sql .= ', moderatorNewsletterFrequency = :moderatorNewsletterFrequency';
        }

        if ($this->showOrganizerEmailPreferences) {
            $organizerUpdateEmails = $this->getElementValue('organizerUpdateEmails');
            if (!array_key_exists($organizerUpdateEmails, lanlistOrganizerUpdateEmailOptions())) {
                $organizerUpdateEmails = 'always';
            }

            $eventUpdateEmails = $this->getElementValue('eventUpdateEmails');
            if (!array_key_exists($eventUpdateEmails, lanlistEventUpdateEmailOptions())) {
                $eventUpdateEmails = 'always';
            }

            $sql .= ', organizerUpdateEmails = :organizerUpdateEmails, eventUpdateEmails = :eventUpdateEmails';
        }

        $sql .= ' WHERE id = :id';

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $this->getElementValue('uid'));
        $stmt->bindValue(':email', $this->getElementValue('email'));
        $stmt->bindValue(':usernameSteam', $this->getElementValue('usernameSteam'));
        $stmt->bindValue(':discordUser', $this->getElementValue('discordUser'));

        if ($this->showModeratorNewsletterFrequency && $targetGroup === MODERATOR_GID) {
            $stmt->bindValue(':moderatorNewsletterFrequency', $frequency);
        }

        if ($this->showOrganizerEmailPreferences) {
            $stmt->bindValue(':organizerUpdateEmails', $organizerUpdateEmails);
            $stmt->bindValue(':eventUpdateEmails', $eventUpdateEmails);
        }

        if (Session::getUser()->hasPriv('EDIT_USER')) {
            $userBefore = $this->getUser();
            $uid = (int) $this->getElementValue('uid');
            $newOrganizer = (int) $this->getElementValue('organizer');
            $newGroup = $targetGroup;

            $stmt->bindValue(':organizer', $newOrganizer);
            $stmt->bindValue(':group', $newGroup);
            $stmt->execute();

            $details = 'User ' . $userBefore['username'] . ' (' . $uid . ') edited by '
                . Session::getUser()->getUsername();
            if ((int) $userBefore['organization'] !== $newOrganizer) {
                $details .= '; organization ' . $userBefore['organization'] . ' → ' . $newOrganizer;
            }
            if ((int) $userBefore['group'] !== $newGroup) {
                $details .= '; group ' . $userBefore['group'] . ' → ' . $newGroup;
            }

            $newPassword = $this->getElementValue('password');

            if (!empty($newPassword)) {
                $this->changePassword($newPassword);
                $details .= '; password changed';
            }

            $logMeta = ['relatedUser' => $uid];
            if ($newOrganizer > 0) {
                $logMeta['relatedOrganizer'] = $newOrganizer;
            }
            Logger::messageAudit($details, 'EDIT_USER', $logMeta);

            redirect('viewUser.php?id=' . $uid, 'User edited.');
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
