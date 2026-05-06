<?php

define('TITLE', 'News');
define('META_DESCRIPTION', 'News and short announcements about lanlist and the LAN party community.');
require_once 'includes/widgets/header.php';

$sql = 'SELECT n.id, n.title, n.content FROM news n';
$stmt = $db->prepare($sql);
$stmt->execute();

$tpl->assign('news', $stmt->fetchAll());
$tpl->display('news.tpl');


require_once 'includes/widgets/footer.php';
