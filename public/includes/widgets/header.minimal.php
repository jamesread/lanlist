<!DOCTYPE html>

<html lang = "en">

<head>
<title><?php echo SITE_TITLE; ?> - Redirecting</title>

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
    <main class = "minimal">
        
    <div class = "infobox">

    <h1><a href = "/"><?php echo SITE_TITLE_DOMAIN; ?><span class = "tld"><?php echo SITE_TITLE_TLD; ?></span></a></h1>
<br /><br />
