<h2><a href = "account.php">Account</a> &raquo; Groups</h2>

<table>
	<thead>
		<tr>
			<th>ID</th>
			<th>Name</th>
		</tr>
	</thead>

	<tbody>
{foreach from = $listGroups item = itemGroups}
	<tr>
		<td><a href = "viewGroup.php?id={$itemGroups.id}">{$itemGroups.id}</a></td>
		<td><a href = "viewGroup.php?id={$itemGroups.id}">{$itemGroups.title}</a></td>
	</tr>
{/foreach}
	</tbody>
</table>
