<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>

<head>
    <title>lanlist.org - Redirecting</title>

    <link rel = "stylesheet" type = "text/css" href = "resources/stylesheets/main.css" />
    <link rel = "shortcut icon" type = "image/png" href = "resources/images/favicon.png" />
    <meta name = "viewport" content = "width=device-width" />

	<?php 
	
	if (defined('REDIRECT')) {
		echo '<meta http-equiv = "refresh" content = "3; url=' . REDIRECT . '"/>';
	}
	?>
</head>

<body>
	<div id = "content" class = "minimal">

	<img src = "resources/images/lanlist.org-banner.png" /><br /><br />
