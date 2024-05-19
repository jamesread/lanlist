<?php

require_once 'includes/common.php';
require_once 'includes/classes/FormLogin.php';
require_once 'includes/classes/FormRegister.php';

use libAllure\Session;
use libAllure\Logger;

$fRegister = new FormRegister();

if ($fRegister->validate()) {
    $fRegister->process();
}

$f = new FormLogin();

if ($f->validate()) {
    try {
        $username = $f->getElementValue('username');
        Session::checkCredentials($username, $f->getElementValue('password'));

        //setcookie('mylocation', Session::getUser()->getData('location', ''));

        redirect('index.php', 'You have logged in.');
    } catch (\libAllure\exceptions\IncorrectPasswordException $e) {
        Logger::messageAudit('Failed login for ' . $username . ', password wrong.', 'LOGIN_FAILURE_PASSWORD');

        $f->setElementError('password', 'Password wrong.');
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

define('TITLE', 'Login to, or Register an account');
require_once 'includes/widgets/header.php';

echo '<div class = "paralellContainer">';
$tpl->displayForm($f);
echo '</div>';

echo '<div class = "paralellContainer">';
$tpl->displayForm($fRegister);
echo '</div>';

startSidebar();

?>
<div class = "infobox">
    <h2>Why register?</h2>
    <p>If you decide to register, you will be able to submit your own events, get directions and get a personalized list of LAN parties near you in the future.</p>
</div>

<div class = "infobox">
    <h2>Freeeedom!</h2>
    <p>This website was created for those of you who love LAN Parties, either staff, punters or newbies. It is free to create an account and always will be. The site is looked after, if you spot a problem then <strong>please</strong> let us know.</p>
</div>

<p>Having problems logging in or registering a new account? Get in <a href = "contact.php">contact</a>.</p>
<?php

require_once 'includes/widgets/footer.php';

?>
