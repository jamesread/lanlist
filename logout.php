<?php

require_once 'includes/common.php';

use \libAllure\Session;

if (!Session::isLoggedIn()) {
	redirect('index.php', 'You are already logged out!');
} else {
	Session::logout();
	redirect('index.php', 'Logged out.');
}

?>
