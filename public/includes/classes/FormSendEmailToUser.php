<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementInput;
use libAllure\ElementHidden;
use libAllure\ElementTextbox;
use libAllure\ElementReadOnly;

class FormSendEmailToUser extends Form
{
    private ?\libAllure\User $user;
    private array $organizer;

    public function __construct()
    {
        parent::__construct('formSendEmailToUser', 'Send email to user');

        Session::requirePriv('SEND_EMAIL');

        $elUid = $this->addElementReadOnly('User', 0, 'uid');
        $sendTo = $this->addElementReadOnly('Email', '', 'email');

        if (isset($_REQUEST['formSendEmailToUser-email'])) {
            $to = $_REQUEST['formSendEmailToUser-email'];

            $sendTo->setValue($to);
            $this->getElement('email')->setValue($to);
        } else if (isset($_REQUEST['formSendEmailToUser-uid'])) {
            $uid = $_REQUEST['formSendEmailToUser-uid'];
            $uid = intval($uid);
            $this->user = \libAllure\User::getUserById($uid);

            $sql = 'SELECT o.* FROM users u LEFT JOIN organizers o ON u.organization = o.id WHERE u.id = :userId LIMIT 1';
            $stmt = \libAllure\DatabaseFactory::getInstance()->prepare($sql);
            $stmt->bindValue(':userId', $this->user->getId());
            $stmt->execute();

            if ($stmt->numRows()) {
                $this->organizer = $stmt->fetchRow();
            }

            $sendTo->description = $this->user->getData('email') .  ', User: <a href = "viewUser.php?id=' . $this->user->getId() . '">' . $this->user->getData('username') . '</a> Organizer: <a href = "viewOrganizer.php?id=' . $this->organizer['id'] . '">' . $this->organizer['title'] . '</a>' ;
            $sendTo->value = $this->user->getData('email');
        }

        if (empty($this->organizer)) {
            $this->organizer = array('title' => '???', 'id' => '0');
        }

        $this->addElement(new ElementInput('subject', 'Subject', 'Message from a human!'));

        $this->addElement(new ElementTextbox('body', 'Body', "", 'No footer will be appended. From: mailer@lanlist.org'));
        $s = <<<EOF
document.getElementById('formSendEmailToUser-body').setAttribute('rows', '20');
EOF;
        $this->addScript($s);

        $this->loadTemplate();

        $this->addDefaultButtons('Send email');
    }

    private function loadTemplate()
    {
        if (isset($_REQUEST['template'])) {
            $template = $_REQUEST['template'];
        } else {
            $template = 'default';
        }

        global $tpl;

        if (!empty($this->user)) {
            $tpl->assign('username', $this->user->getUsername());
            $tpl->assign('organizationUrl', 'http://lanlist.org/viewOrganizer.php?id=' . $this->user->getData('organization'));
            $tpl->assign('organizer', $this->organizer);
        }

        $content = $tpl->fetch('email.' . $template . '.tpl');
        $subject = 'Message from a human!';

        preg_match('#^Subject: (.+)#', $content, $matches);

        if (count($matches) == 2) {
            $content = trim(str_replace($matches[0], '', $content));
            $subject = $matches[1];
        }

        $this->getElement('body')->setValue($content);
        $this->getElement('subject')->setValue($subject);
    }

    public function process()
    {
        $content = nl2br($this->getElementValue('body'));
        $content .= '<br /><br /><small>This is NOT an automated email, a human wrote this email and sent it to you directly from lanlist.org. We try not to spam our users and hope you found this email useful. If you REALLY hate us and want to stop receiving email from us then login to http://lanlist.org and remove your email address from your user profile. You should be able to reply to this email and talk to a human, or check http://lanlist.org/contact.php for our latest contact details. </small>';

        $subject = $this->getElementValue('subject');

        sendEmail($this->getElementValue('email'), $content, $subject, false);

        redirect('listUsers.php?', 'Email sent.');
    }
}
