<html>

<body>

<p>Hey {$username|escape:'html'},</p>

<p>Welcome to <a href="{$siteBaseUrl}/">{$siteTitle|escape:'html'}</a> — thanks for registering.</p>

<p>With your account you can:</p>

<ul>
	<li>Browse upcoming LAN parties and events on the <a href="{$siteBaseUrl}/eventsMap.php">map</a></li>
	<li>Submit your own events from your <a href="{$siteBaseUrl}/account.php">account page</a></li>
	<li>Get directions and keep track of parties near you</li>
</ul>

<p>If you run or help run an organizer that is already listed, you can <a href="{$siteBaseUrl}/formHandler.php?formClazz=FormJoinOrganizer">request to join that organizer</a> after you log in.</p>

<p><a href="{$loginUrl|escape:'html'}">Log in now</a> to get started.</p>

</body>

</html>
