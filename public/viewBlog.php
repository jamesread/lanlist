<?php

define('TITLE', 'Blog');
define('META_DESCRIPTION', 'Blog posts and longer updates from the lanlist and LAN party community.');
require_once 'includes/widgets/header.php';

$sql = 'SELECT id, title, content FROM blogPosts';
$blogPosts = $db->prepare($sql)->fetchAll();

$tpl->assign('blogPosts', $blogPosts);
$tpl->display('viewBlog.tpl');

require_once 'includes/widgets/footer.php';
