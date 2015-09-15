<div>
	<h2>LAN Parties in a list...</h2>
	<p>This is a list of LAN Parties.</p>

	<table class = "sortable">
		<thead>
			<tr>
				<th>Title</th>
				<th>Venue</th>
				<th>Country</th>
				<th>Organizer</th>
				<th>Start date</th>
				<th>Number of Seats</th>
			</tr>
		</thead>

		<tbody>
		{foreach from = "$listEvents" item = "itemEvent"}
		<tr>
			<td><a href = "viewEvent.php?id={$itemEvent.id}">{$itemEvent.title}</a></td>
			<td>{$itemEvent.venueTitle}</td>
			<td>{$itemEvent.country}</td>
			<td><a href = "viewOrganizer.php?id={$itemEvent.organizerId}">{$itemEvent.organizerTitle}</a></td>
			<td>{$itemEvent.dateStart}</td>
			<td>{$itemEvent.numberOfSeats}</td>
		</tr>
		{/foreach}
		</tbody>
	</table>
</div>
