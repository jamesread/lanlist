<?php

require_once 'includes/common.php';
require_once 'includes/classes/FormRegister.php';

use libAllure\Session;
use libAllure\Logger;

$fRegister = new FormRegister();

if ($fRegister->validate()) {
    $fRegister->process();
}

define('TITLE', 'Login to, or Register an account');
require_once 'includes/widgets/header.minimal.php';

$tpl->displayForm($fRegister);

?>
</div>
<div class = "infobox" style = "text-align: left;">
    <h2>Why register?</h2>
    <p>If you decide to register, you will be able to submit your own events, get directions and get a personalized list of LAN parties near you in the future.</p>
    <p>If you already have an account, then <a href = "login.php">Login</a>!</p>
</div>
<?php 

require_once 'includes/widgets/footer.php';

