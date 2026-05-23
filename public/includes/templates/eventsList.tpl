<div>
	<h2>{if $eventsListMode == 'country' && $eventsListCountry != ''}Upcoming LAN parties in {$eventsListCountry|escape}{elseif $eventsListMode == 'country'}Upcoming LAN parties by country{else}LAN Parties in a list...{/if}</h2>

	<table class = "sortable">
		<thead>
			<tr>
				<th>Organizer</th>
				<th class = "collapseable">Venue</th>
				<th class = "collapseable">Country</th>
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
			<td class = "collapseable">{if $itemEvent.countryFlagHtml != ''}{$itemEvent.countryFlagHtml nofilter} {/if}{$itemEvent.country|escape}</td>
			<td><a href = "viewEvent.php?id={$itemEvent.id}">{$itemEvent.title}</a></td>
			<td>{$itemEvent.dateStartHuman}</td>
			<td class = "collapseable">{$itemEvent.numberOfSeats}</td>
		</tr>
		{/foreach}
		</tbody>
	</table>
</div>
