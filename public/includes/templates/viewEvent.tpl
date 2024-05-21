<h2>Event: {$event.eventTitle}</h2>

{if $event.published eq 0}
<p class = "alert">This event is not published, an admin will approve it soon.</p>
{/if}

<div class = "paralellContainer">
	<h3>Basics</h3>

	<strong>Organizer: </strong> {if empty($event.organizerId)}???{else}<a href = "viewOrganizer.php?id={$event.organizerId}">{$event.organizerTitle|default:"???"}</a>{/if}<br />
	<strong>Event website: </strong> {$event.website|externUrlOr:"None"}	<br /><br />

	<strong>Starts: </strong> {$event.dateStartHuman} <br />
	<strong>Finishes: </strong> {$event.dateFinishHuman} <br /><br />

	<strong>Number of seats: </strong> {if $event.numberOfSeats < 1}Unknown{else}{$event.numberOfSeats}{/if} <br /><br />

	<strong>Ticket price on door: </strong> {if $event.priceOnDoor == 0}Not Applicable{else}{$event.priceOnDoor|floatToMoney:$event.currency}{/if} <br />
	<strong>Ticket price in advance: </strong> {$event.priceInAdv|floatToMoney:$event.currency} <br /><br />
	<strong>Age restrictions: </strong> {if $event.ageRestrictions == ''}Not known{else}{$event.ageRestrictions}{/if}<br /><br />
	<strong>Additional details: </strong> {$event.blurb|default:"Nothing"|stripslashes|htmlify} <br />
</div>

<div class = "paralellContainer">
	<h3>Venue</h3>
	<a href = "viewVenue.php?id={$event.venueId}">{$event.venueTitle|default:"???"}</a> <br />

	<br /><br />

	<h3>Facilities</h3>
	<strong>Sleeping: </strong> {$event.sleeping} <br />
	<strong>Showers?: </strong> {$event.showers|lookupField:'showers'} <br />
	<strong>Smoking area?: </strong> {$event.smoking|lookupField:'smoking'} <br />
	<strong>Alcohol allowed?: </strong> {$event.alcohol|lookupField:'alcohol'} <br />
	<strong>Network connection (mbps): </strong> {$event.networkMbps|default:"None"}<br />
	<strong>Internet connection (mbps): </strong> {$event.internetMbps|default:"None"} <br />
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
