<?php

require_once 'includes/common.php';

global $tpl;


if (defined('TITLE')) {
	$tpl->assign('title', TITLE);
}

use \libAllure\Session;

$tpl->assign('isLoggedIn', Session::isLoggedIn());
$tpl->assign('username', Session::isLoggedIn() ? Session::getUser()->getUsername() : 'Guest');
//$tpl->register_modifier('floatToMoney', 'floatToMoney');
$tpl->display('header.tpl');

?>
