<div class="box">
	<h2>Unpublished organizers</h2>
	<p>These organizers are not published yet and need admin review.</p>

	{if $listUnpublishedOrganizers|@count == 0}
	<p><em>No unpublished organizers.</em></p>
	{else}
	<table class="sortable">
		<thead>
			<tr>
				<th>Organizer ID</th>
				<th>Organizer</th>
				<th>Website</th>
				<th><abbr title="Number of associated users with an email address">Ass.</abbr> User #</th>
				<th>Last checked</th>
				{if $canPublishOrganizer}<th>Publish</th>{/if}
			</tr>
		</thead>
		<tbody>
		{foreach from=$listUnpublishedOrganizers item="itemOrganizer"}
			<tr{if not empty($itemOrganizer.assumedStale)} class="stale"{/if}>
				<td>{$itemOrganizer.id}</td>
				<td><a href="viewOrganizer.php?id={$itemOrganizer.id}">{$itemOrganizer.title|escape:'html'}</a></td>
				<td>{if $itemOrganizer.websiteUrl}<a href="{$itemOrganizer.websiteUrl|escape:'html'}" target="_blank" rel="noopener noreferrer">{$itemOrganizer.websiteUrl|escape:'html'}</a>{else}<span class="subtle">&mdash;</span>{/if}</td>
				<td>{$itemOrganizer.assUserCount}</td>
				<td>{$itemOrganizer.lastChecked|default:'Never'|escape:'html'}</td>
				{if $canPublishOrganizer}
				<td>
					{include file='organizerInlinePublished.tpl' organizer=$itemOrganizer canPublishOrganizer=$canPublishOrganizer compact=1}
				</td>
				{/if}
			</tr>
		{/foreach}
		</tbody>
	</table>
	{/if}
</div>
