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
</dl>


<h3>Events</h3>

{if $organizer.events|@count == 0}
	<p class = "bad">No future events found.</p>
	
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
		<a href="eventEdit.php?id={$event.id}">Edit</a>
		</td>
		</tr>
	{/foreach}
	</tbody>
	</table>
{/if}
