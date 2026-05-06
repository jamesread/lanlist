<!DOCTYPE html>

<html lang = "en">

<head>
	<title>{$title|default:"A List of LAN Parties"} &bull; {$siteTitle}</title>

	<meta name = "author" content = "UKLans Community" />
	<meta name = "description" content = "{$metaDescription|escape:'htmlall':'UTF-8'}" />
	<meta name = "keywords" content = "lan party, lan, list" />

	<meta name = "viewport" content = "width=device-width" />

	<link rel = "canonical" href = "{$canonicalUrl|escape:'htmlall':'UTF-8'}" />

	<link rel = "stylesheet" type = "text/css" href = "resources/stylesheets/main.css" />
	<link rel = "shortcut icon" type = "image/png" href = "resources/images/favicon.png" />
	<link rel = "alternate" type = "application/rss+xml" title = "{$siteTitle} - A list of LAN Parties" href = "api.php?function=events&amp;format=rss" />

	<meta property = "og:image" content = "{$socialImageUrl|escape:'htmlall':'UTF-8'}" />
	<meta property = "og:type" content = "{$ogType|escape:'htmlall':'UTF-8'}" />
	<meta property = "og:url" content = "{$canonicalUrl|escape:'htmlall':'UTF-8'}" />
	<meta property = "og:title" content = "{$title|default:"A list of LAN parties"} &bull; {$siteTitle}" />
	<meta property = "og:description" content = "{$metaDescription|escape:'htmlall':'UTF-8'}" />

	<meta name = "twitter:card" content = "summary_large_image" />
	<meta name = "twitter:title" content = "{$title|default:"A list of LAN parties"} &bull; {$siteTitle}" />
	<meta name = "twitter:description" content = "{$metaDescription|escape:'htmlall':'UTF-8'}" />
	<meta name = "twitter:image" content = "{$socialImageUrl|escape:'htmlall':'UTF-8'}" />

	{if isset($structuredDataJson) && $structuredDataJson !== ''}
	<script type="application/ld+json">{$structuredDataJson nofilter}</script>
	{/if}

	{if $includeGoogleMaps}
	<script type = "text/javascript" src = "resources/javascript/map.js"></script>

	<script type="text/javascript">
		const key = "{$mapsApiKey|escape:'javascript':'UTF-8'}";
		{literal}

  (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: key,
    v: "weekly",
  });

		{/literal}
	</script>
	{/if}
</head>

<body>
	<header>
		<h1><a href = "/">{$siteTitleDomain}<span class = "tld">{$siteTitleTld}</span></a></h1>
		<nav>
			<ul id = "mainNavigation">
				<li><a href = "eventsMap.php">Map</a></li>
				<li><a href = "eventsList.php">List</a></li>
			{if $isModerator}
				<li><a href = "moderation.php">Moderation</a></li>
			{/if}
			{if $isLoggedIn}
				<li><strong><a href = "account.php">{$username}</a></strong></li>
			{else}
				<li><a href = "login.php">Login</a></li>
				<li><a href = "register.php">Register</a></li>
			{/if}
			</ul>
		</nav>

	</header>

	{if !empty($alertMessage)}
	<div class = "alert">{$alertMessage}</div>
	{/if}


	<main>
	<div class = "{if $mainNopadding}nopadding{/if} infobox">
