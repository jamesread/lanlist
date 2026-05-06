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
	<ul>
	{foreach from = $organizer.events item = event}
		<li class = "{if $event.inPast}subtle{else}{/if}">
		{$event.dateStart}: 
		<a href = "viewEvent.php?id={$event.id}">{$event.title}</a>
		created by <a href = "viewUser.php?id={$event.uid}">{$event.username}</a>
		|
		<a href="misc.php?action=cloneEvent&id={$event.id}">Clone</a>
		|
		<a href="eventEdit.php?id={$event.id}">Edit</a>

		</li>
	{/foreach}
	</ul>
{/if}
