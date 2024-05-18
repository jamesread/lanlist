<!DOCTYPE html>
<html>

<head>
	<title>{$title|default:"A List of LAN Parties"} &bull; {$siteTitle}</title>

	<meta name = "author" content = "UKLans Community" />
	<meta name = "description" content = "A list of LAN Parties" />
	<meta name = "keywords" content = "lan party, lan, list" />

	<meta name = "viewport" content = "width=device-width" />

	<link rel = "stylesheet" type = "text/css" href = "resources/stylesheets/main.css" />
	<link rel = "shortcut icon" type = "image/png" href = "resources/images/favicon.png" />
	<link rel = "alternate" type = "application/rss+xml" title = "{$siteTitle} - A list of LAN Parties" href = "api.php?function=events&amp;format=rss" />

	<script type = "text/javascript" src = "resources/javascript/map.js"></script>

	<script type="text/javascript">
		const key = "{$mapsApiKey}";
		{literal}

  (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: key,
    v: "weekly",
  });

		{/literal}
	</script>
</head>

<body>
	<header>
		<h1><a href = "/">{$siteTitleDomain}<span class = "tld">{$siteTitleTld}</span></a></h1>
		<nav>
			<ul id = "mainNavigation">
				<li><a href = "eventsMap.php">Map</a></li>
				<li><a href = "eventsList.php">List</a></li>
			{if $isLoggedIn}
				<li><strong><a href = "account.php">{$username}</a></strong></li>
			{else}
				<li><a href = "loginregister.php">Login/Register</a></li>
			{/if}
			</ul>
		</nav>
	</header>

	{if !empty($alertMessage)}
	<p class = "alert">{$alertMessage}</p>
	{/if}

	<div id = "content">
