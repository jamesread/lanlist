<h2>Organizer: {$organizer.title}</h2>
<img class = "banner bannerFit" alt = "Organizer logo" title = "Organizer logo for {$organizer.title}" src = "{$organizer.logoUrl}" /><br />

<div class = "paralellContainer">
	<strong>Website: </strong> {$organizer.websiteUrl|externUrl}<br />
	<div class = "organizer-steam-discord-row">
		<span class = "organizer-steam-cell">
			{if not empty($organizer.steamGroupUrl)}
			<div class = "organizer-steam-join">
				<a class = "organizer-platform-button organizer-platform-button--steam" href = "{$organizer.steamGroupHref}" target = "_blank" rel = "noopener noreferrer">
					<svg class = "organizer-platform-icon" width = "18" height = "18" viewBox = "0 0 27 27" aria-hidden = "true" focusable = "false"><path fill = "currentColor" d = "M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.911-.59.053 0 .104.003.157.004l2.384-3.845v-.003c0-2.052 1.738-3.714 3.865-3.714 2.129 0 3.849 1.662 3.849 3.714 0 2.051-1.72 3.714-3.849 3.714-.216 0-.424-.022-.624-.062L13.335 21.64c.162.484.253.999.253 1.537 0 2.514-2.028 4.544-4.544 4.544-2.156 0-3.971-1.515-4.428-3.533L.436 15.043C1.725 20.449 7.265 24 13.685 24 21.063 24 27 18.063 27 10.685 27 4.971 25.898 0 11.979 0z"/></svg>
					<span>Join the {$organizer.title|escape:'html'} Steam group</span>
				</a>
			</div>
			{else}
			<span class = "organizer-steam-none"><strong>Steam Group:</strong> None</span>
			{/if}
		</span>
		{if not empty($organizer.discordInviteUrl)}
		<span class = "organizer-discord-cell">
			<div class = "organizer-discord-join">
				<a class = "organizer-platform-button organizer-platform-button--discord" href = "{$organizer.discordInviteHref}" target = "_blank" rel = "noopener noreferrer">
					<svg class = "organizer-platform-icon" width = "18" height = "18" viewBox = "0 0 127.14 96.36" aria-hidden = "true" focusable = "false"><path fill = "currentColor" d = "M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.46,8.09C2.79,32.65-1.71,56.6.54,80.21a105.73,105.73,0,0,0,32.17,16.15,77.38,77.38,0,0,0,6.89-11.11,68.18,68.18,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1,105.25,105.25,0,0,0,32.19-16.14c2.64-27.38-4.51-51.11-18.9-72.11ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.73,73.25,60,73.25,53s5-12.74,11.44-12.74S96.23,46,96.12,53,91.08,65.75,84.69,65.75Z"/></svg>
					<span>Join the {$organizer.title|escape:'html'} Discord server</span>
				</a>
			</div>
		</span>
		{/if}
	</div>

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

