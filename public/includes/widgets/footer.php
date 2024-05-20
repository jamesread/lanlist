<?php

if (!defined('SIDEBAROUTPUT')) {
    startSidebar();
}

global $db;
global $tpl;

$tpl->display('footer.tpl');

exit;
