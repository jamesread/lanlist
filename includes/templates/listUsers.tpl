<h2>Users</h2>

<table class = "sortable">
	<thead>
		<tr>
			<th>Username</th>
			<th>Group</th>
			<th>Last Login</th>
			<th>Registered</th>
			<th>Organizer</th>
		</tr>
	</thead>
	<tbody>
		{foreach from = "$listUsers" item = "itemUser"}
		<tr>
			<td><a href = "viewUser.php?id={$itemUser.id}">{$itemUser.username}</a></td>
			<td>{$itemUser.groupTitle}</td>
			<td>{$itemUser.lastLogin}</td>
			<td>{$itemUser.registered}</td>
			<td><a href = "viewOrganizer.php?id={$itemUser.organization}">{$itemUser.organizationTitle}</a></td>
		</tr>
		{/foreach}
	</tbody>
</table>
