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
		<td class = "{$itemLog.class|escape:'html'}"><strong>{$itemLog.priority|escape:'html'}</strong></td>
		<td>{$itemLog.eventType|escape:'html'}</td>
		<td>{$itemLog.timestamp|escape:'html'}</td>
		<td>{$itemLog.content|escape:'html'}</td>
	</tr>
	{/foreach}

	</tbody>
</table>

		
