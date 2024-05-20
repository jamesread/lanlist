<div>
	<h2>LAN Parties in a list...</h2>

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
			<td class = "collapseable">{$itemEvent.country}</td>
			<td><a href = "viewEvent.php?id={$itemEvent.id}">{$itemEvent.title}</a></td>
			<td>{$itemEvent.dateStartHuman}</td>
			<td class = "collapseable">{$itemEvent.numberOfSeats}</td>
		</tr>
		{/foreach}
		</tbody>
	</table>
</div>
