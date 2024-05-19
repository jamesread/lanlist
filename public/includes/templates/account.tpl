<h2>Account</h2>
{if empty($userEmail) || empty($usernameSteam)} 
	<p class = "notification">
		<img src = "resources/images/steam.png" style = "float: left; padding-right: 1em;" />
		<img src = "resources/images/email.png" style = "float: left; padding-right: 1em;" />
		<span class = "karmaBad">Your steam username or email address is not in your profile!</span> Please could you <a href = "formHandler.php?formClazz=FormEditUser">update your profile with your contact details</a>, so that we can get in touch with you easily... We look after this site and will only send you messages if the events you submit have problems. We will <strong>not</strong> spam you, really!
	</p>
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

<h3>New - Free Advertising</h3>

<p>
	We are experimenting with banners in the header of the site. For now, they'll just be advertising events from organizers we know and love. We're not asking for money, just communication. Get in <a href = "contact.php">contact</a> and we can set a free banner up for you too.
</p>
