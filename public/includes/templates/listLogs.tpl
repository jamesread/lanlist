<table>
	<thead>
		<tr>
			<th>priority</th>
			<th>event type</th>
			<th>user</th>
			<th>organizer</th>
			<th>timestamp</th>
			<th>content</th>
		</tr>
	</thead>

	<tbody>
	{foreach from = $listLogs item = "itemLog"}
	<tr>
		<td class = "{$itemLog.class|escape:'html'}"><strong>{$itemLog.priority|escape:'html'}</strong></td>
		<td>{$itemLog.eventType|escape:'html'}</td>
		<td>
			{if not empty($itemLog.relatedUser)}
				<a href = "viewUser.php?id={$itemLog.relatedUser}">{if $itemLog.relatedUsername}{$itemLog.relatedUsername|escape:'html'}{else}#{$itemLog.relatedUser}{/if}</a>
			{else}
				<span class = "subtle">&mdash;</span>
			{/if}
		</td>
		<td>
			{if not empty($itemLog.relatedOrganizer)}
				<a href = "viewOrganizer.php?id={$itemLog.relatedOrganizer}">{if $itemLog.relatedOrganizerTitle}{$itemLog.relatedOrganizerTitle|escape:'html'}{else}#{$itemLog.relatedOrganizer}{/if}</a>
			{else}
				<span class = "subtle">&mdash;</span>
			{/if}
		</td>
		<td>{$itemLog.timestamp|escape:'html'}</td>
		<td>{$itemLog.content|escape:'html'}</td>
	</tr>
	{/foreach}

	</tbody>
</table>


