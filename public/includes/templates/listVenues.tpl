<h2>Venues</h2>
<p>These venues are halls, rooms and places that host LAN Parties. You can view venues by country, or find an organizer that takes your fancy.</p>

<table class = "sortable">
	<thead>
		<tr>
			<th>Venue name</th>
			<th># of upcomming events</th>
			<th>Country</th>
		</tr>
	</thead>

	<tbody>
	{foreach from = $listVenues item = "itemVenue"}
		<tr>
			<td><a href = "viewVenue.php?id={$itemVenue.id}">{$itemVenue.title}</a></td>
			<td>{$itemVenue.upcommingEvents}</td>
			<td><a href = "listVenues.php?country={$itemVenue.country}">{$itemVenue.country}</a></td>
		</tr>
	{/foreach}
	</tbody>
</table>
