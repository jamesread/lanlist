<div class="box">
	<h2>Organizers with no upcoming events</h2>
	<p>Published organizers with no upcoming events, where the last check was more than 60 days ago (or never).</p>

	{if $listOrganizers|@count == 0}
	<p><em>No organizers with no upcoming events.</em></p>
	{else}
	<table class="sortable">
		<thead>
			<tr>
				<th>Organizer ID</th>
				<th>Organizer</th>
				<th>Website</th>
				<th><abbr title="Number of associated users with an email address">Ass.</abbr> User #</th>
				<th>Last checked, reset link</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$listOrganizers item="itemOrganizer"}
			<tr{if not empty($itemOrganizer.assumedStale)} class="stale"{/if}>
				<td>{$itemOrganizer.id}</td>
				<td><a href="viewOrganizer.php?id={$itemOrganizer.id}">{$itemOrganizer.title|escape:'html'}</a></td>
				<td>{if $itemOrganizer.websiteUrl}<a href="{$itemOrganizer.websiteUrl|escape:'html'}" target="_blank" rel="noopener noreferrer">{$itemOrganizer.websiteUrl|escape:'html'}</a>{else}<span class="subtle">&mdash;</span>{/if}</td>
				<td>{$itemOrganizer.assUserCount}</td>
				<td>{$itemOrganizer.lastChecked|default:'Never'|escape:'html'}, <a href="misc.php?action=updateOrganizerLastChecked&amp;id={$itemOrganizer.id}">Update last checked</a></td>
			</tr>
		{/foreach}
		</tbody>
	</table>
	{/if}
</div>
