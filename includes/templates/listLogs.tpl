<table>
	<thead>
		<tr>
			<th>id</th>
			<th>event type</th>
			<th>timestamp</th>
			<th>content</th>
			<th>priority</th>
	</thead>

	<tbody>
	{foreach from = $listLogs item = "itemLog"}
	<tr class = "{$itemLog.class}">
		<td>{$itemLog.id}</td>
		<td>{$itemLog.eventType}</td>
		<td>{$itemLog.timestamp}</td>
		<td>{$itemLog.content}</td>
		<td>{$itemLog.priority}</td>
	</tr>
	{/foreach}

	</tbody>
</table>

		
