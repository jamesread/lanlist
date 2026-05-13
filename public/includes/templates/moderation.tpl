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
	<dd class = "{$organizer.discordInviteRowClass}">
	{if not empty($organizer.discordInviteUrl)}
		<a target = "_blank" rel = "noopener noreferrer" href = "{$organizer.discordInviteHref}">{$organizer.discordInviteUrl|escape:'html'}</a>
		{if $organizer.discordInviteRowClass eq 'warn'}
			<em>&nbsp;(Does not match typical Discord URLs — verify manually.)</em>
		{/if}
	{else}
		<em>Not set</em>
	{/if}
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
	</dd>
</dl>


<h3>Events</h3>

{if $organizer.events|@count == 0}
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

	{foreach from = $organizer.events item = event}
		<tr class = "{if $event.inPast}subtle{else}{/if}">

		<td>
		{$event.dateStart}
		</td>
		<td>
		<a href = "viewEvent.php?id={$event.id}">{$event.title}</a>
		</td>
		<td>
		{if !empty($event.venueId)}
			<a href = "viewVenue.php?id={$event.venueId}">{$event.venueTitle}</a>
		{else}
			<span class = "subtle">No venue</span>
		{/if}
		</td>
		<td>
		created by <a href = "viewUser.php?id={$event.uid}">{$event.username}</a>

		</td>
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
