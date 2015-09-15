<?php

require_once 'includes/common.php';

if (!Session::isLoggedIn()) {
	redirect('index.php', 'You are already logged out!');
} else {
	Session::logout();
	redirect('index.php', 'Logged out.');
}

?>
