{if $excludedLogEventTypeFilters|@count gt 0}
<p class = "log-active-filters">
	<strong>Hiding:</strong>
	{foreach from = $excludedLogEventTypeFilters item = "itemFilter" name = "logFilters"}
	<span class = "log-filter-tag">{$itemFilter.name|escape:'html'} <a href = "{$itemFilter.removeUrl|escape:'html'}" title = "Stop hiding this event type" aria-label = "Stop hiding {$itemFilter.name|escape:'html'}">&times;</a></span>{if not $smarty.foreach.logFilters.last} {/if}
	{/foreach}
	<a class = "log-filter-clear" href = "{$logListUrlClearFilters|escape:'html'}">Clear all</a>
</p>
{else}
<p class = "subtle log-filter-hint">Right-click an event type to hide it from this view.</p>
{/if}

<script type = "application/json" id = "log-list-config">{$logListConfigJson nofilter}</script>

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
		<td>
			{if not empty($itemLog.eventType)}
			<span class = "log-event-type" data-event-type = "{$itemLog.eventType|escape:'html'}" title = "Right-click to hide this event type">{$itemLog.eventType|escape:'html'}</span>
			{else}
			<span class = "subtle">&mdash;</span>
			{/if}
		</td>
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

