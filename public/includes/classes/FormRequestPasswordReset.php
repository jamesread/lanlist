<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\ElementPassword;
use libAllure\ElementEmail;
use libAllure\DatabaseFactory;

class FormRequestPasswordReset extends Form
{
    private ?array $user;

    public function __construct()
    {
        parent::__construct('formRequestPasswordReset', 'Request password reset');

        if (Session::isLoggedIn()) {
            throw new Exception('It makes no sense to request a password reset if you are already logged in.');
        }

        $this->addElement(new ElementEmail('email', 'Your email address'));
        $this->addDefaultButtons('Send me a reset code');
    }

    protected function validateExtended()
    {
        $this->validateExistingUser();
    }

    private function validateExistingUser()
    {
        $sql = 'SELECT * FROM users u WHERE u.email = :email';
        $stmt = DatabaseFactory::getInstance()->prepare($sql);
        $res = $stmt->execute([
            'email' => $this->getElementValue('email'),
        ]);
        
        if ($stmt->numRows() == 0) {
            $this->setElementError('email', 'No user found with that email address');
        } else {
            $this->user = $stmt->fetchRow();
        }
    }

    public function process()
    {
        if ($this->user == null) {
            throw new Exception('Cannot process password reset');
        }

        $resetCode = uniqid();

        $sql = 'UPDATE users u SET u.resetCode = :resetCode, u.resetCodeExpiry = now() WHERE u.email = :email LIMIT 1';
        $stmt = DatabaseFactory::getInstance()->prepare($sql);
       
        $res = $stmt->execute([
            'email' => $this->user['email'],
            'resetCode' => $resetCode,
        ]);

        $body = 'Your password reset code is: ' . $resetCode . '';

        sendEmail($this->user['email'], 
            $body,
           'Password Reset Requested',
        );
        
        redirect('formHandler.php?formClazz=FormResetPassword', 'Please check your email for a reset code.');
    }
}
