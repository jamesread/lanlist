<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementPassword;

class FormChangePassword extends Form
{
    public function __construct()
    {
        parent::__construct('formChangePassword', 'Change password');

        if (!Session::isLoggedIn()) {
            throw new Exception('You need to be logged in to change your password.');
        }

        $this->addElement(new ElementPassword('password1', 'New password'));
        $this->addElement(new ElementPassword('password2', 'Password (confirm)'));

        $this->addDefaultButtons('Change Password');
    }

    protected function validateExtended()
    {
        $this->validatePassword();
    }

    private function validatePassword()
    {
        if (strlen($this->getElementValue('password1')) < 6) {
            $this->setElementError('password1', 'Please enter a password that is at least 6 characters long.');
        }

        if ($this->getElementValue('password1') != $this->getElementValue('password2')) {
            $this->setElementError('password2', 'The passwords do not match.');
        }
    }

    public function process()
    {
        Session::getUser()->setData('password', sha1($this->getElementValue('password1')));

        redirect('account.php', 'Your password has been changed.');
    }
}
