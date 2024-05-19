<div class = "infobox">
	<h2>How do I add an event?</h2>

{if not $isLoggedIn}
	<p><a href = "loginregister.php">Register for an account (or login)</a>, there is a form on the user profile page.</p>
{else}
	<p>You're logged in, yay! So use the <a href = "formHandler.php?formClazz=FormNewEvent">add event form</a>. </p>
	<p>If you're having problems adding an event, remember you can always <a href = "contact.php">contact us</a> easily.</p>
{/if}
</div>


