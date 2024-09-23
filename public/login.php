<?php

require_once 'includes/common.php';
require_once 'includes/classes/FormLogin.php';

use libAllure\Session;
use libAllure\Logger;

$f = new FormLogin();

if ($f->validate()) {
    try {
        $username = $f->getElementValue('username');
        Session::checkCredentials($username, $f->getElementValue('password'));

        //setcookie('mylocation', Session::getUser()->getData('location', ''));

        redirect('index.php', 'You have logged in.');
    } catch (\libAllure\exceptions\IncorrectPasswordException $e) {
        Logger::messageAudit('Failed login for ' . $username . ', password wrong.', 'LOGIN_FAILURE_PASSWORD');

        $f->setElementError('password', 'Incorrect password.');
    } catch (\libAllure\exceptions\UserNotFoundException $e) {
        Logger::messageAudit('Failed login for ' . $username . ', nonexistant user.', 'LOGIN_FAILURE_USERNAME');

        $f->setElementError('username', 'User not found');
    }
} elseif (isset($_GET['formLogin-username'])) {
    $username = htmlentities($_GET['formLogin-username']);
    $f->getElement('username')->setValue($username);
}

if (Session::isLoggedIn()) {
    redirect('index.php', 'You are already logged in!');
}

define('TITLE', 'Login to your account');

require_once 'includes/widgets/header.minimal.php';

$tpl->displayForm($f);

?>
</div>

<div class = "infobox">
    <h2>Forgotten your password?</h2>
    <p>You can <a href = "formHandler.php?formClazz=FormRequestPasswordReset">request a password reset</a>.<p>
</div>

<div class = "infobox" style = "text-align: left;">
    <h2>Register for an account</h2>
    <p>If you do not yet have an account <a href = "register.php">register here</a>.</p>
</div>
<?php

require_once 'includes/widgets/footer.php';

