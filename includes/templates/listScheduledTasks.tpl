<h2>Scheduled Tasks</h2>

<table>
	<thead>
		<tr>
			<th>Class</th>
			<th>Frequency</th>
			<th>Last Run Date</th>
		</tr>
	</thead>

	<tbody>
	{foreach from = "$listScheduledTasks" item = "itemScheduledTask"}
		<tr>
			<td>{$itemScheduledTask.className}</td>
			<td>{$itemScheduledTask.frequency}</td>
			<td>{$itemScheduledTask.lastRunTime}</td>
		</tr>
	{/foreach}
	</tbody>
</table>
