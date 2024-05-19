<h2><a href = "account.php">Account</a> &raquo; <a href = "listGroups.php">Groups</a> &raquo; View group: {$itemGroup.title}</h2>

ID: {$itemGroup.id}

<table>
	<thead>
		<tr>
			<th>ID</th>
			<th>Source</th>
			<th>Username</th>
		</tr>
	</thead>

	<tbody>

		{foreach from = $listMembers item = "itemMember"}
		<tr>
			<td>{$itemMember.id}</td>
			<td>{$itemMember.source}</td>
			<td><a href = "viewUser.php?id={$itemMember.id}">{$itemMember.username}</a></td>
		</tr>
		{/foreach}
	</tbody>
</table>

<h3>Permissions<h3>
<table>
	<thead>
		<tr>
			<th>Key</th>
			<th>Description</th>
		</tr>
	</thead>

	<tbody>

		{foreach from = $listPrivileges item = "itemPermission"}
		<tr>
			<td>{$itemPermission.key}</td>
			<td>{$itemPermission.description}</td>
		</tr>
		{/foreach}
	</tbody>
</table>
