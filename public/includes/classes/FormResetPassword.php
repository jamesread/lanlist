<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementPassword;
use libAllure\ElementInput;
use libAllure\DatabaseFactory;

class FormResetPassword extends Form
{
    public function __construct()
    {
        parent::__construct('formResetPassword', 'Reset password');

        if (Session::isLoggedIn()) {
            throw new Exception('It makes no sense to reset your password while logged in.');
        }

        $this->addElement(new ElementInput('resetCode', 'Reset code from your email'));
        $this->getElement('resetCode')->setMinMaxLengths(5, 15);
        $this->getElement('resetCode')->setRequired(true);

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
        $sql = 'SELECT u.id, date_add(u.resetCodeExpiry, interval 1 hour) AS resetCodeExpiry FROM users u wHERE u.resetCode = :resetCode LIMIT 1';
        $stmt = DatabaseFactory::getInstance()->prepare($sql);
        $stmt->execute([
            'resetCode' => $this->getElementValue('resetCode'),
        ]);

        if ($stmt->numRows() == 0) {
            $this->getElement('resetCode')->setValidationError('Invalid code');
            return;
        }

        $user = $stmt->fetchRow();
        if ($user['resetCodeExpiry'] < time()) {
            $this->getElement('resetCode')->setValidationError('Code expired');
        }

        if (strlen($this->getElementValue('password1')) < 6) {
            $this->setElementError('password1', 'Please enter a password that is at least 6 characters long.');
        }

        if ($this->getElementValue('password1') != $this->getElementValue('password2')) {
            $this->setElementError('password2', 'The passwords do not match.');
        }
    }

    public function process()
    {
        $sql = 'UPDATE users u SET u.password = :password WHERE u.resetCode = :resetCode LIMIT 1';
        $stmt = DatabaseFactory::getInstance()->prepare($sql);
        $stmt->execute([
            'password' => sha1($this->getElementValue('password1')),
            'resetCode' => $this->getElementValue('resetCode'),
        ]);

        if ($stmt->numRows() == 1) {
            redirect('account.php', 'Your password has been changed.');
        } else {
           throw new libAllure\exceptions\SimpleFatalError('Your password was not changed!'); 
        }

    }
}
