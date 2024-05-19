<h2>Venue: {$venue.title}</h2>

<div class = "paralellContainer">
	<h3>Location</h3>
	<p><strong>lat</strong>: {$venue.lat}</p>
	<p><strong>lng</strong>: {$venue.lng}</p>
	<p><strong>Country</strong>: {$venue.country}</p>
</div>

<div class = "paralellContainer">
	<h3>Events at this venue...</h3>
{if $eventsAtVenue|@count eq 0}
	<p>There are no future events planned at this venue.</p>
{else}
	<ul>
	{foreach from = $eventsAtVenue item = "event"}
		<li><a href = "viewEvent.php?id={$event.id}">{$event.title}</a></li>
	{/foreach}
	</ul>
{/if}
</div>

<div id = "map" style = "width: 100%; height: 600px;border: 1px solid LightGray;" >
	<noscript>
		<p>There should be a map here, but you dont have javascript...</p>
	</noscript>
</div>

<script type = "text/javascript">
	renderMap();

	addMarker({$venue.lat}, {$venue.lng}, null, true);
</script>
