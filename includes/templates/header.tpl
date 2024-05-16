<!DOCTYPE html>
<html>

<head>
	<title>{$title|default:"A List of LAN Parties"} &bull; lanlist.org</title>

	<meta name = "author" content = "lanlist.org Admin Team" />
	<meta name = "description" content = "A list of LAN Parties" />
	<meta name = "keywords" content = "lan party, lan, list" />

	<meta name = "viewport" content = "width=device-width" />

	<link rel = "stylesheet" type = "text/css" href = "resources/stylesheets/main.css" />
	<link rel = "shortcut icon" type = "image/png" href = "resources/images/favicon.png" />
	<link rel = "alternate" type = "application/rss+xml" title = "lanlist.org - A list of LAN Parties" href = "api.php?function=events&amp;format=rss" />

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script type = "text/javascript" src = "resources/javascript/jquery.dataTables.min.js"></script>
	<script type = "text/javascript" src = "resources/javascript/jquery.ui.datetime.src.js"></script>

	<script type = "text/javascript" src = "resources/javascript/map.js"></script>
	<script type = "text/javascript" src = "resources/javascript/common.js"></script>

	<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDR2HQth0T-LwBf4vwmfFWlg_lQC5pFkw0" sync = "async"></script>

	<script type="text/javascript">
		{literal}
		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-17036308-2']);
		  _gaq.push(['_setDomainName', 'none']);
		  _gaq.push(['_setAllowLinker', true]);
		  _gaq.push(['_trackPageview']);

		  (function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		$(document).ready(function() {
			setupSortableTables();
		});
		{/literal}
	</script>
</head>

<body>
	<div id = "header">
		<div id = "navigation">
			<div id = "logo">
				<img src = "resources/images/lanlist.org-banner.png" alt = "lanlist.org" />
				<br /><small>A List Of LAN Parties</small>
			</div>

			<ul id = "mainNavigation">
				<li><a href = "eventsMap.php">Map</a></li>
				<li><a href = "eventsList.php">List</a></li>
			{if $isLoggedIn}
				<li><strong><a href = "account.php">{$username}</a></strong></li>
			{else}
				<li><a href = "loginregister.php">Login/Register</a></li>
			{/if}
			</ul>
		</div>

		{*
		<div id = "advertisingBanner">
		{include file = "banner.tpl"}
		</div>
		*}
	</div>

	{if 0}
	<p class = "alert">The website is undergoing maintenance. Feel free to browse around, but things will probably be more broken than normal.</p>
	{/if}

	<div id = "content">
