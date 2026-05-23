<?php

if (!defined('SIDEBAROUTPUT')) {
    startSidebar();
}

global $db;
global $tpl;

$tplVars = $tpl->getTemplateVars();
if (!is_array($tplVars) || !array_key_exists('includeInlineEdit', $tplVars)) {
    $tpl->assign('includeInlineEdit', false);
}
if (!is_array($tplVars) || !array_key_exists('isModerator', $tplVars)) {
    $tpl->assign('isModerator', false);
}

$tpl->display('footer.tpl');

exit;
