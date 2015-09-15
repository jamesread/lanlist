<h2>Organizers with 0 upcomming events</h2>
<p>Note: Played with this a little, it might still be broken... </p>

<table class = "sortable">
	<thead>
		<tr>
			<th>Organizer ID</th>
			<th>Organizer</th>
			<th>Website</th>
			<th><abbr title = "Number of associated users with an email address">Ass.</abbr> User #</th>
			<th>Last Checked, Reset link</td>
		</tr>
	</thead>
	{foreach from = "$listOrganizers" item = "itemOrganizer"}
		<tr>
			<td>{$itemOrganizer.id}</td>
			<td><a href = "viewOrganizer.php?id={$itemOrganizer.id}">{$itemOrganizer.title}</a></td>
			<td><a href = "{$itemOrganizer.websiteUrl}" target = "_new">{$itemOrganizer.websiteUrl}</a></td>
			<td>{$itemOrganizer.assUserCount}</td>
			<td>{$itemOrganizer.lastChecked|default:'Never'}, <a href = "misc.php?action=updateOrganizerLastChecked&amp;id={$itemOrganizer.id}">Update last checked</a></td>
		</tr>
	{/foreach}
	</tbody>
</table>
