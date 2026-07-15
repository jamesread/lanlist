<div>
	<h2>{if $eventsListMode == 'country' && $eventsListCountry != ''}LAN Parties in {$eventsListCountry|escape}{elseif $eventsListMode == 'country'}Upcoming LAN parties by country{else}LAN Parties in a list...{/if}</h2>

	{if $eventsListMode == 'country' && $eventsListCountry != ''}
	<p class="eventsList-countryIntro">
		There {if $eventsListCountryStats.organizerCount == 1}is{else}are{/if} {$eventsListCountryStats.organizerCount} organizer{if $eventsListCountryStats.organizerCount != 1}s{/if},
		{$eventsListCountryStats.pastEventCount} past event{if $eventsListCountryStats.pastEventCount != 1}s{/if}
		and {$eventsListCountryStats.upcomingEventCount} upcoming event{if $eventsListCountryStats.upcomingEventCount != 1}s{/if}
		in {if $eventsListCountryFlagHtml != ''}<span class="eventsList-countryFlag" aria-hidden="true">{$eventsListCountryFlagHtml nofilter}</span> {/if}{$eventsListCountry|escape}.
	</p>
	{if $eventsListCountryRelatedSites|@count > 0}
	<p class="eventsList-countryIntro">
		{$eventsListCountry|escape} has {$eventsListCountryRelatedSites|@count} event list{if $eventsListCountryRelatedSites|@count != 1}s{/if} on other sites, check out:
		{foreach from=$eventsListCountryRelatedSites item="relatedSite" name="relatedSites"}
		{if !$smarty.foreach.relatedSites.first}, {/if}<a href="{$relatedSite.url|escape:'html'}" target="_blank" rel="noopener noreferrer">{$relatedSite.title|escape:'html'}</a>
		{/foreach}.
	</p>
	{/if}
	{/if}

	{if $eventsListMode == 'country' && $eventsListCountry != '' && $listEvents|@count == 0}
	<div class="eventsList-countryEmptyCallout">
		<p class="eventsList-countryEmptyCallout-lead">
			{if $eventsListCountryFlagHtml != ''}<span class="eventsList-countryFlag" aria-hidden="true">{$eventsListCountryFlagHtml nofilter}</span> {/if}No upcoming LAN parties in {$eventsListCountry|escape} right now.
		</p>
		<p>Know of one coming up? It only takes a minute to help the community — add the event to lanlist so others can find it.</p>
		{if not $isLoggedIn}
		<p><a href="register.php">Create an account</a> or <a href="login.php">log in</a>, then use the add-event form on your profile.</p>
		{else}
		<p><a href="formHandler.php?formClazz=FormNewEvent">Add an event in {$eventsListCountry|escape}</a> — pick a venue in that country, or add a new venue if needed.</p>
		{/if}
		<p class="eventsList-countryEmptyCallout-footnote">Not sure how, or organising something yourself? <a href="contact.php">Get in touch</a> and we&rsquo;ll help.</p>
	</div>
	{else}
	<table class = "sortable">
		<thead>
			<tr>
				<th>Organizer</th>
				<th class = "collapseable">Venue</th>
				{if !($eventsListMode == 'country' && $eventsListCountry != '')}
				<th class = "collapseable">Country</th>
				{/if}
				<th>Event</th>
				<th>Start date</th>
				<th class = "collapseable">Number of Seats</th>
			</tr>
		</thead>

		<tbody>
		{foreach from = $listEvents item = "itemEvent"}
		<tr>
			<td><a href = "viewOrganizer.php?id={$itemEvent.organizerId}">{$itemEvent.organizerTitle}</a></td>
			<td class = "collapseable">{$itemEvent.venueTitle}</td>
			{if !($eventsListMode == 'country' && $eventsListCountry != '')}
			<td class = "collapseable"><a href = "eventsList.php?mode=country&amp;country={$itemEvent.country|escape:'url'}" title = "LAN Parties in {$itemEvent.country|escape}">{if $itemEvent.countryFlagHtml != ''}{$itemEvent.countryFlagHtml nofilter} {/if}{$itemEvent.country|escape}</a></td>
			{/if}
			<td><a href = "viewEvent.php?id={$itemEvent.id}">{$itemEvent.title}</a></td>
			<td>{$itemEvent.dateStartHuman}</td>
			<td class = "collapseable">{$itemEvent.numberOfSeats}</td>
		</tr>
		{/foreach}
		</tbody>
	</table>
	{/if}

	{if $eventsListMode == 'country' && $eventsListCountry != ''}
	<h3>Past LAN Parties in {$eventsListCountry|escape}</h3>
	{if $listPastEvents|@count == 0}
	<p>No past LAN parties found for {$eventsListCountry|lower|escape}.</p>
	{else}
	<table class = "sortable">
		<thead>
			<tr>
				<th>Organizer</th>
				<th class = "collapseable">Venue</th>
				<th>Event</th>
				<th>Start date</th>
				<th class = "collapseable">Number of Seats</th>
			</tr>
		</thead>
		<tbody>
		{foreach from = $listPastEvents item = "itemEvent"}
		<tr>
			<td><a href = "viewOrganizer.php?id={$itemEvent.organizerId}">{$itemEvent.organizerTitle}</a></td>
			<td class = "collapseable">{$itemEvent.venueTitle}</td>
			<td><a href = "viewEvent.php?id={$itemEvent.id}">{$itemEvent.title}</a></td>
			<td>{$itemEvent.dateStartHuman}</td>
			<td class = "collapseable">{$itemEvent.numberOfSeats}</td>
		</tr>
		{/foreach}
		</tbody>
	</table>
	{/if}
	{/if}
</div>
