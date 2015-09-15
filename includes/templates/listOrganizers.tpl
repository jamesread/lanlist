<h2>Organizers list</h2>

<table class = "sortable">
	<thead>
		<tr>
			<th>Title</th>
			<th>Website</th>
			<th>Number of events</th>
		</tr>
	</thead>

	<tbody>
	{foreach from = "$listOrganizers" item = "itemOrganizer"}
		<tr>
			<td><a href = "viewOrganizer.php?id={$itemOrganizer.id}">{$itemOrganizer.title}</a></td>
			<td><a href = "{$itemOrganizer.websiteUrl}" target = "_new">{$itemOrganizer.websiteUrl}</a></td>
			<td>{$itemOrganizer.eventCount}</td>
		</tr>
	{/foreach}
	</tbody>
</table>
