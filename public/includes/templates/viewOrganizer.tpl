<h2>Organizer: {$organizer.title}</h2>
<img class = "banner bannerFit" alt = "Organizer logo" title = "Organizer logo for {$organizer.title}" src = "{$organizer.logoUrl}" /><br />

<div class = "paralellContainer">
	<strong>Website: </strong> {$organizer.websiteUrl|externUrl}<br />
	<br />
	{include file = 'organizerSteamDiscordRow.tpl' orgTitle = $organizer.title steamGroupUrl = $organizer.steamGroupUrl steamGroupHref = $organizer.steamGroupHref discordInviteUrl = $organizer.discordInviteUrl discordInviteHref = $organizer.discordInviteHref showOrganizerSteamNone = 1}

	{if not empty($organizer.assumedStale)}
	<p class = "alert">This organizer seems stale, or has gone out of business or something. We've been ignoring them since <strong>{$organizer.assumedStale}</strong></p>
	{/if}
</div>

<div class = "paralellContainer">

	{if empty($organizer.blurb)}	
	<p>Not much is known about this organizer. If you are the organizer of this event, <a href = "register.php">register a user account</a> and request to <a href = "formHandler.php?formClazz=FormJoinOrganizer">join an existing organizer</a> to edit this page.</p>
	{else}
	<p>{$organizer.blurb|stripslashes|htmlify}</p>
	{/if}
</div>

<div style = "clear:both;">&nbsp;</div>

{if $isModerator}
<div class = "paralellContainer organizer-moderator-fields">
	{include file='organizerModeratorFields.tpl'}
</div>
{/if}

<div class = "paralellContainer">
	<h3>Events ({$events|@count})</h3>

{if $events|@count == 0} 
	<p>We don't know of any events from this organizer. </p>
	<p>Events can be added from the <a href = "account.php">account</a> page.</p>
{else}
	<ul>
	{foreach from = $events item = event}
		<li><a href = "viewEvent.php?id={$event.id}">{$event.title}</a>, {$event.dateStartHuman} {if not $event.published} - <span class = "alert">not published by admin!</span>{/if}</li>
	{/foreach}
	</ul>
{/if}
</div>

{if isset($associatedUsers)}
<div class = "paralellContainer">
	<h3>Associated users</h3>

	<p>There are {$associatedUsers|@count} associated user(s).</p>

	<ul>
		{foreach from = $associatedUsers item = user} 
			{if $userlist}
		<li><a href = "viewUser.php?id={$user.id}">{$user.username}</a>, Last login: {$user.lastLogin}</li>
			{else}
		<li>{$user.username}</li>
			{/if}
		{/foreach}
	</ul>

	{if !empty($organizer.genericEmail)}
		<a href = "formHandler.php?formClazz=FormSendEmailToUser&amp;formSendEmailToUser-email={$organizer.genericEmail|escape:'url'}">{$organizer.genericEmail|escape:'html'}</a>
	{/if}
</div>
{/if}

