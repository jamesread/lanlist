<h2>Event: {$event.eventTitle}</h2>

{if $event.published eq 0}
<p class = "alert">This event is not published, an admin will approve it soon.</p>
{/if}

<div class = "paralellContainer">
	<h3>Basics</h3>

	<strong>Organizer: </strong> {if empty($event.organizerId)}???{else}<a href = "viewOrganizer.php?id={$event.organizerId}">{$event.organizerTitle|default:"???"}</a>{/if}<br />
	<strong>Event website: </strong> {$event.website|externUrlOr:"None"}	<br /><br />

	<ul class = "property-list">
		<li>
			<img src = "resources/images/svg/calendar.svg" /><strong>Dates: </strong> {$event.dateStartHuman} - {$event.dateFinishHuman}
		</li>
		<li>
			<img src = "resources/images/svg/people.svg" /><strong>Number of seats: </strong> {if $event.numberOfSeats < 1}Unknown{else}{$event.numberOfSeats}{/if}
		</li>
		<li>
			<img src = "resources/images/svg/age.svg" /><strong>Age restrictions: </strong> {if $event.ageRestrictions == ''}Not known{else}{$event.ageRestrictions}{/if}
		</li>
	</ul>

	<h3>Tickets</h3>

	{if empty($event.tickets)}
	<strong>On door: </strong> {if $event.priceOnDoor == 0}Not Applicable{else}{$event.priceOnDoor|floatToMoney:$event.currency}{/if} <br />
	<strong>In advance: </strong> {$event.priceInAdv|floatToMoney:$event.currency} <br /><br />
	{else}
	    {if count($event.tickets) eq 1 && $event.tickets[0].cost == 0}
		<p>Tickets are free for this event!</p>
		{else}
			<ul>
			{foreach from = $event.tickets item = ticket}
				<li><strong>{$ticket.title}:</strong> {$ticket.cost} {$ticket.currency}
				{if $canEditEvent}<a href = "formHandler.php?formClazz=FormEditTicket&editTicket-id={$ticket['id']}">Edit Ticket</a>{/if}
				</li>
			{/foreach}
			</ul>
		{/if}
	{/if}
	
	{if $canEditEvent}
	<a href = "formHandler.php?formClazz=FormAddTicket&addticket-eventId={$event['id']}">Add ticket</a>
	{/if}


	<h3>Additional details</h3>
	{$event.blurb|default:"Nothing"|stripslashes|htmlify} 
</div>

<div class = "paralellContainer">
	<h3>Venue</h3>
	<a href = "viewVenue.php?id={$event.venueId}">{$event.venueTitle|default:"???"}</a> <br />

	<br />

	<h3>Facilities</h3>
	<ul class = "property-list">
		<li>
			<img src = "resources/images/svg/sleep.svg" /><strong>Sleeping: </strong> {$event.sleeping|lookupField:'sleeping'}
		</li>
		<li>
			<img src = "resources/images/svg/shower.svg" /><strong>Showers?: </strong> {$event.showers|lookupField:'showers'}
		</li>
		<li>
			<img src = "resources/images/svg/smoking.svg" /><strong>Smoking area?: </strong> {$event.smoking|lookupField:'smoking'} 
		</li>
		<li>
			<img src = "resources/images/svg/alcohol.svg" /><strong>Alcohol allowed?: </strong> {$event.alcohol|lookupField:'alcohol'} 
		</li>
		<li>
			<img src = "resources/images/svg/network.svg" /><strong>Network connection: </strong> {$event.networkMbps|default:"None"}mbps
		</li>

		<li>
			<img src = "resources/images/svg/network.svg" /><strong>Internet connection: </strong> {$event.internetMbps|default:"None"}mbps
		</li>
	</ul>
	</div>

<hr />

{if $event.venueId eq ""} 
	<p><span class = "karmaBad">We dont know where this event is!</span> If we did, there would be a nice map here now... sorry about that.</p>
{else}
	<div id = "map" style = "width: 100%; height: 600px;border: 1px solid LightGray;" >
		<noscript>There should be a map here, but you dont have javascript...</noscript>
	</div>

	<script type = "text/javascript">
		renderMap();

		{foreach from = $markers item = "marker"} 
			{$marker}
		{/foreach}

	</script>
{/if}
