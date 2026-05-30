<h2>Venues</h2>
<p>These venues are halls, rooms and places that host LAN Parties. You can view venues by country below, or <a href = "eventsList.php?mode=country">browse upcoming LAN parties by country</a>.</p>

<table class = "sortable">
	<thead>
		<tr>
			<th>Venue name</th>
			<th># of upcoming events</th>
			<th>Country</th>
		</tr>
	</thead>

	<tbody>
	{foreach from = $listVenues item = "itemVenue"}
		<tr>
			<td><a href = "viewVenue.php?id={$itemVenue.id}">{$itemVenue.title}</a></td>
			<td>{$itemVenue.upcommingEvents}</td>
			<td><a href = "eventsList.php?mode=country&amp;country={$itemVenue.country|escape:'url'}" title = "LAN Parties in {$itemVenue.country|escape}">{if $itemVenue.countryFlagHtml != ''}{$itemVenue.countryFlagHtml nofilter} {/if}{$itemVenue.country|escape}</a></td>
		</tr>
	{/foreach}
	</tbody>
</table>
