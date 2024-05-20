<table>
	<thead>
		<tr>
			<th>priority</th>
			<th>event type</th>
			<th>timestamp</th>
			<th>content</th>
	</thead>

	<tbody>
	{foreach from = $listLogs item = "itemLog"}
	<tr>
		<td class = "{$itemLog.class}"><strong>{$itemLog.priority}</strong></td>
		<td>{$itemLog.eventType}</td>
		<td>{$itemLog.timestamp}</td>
		<td>{$itemLog.content}</td>
	</tr>
	{/foreach}

	</tbody>
</table>

		
