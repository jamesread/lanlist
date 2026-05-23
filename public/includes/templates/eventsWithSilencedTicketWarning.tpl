<div class="box">
	<h2>Silenced ticket warnings</h2>
	<p>Published upcoming events with no tickets yet, where a moderator has marked tickets as not yet released.</p>

	{if $listEventsWithSilencedTicketWarning|@count == 0}
	<p><em>No silenced ticket warnings.</em></p>
	{else}
	<table>
		<thead>
			<tr>
				<th>Event</th>
				<th>Organizer</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$listEventsWithSilencedTicketWarning item="silencedEvent"}
			<tr>
				<td><a href="viewEvent.php?id={$silencedEvent.id}">{$silencedEvent.title|escape:'html'}</a></td>
				<td><a href="viewOrganizer.php?id={$silencedEvent.organizerId}">{$silencedEvent.organizerTitle|escape:'html'}</a></td>
				<td>Ticket warning silenced for {$silencedEvent.ticketsNotReleasedDaysRemaining} more day{if $silencedEvent.ticketsNotReleasedDaysRemaining != 1}s{/if} (until {$silencedEvent.ticketsNotReleasedUntil|escape:'html'})</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
	{/if}
</div>
