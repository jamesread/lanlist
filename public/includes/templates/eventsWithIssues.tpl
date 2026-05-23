<div class="box">
	<h2>Events with issues</h2>
	<p>Even if there are lots of issues with the event, only one is shown. Checks for (in order): event not published, blank event website, missing organizer, organizer not published, missing ticket prices, duration (finish − start) is 0. For &ldquo;No tickets defined for event&rdquo;, moderators can mark tickets as not yet released; silenced events appear in the section below.</p>

	{if $listEventsWithIssues|@count == 0}
	<p><em>No events with issues.</em></p>
	{else}
	<table>
		<thead>
			<tr>
				<th>Event</th>
				<th>Organizer</th>
				<th>Website</th>
				<th>Issue Description</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$listEventsWithIssues item="issueEvent"}
			<tr>
				<td><a href="viewEvent.php?id={$issueEvent.id}">{$issueEvent.title|escape:'html'}</a></td>
				<td><a href="viewOrganizer.php?id={$issueEvent.organizerId}">{$issueEvent.organizerTitle|escape:'html'}</a></td>
				<td><a href="{$issueEvent.website|escape:'html'}" target="_blank" rel="noopener noreferrer">{$issueEvent.website|escape:'html'}</a></td>
				<td>
					{$issueEvent.issueDescription|escape:'html'}
					{if $issueEvent.issueDescription == 'No tickets defined for event'}
						&mdash; <a href="misc.php?action=markTicketsNotReleased&amp;id={$issueEvent.id}">Mark tickets not yet released</a>
					{/if}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
	{/if}
</div>
