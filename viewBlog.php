<?php

require_once 'includes/widgets/header.php';

$sql = 'SELECT id, title, content FROM blogPosts';
$blogPosts = $db->prepare($sql)->fetchAll();

$tpl->assign('blogPosts', $blogPosts);
$tpl->display('viewBlog.tpl');

require_once 'includes/widgets/footer.php';

?>
