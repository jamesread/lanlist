<?php

require_once 'includes/widgets/header.php';

?>
<h2>Licensing - Source code</h2>
<p>The source code for this website fully open source, licensed AGPL-v3. You can find the code on <a href = "http://github.com/jamesread/lanlist/">GitHub</a>.</p>

<h2>Licensing - Database</h2>
<p>This site's content is it's database, which is built and maintained by the LAN Party Community.</p> 
<p>The list of LAN Parties on this website is licensed Creative Commons Attributution-Share-Alike 4.0. This license refers to the list in all forms - text, csv, ical, etc. Each format can be found on the <a href = "eventsList.php">list</a> page. PLEASE don't use a web-page scraping tool to hammer this webserver. For what you are allowed to do with the list, please <a href = "http://creativecommons.org/licenses/by-sa/4.0/">see the license</a>. </p>
<div id = "licenseBlock">
    <a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/">
        <img alt="Creative Commons Licence" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/4.0/88x31.png" />
    </a><br />

    <span xmlns:dct="http://purl.org/dc/terms/" href="http://purl.org/dc/dcmitype/Text" property="dct:title" rel="dct:type">The lanlist.org LAN Party Lists</span> 
    by <a xmlns:cc="http://creativecommons.org/ns#" href="http://lanlist.org/contact.php" property="cc:attributionName" rel="cc:attributionURL">lanlist.org Admin Team</a> 
    are licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/4.0/">Creative Commons Attribution-ShareAlike 4.0 Unported License</a>.<br />
</div>
<br /><br />

<h3>Link to us!</h3>
<p>If you would like to use the data, please feel free. Linking to us is part of the license though. The aptly named "<a href = "linkus.php">link to us!</a>" page gives you lots of pretty buttons that you can use. </p>
<?php

startSidebar();

$tpl->display('infobox.otherFormats.tpl');

require_once 'includes/widgets/footer.php';

?>
