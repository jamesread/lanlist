<style type = "text/css">
dt {
	font-weight: 600;
}

dl {
	display: grid;
	grid-template-columns: max-content auto;
	gap: 1em;
}

.bad {
	background-color: salmon;
}

.warn {
	background-color: #fff8dc;
	border-left: 3px solid goldenrod;
	padding: 0.35em 0.5em;
}

li {
	margin-bottom: 1em;
}
</style>
<h2><a href = "viewOrganizer.php?id={$organizer.id}">{$organizer.title}</a></h2>

{if $organizer.assumedStale}
<p class = "bad">This organizer has been marked as stale. Please review its information.</p>
{/if}

<dl>
	<dt>ID</dt>
	<dd>{$organizer.id}</dd>

	<dt>Website URL</dt>
	<dd><a target = "_blank" href="{$organizer.websiteUrl}">{$organizer.websiteUrl}</a></dd>

	<dt>Last Checked</dt>
	<dd>
		{if $organizer.lastChecked}
			{$organizer.lastChecked}
		{else}
			Never
		{/if}
	</dd>

	<dt>Steam group</dt>
	<dd class = "{$organizer.steamGroupRowClass}">
	{if not empty($organizer.steamGroupUrl)}
		<a target = "_blank" rel = "noopener noreferrer" href = "{$organizer.steamGroupHref}">{$organizer.steamGroupUrl|escape:'html'}</a>
		{if $organizer.steamGroupRowClass eq 'warn'}
			<em>&nbsp;(Does not match typical Steam URLs — verify manually.)</em>
		{/if}
	{else}
		<em>Not set</em>
	{/if}
	</dd>

	<dt>Discord invite</dt>
	<dd class = "{$organizer.discordInviteRowClass}" data-inline-row-class-target>
	{include file='organizerInlineDiscordInvite.tpl' organizer=$organizer}
	</dd>

	<dt>Banner logo</dt>
	<dd class = "{$organizer.logoRowClass}">
	{if $organizer.logoFileExists}
		<img src = "resources/images/organizer-logos/{$organizer.id}.jpg" alt = "Organizer banner" style = "max-height: 100px; max-width: 240px; border: 1px solid #ccc;" />
	{else}
		<em>Missing file (expected resources/images/organizer-logos/{$organizer.id}.jpg)</em>
	{/if}
	</dd>

	<dt>Favicon</dt>
	<dd class = "{$organizer.faviconRowClass}">
	<p><strong>Collect favicon from website:</strong> {if $organizer.useFaviconEnabled}yes{else}no{/if}</p>
	{if $organizer.faviconFileExists}
		<img src = "resources/images/organizer-favicons/{$organizer.id}.png" width = "32" height = "32" alt = "Collected favicon" />
	{else}
		<em>No favicon PNG (resources/images/organizer-favicons/{$organizer.id}.png)</em>
	{/if}
	{if $organizer.useFaviconEnabled && !$organizer.faviconFileExists}
		<p><em>Favicon collection is enabled but file is missing (job may not have run yet, or fetch failed).</em></p>
	{/if}

	{if $latestFaviconAsyncJob}
		<p style="margin-top:1em;"><strong>Latest OliveTin favicon job:</strong></p>
		<dl style="margin-top:0;">
			<dt>Job ID</dt>
			<dd>{$latestFaviconAsyncJob.id}</dd>
			<dt>Status</dt>
			<dd>{$latestFaviconAsyncJob.status}</dd>
			<dt>OliveTin tracking</dt>
			<dd>{if !empty($latestFaviconAsyncJob.execution_tracking_id)}{$latestFaviconAsyncJob.execution_tracking_id|escape:'html'}{else}<em>Awaiting dispatch or not yet confirmed</em>{/if}</dd>
			<dt>Queued</dt>
			<dd>{$latestFaviconAsyncJob.created_at}</dd>
			<dt>Accepted by OliveTin</dt>
			<dd>{if !empty($latestFaviconAsyncJob.started_at)}{$latestFaviconAsyncJob.started_at}{else}—{/if}</dd>
			<dt>Finished</dt>
			<dd>{if !empty($latestFaviconAsyncJob.finished_at)}{$latestFaviconAsyncJob.finished_at}{else}—{/if}</dd>
			{if !empty($latestFaviconAsyncJob.error_message)}
			<dt>Note</dt>
			<dd class="warn">{$latestFaviconAsyncJob.error_message|escape:'html'}</dd>
			{/if}
			{if !empty($latestFaviconAsyncJob.metadataDecoded.enqueuedByUserId)}
			<dt>Queued by user id</dt>
			<dd>{$latestFaviconAsyncJob.metadataDecoded.enqueuedByUserId}</dd>
			{/if}
		</dl>
	{/if}

	{if $organizer.useFaviconEnabled && !$hasActiveFaviconAsyncJob && !empty($organizer.websiteUrl)}
	<form method="post" action="misc.php" style="margin-top:.75em;">
		<input type="hidden" name="action" value="enqueueOrganizerFaviconFetch" />
		<input type="hidden" name="organizerId" value="{$organizer.id}" />
		<button type="submit">Queue favicon fetch (async via OliveTin)</button>
	</form>
	{elseif $organizer.useFaviconEnabled && $hasActiveFaviconAsyncJob}
		<p style="margin-top:.75em;"><em>A favicon queue job is already pending or processing for this organizer.</em></p>
	{/if}
	</dd>
</dl>


<h3>Upcoming events</h3>

{if $organizer.futureEvents|@count == 0}
	<p class = "bad">No future events found.</p>

	<a href = "formHandler.php?formClazz=FormEditOrganizer&amp;formEditOrganizer-id={$organizer.id}">Edit organizer</a>
	&nbsp;|&nbsp;
	<a href = "formHandler.php?formClazz=FormNewEvent&formNewEvent-organizer={$organizer.id}">Create new event</a>
{else}
	<table>
	<thead>
		<tr>
			<th>Date</th>
			<th>Title</th>
			<th>Venue</th>
			<th>Created By</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
	{foreach from = $organizer.futureEvents item = event}
		<tr>
		<td>{$event.dateStart}</td>
		<td><a href = "viewEvent.php?id={$event.id}">{$event.title}</a></td>
		<td>
		{if !empty($event.venueId)}
			<a href = "viewVenue.php?id={$event.venueId}">{$event.venueTitle}</a>
		{else}
			<span class = "subtle">No venue</span>
		{/if}
		</td>
		<td>created by <a href = "viewUser.php?id={$event.uid}">{$event.username}</a></td>
		<td>
		<a href="misc.php?action=cloneEvent&id={$event.id}">Clone</a>
		|
		<a href = "formHandler.php?formClazz=FormEditEvent&amp;formEditEvent-id={$event.id}">Edit</a>
		</td>
		</tr>
	{/foreach}
	</tbody>
	</table>
{/if}

<h3>Past events</h3>

{if $organizer.pastEvents|@count == 0}
	<p class = "subtle">No past events.</p>
{else}
	<table>
	<thead>
		<tr>
			<th>Date</th>
			<th>Title</th>
			<th>Venue</th>
			<th>Created By</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
	{foreach from = $organizer.pastEvents item = event}
		<tr class = "subtle">
		<td>{$event.dateStart}</td>
		<td><a href = "viewEvent.php?id={$event.id}">{$event.title}</a></td>
		<td>
		{if !empty($event.venueId)}
			<a href = "viewVenue.php?id={$event.venueId}">{$event.venueTitle}</a>
		{else}
			<span class = "subtle">No venue</span>
		{/if}
		</td>
		<td>created by <a href = "viewUser.php?id={$event.uid}">{$event.username}</a></td>
		<td>
		<a href="misc.php?action=cloneEvent&id={$event.id}">Clone</a>
		|
		<a href = "formHandler.php?formClazz=FormEditEvent&amp;formEditEvent-id={$event.id}">Edit</a>
		</td>
		</tr>
	{/foreach}
	</tbody>
	</table>
{/if}
