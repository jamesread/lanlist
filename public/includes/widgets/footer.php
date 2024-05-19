<?php 

if (!defined('SIDEBAROUTPUT')) {
	startSidebar();
}

global $db;
global $tpl;

$tpl->assign('queryCount', $db->queryCount);
$tpl->display('footer.tpl');	

exit; 

?>
