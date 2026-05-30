<h2>Sitemap</h2>

<p>Whether you are a human or a robot, here is a sitemap for you to read.</p>

<p>Machine-readable index: <a href = "sitemap.xml.php">XML sitemap</a> (also listed in <code>robots.txt</code>).</p>

<h3>Lists</h3>
<dl>
	<dt><a href = "eventsList.php">Events</a></dt>
	<dd>The main part of the site, a list of LAN Parties</dd>

	<dt><a href = "eventsList.php?mode=country">Events by country</a></dt>
	<dd>Upcoming LAN parties filtered by country.</dd>
	{if $eventsListCountries|@count > 0}
	<dd>
		<ul>
		{foreach from = $eventsListCountries item = "country"}
			<li><a href = "eventsList.php?mode=country&amp;country={$country|escape:'url'}">{$country|escape}</a></li>
		{/foreach}
		</ul>
	</dd>
	{/if}

	<dt><a href = "listOrganizers.php">Organizers</a></dt>
	<dd>A list of organizers of LAN Parties.</dd>

	<dt><a href = "listVenues.php">Venues</a></dt>
	<dd>Venues are physical places where organizers hold their events.</dd>
</dl>

<h3>Users &amp; Accounts</h3>

<dl>
	<dt><a href = "register.php">Register</a></dt>
	<dd>Register a new user account.</dd>

	<dt><a href = "login.php">Login</a></dt>
	<dd>Login with an existing user account.</dd>

	<dt><a href = "account.php">Account (Control Panel)</a></dt>
	<dd>Manage your organization, create events, etc</dd>

	<dt><a href = "lpps.php">Lan Party Publishing Standard (LPPS)</a></dt>
	<dd>Optional JSON feed for organizers who want automated sync; events can always be added manually.</dd>
</dl>
