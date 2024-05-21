<h2>Account</h2>
{if empty($usernameDiscord)} 
	<p>
		<img src = "resources/images/discord.png" style = "float: left; padding-right: 1em;" />
		<span style = "color: #7289DA; font-weight: bold;">Your Discord User ID is not in your profile!</span> If you add it, site moderators can quickly and easily message you about any issues with events that you create. We absolutely won't spam you, and your Discord User ID is not public to anybody else. Don't delay, <a href = "formHandler.php?formClazz=FormEditUser">edit your user profile today!</a>
	</p>
	<br />
{/if}

<p>What would you like to do?</p>
<ul>
{if isset($organization)}
	<li><a href = "viewOrganizer.php?id={$organization.id}">My organization - {$organization.title}</a>
		{if $organization.published eq 0} 
		<em>- not yet published by admin</em>
		{/if}
		<br />&nbsp;&nbsp;&nbsp;&nbsp;Go to your organizer page, to see all the events, venues and staff that are currently active.
	</li>
	<li><a href = "formHandler.php?formClazz=FormNewEvent">Add a new event</a> <em> - won't be public until organizer is published</em></li>
	<li><a href = "formHandler.php?formClazz=FormNewVenue">Add a new venue</a></li>
{else}
	<li><a href = "formHandler.php?formClazz=FormJoinOrganizer">Join onto an existing organizer</strong></a> <em>- if your organization is already registered the site, you can just join up with them.</em></li>
	<li><a href = "formHandler.php?formClazz=FormNewOrganizer">Register as a new organizer</strong></a> <em>- register a new organizer if you cannot find your organizer in the <a href = "listOrganizers.php">organizers list</a>.</em></li>
	<li><a href = "formHandler.php?formClazz=FormNewEvent">Add a new event</a></li>
{/if}

	<li><a href = "formHandler.php?formClazz=FormChangePassword">Change password</a></li>
	<li><a href = "formHandler.php?formClazz=FormEditUser">Edit My Profile</a></li>
	<li><a href = "logout.php">Logout</a></li>

</ul>

