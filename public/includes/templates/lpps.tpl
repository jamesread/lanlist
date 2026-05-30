<h2>Lan Party Publishing Standard (LPPS)</h2>

<p>
	<strong>LPPS is entirely optional.</strong> Most organizers add and update events on lanlist by hand from their
	<a href="account.php">account</a> page — that works perfectly and always will.
</p>

<p>
	<a href="{$lppsStandardUrl|escape:'html'}" target="_blank" rel="noopener noreferrer">LPPS</a>
	is an open JSON format for describing your organization, venues, and events on <em>your own</em> website.
	If you already maintain a calendar or API for your LAN series, LPPS gives lanlist a stable way to read it instead
	of retyping the same details.
</p>

<h3>Why use it?</h3>
<ul>
	<li><strong>One source of truth</strong> — dates, venues, and ticket links stay on your site; lanlist can mirror them on a schedule.</li>
	<li><strong>Less duplicate data entry</strong> — handy when you run many events or update often between parties.</li>
	<li><strong>Community standard</strong> — the spec is public; other tools can consume the same feed.</li>
</ul>

<h3>How lanlist uses LPPS</h3>
<ol>
	<li>You host a version <strong>2</strong> JSON document that follows the
		<a href="{$lppsStandardUrl|escape:'html'}" target="_blank" rel="noopener noreferrer">published schema</a>.</li>
	<li>In <a href="formHandler.php?formClazz=FormEditOrganizer">edit organizer</a>, paste the feed URL into <strong>LPPS feed URL</strong>
		(if your account is linked to that organizer).</li>
	<li>When the site’s LPPS crawl job runs, lanlist imports matching venues and events (matched by stable IDs in the feed).</li>
</ol>
<p>
	You can turn the feed off anytime by clearing the URL. Site moderators can also disable crawls for an organizer
	while keeping manual edits.
</p>

<h3>Manual listing is fine</h3>
<p>
	Never used a JSON API? No problem. Use
	<a href="formHandler.php?formClazz=FormNewEvent">add event</a> and
	<a href="formHandler.php?formClazz=FormNewVenue">add venue</a> like everyone else.
	LPPS is a convenience for teams that already publish structured data — not a requirement to be listed on lanlist.
</p>

<h3>Related</h3>
<ul>
	<li><a href="{$lppsStandardUrl|escape:'html'}" target="_blank" rel="noopener noreferrer">LPPS specification on GitHub</a></li>
	<li><a href="exportLanpartydb.php">Export to OrgaTalk LAN Party Database</a> — archive past public LANs as TOML for a community PR (separate project)</li>
	<li><a href="account.php">Account control panel</a></li>
</ul>
