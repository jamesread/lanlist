<div>
	<h2>User: {$viewUser.username}</h2>
	<ul>
		<li><strong>User</strong>: {$viewUser.username}</li>
		<li><strong>Steam Username:</strong> {$viewUser.usernameSteam|default:'???'}</li>
		<li><strong>Discord ID:</strong> 
		{if empty($viewUser.discordUser)}
			???
		{else}
			<a target = "_new" href = "https://discordapp.com/users/{$viewUser.discordUser}/">DM on Discord</a>
		{/if}
		</li>
		<li><strong>Primary group:</strong> {$viewUser.groupTitle}</li>
		<li><strong>Last login:</strong> {$viewUser.lastLogin}</li>
		<li><strong>Registered:</strong> {$viewUser.registered}</li>
		<li><strong>Email:</strong> {$viewUser.email}</li>

		<li><strong>Organizer:</strong>
		{if !empty($viewUser['organizerId'])}
		<a href = "viewOrganizer.php?id={$viewUser.organizerId}">{$viewUser.organizerTitle}</a>
		{else}
		None
		{/if}
		</li>
	</ul>
</div>


{if isset($loggedEmails)}
<div>
	<h3>Logged emails to this user</h3>

	{if count($loggedEmails) eq 0}
	<p>No emails have been sent to this user from the web interface.<p>
	{else}
	<p>This email shows the last 10 emails sent to this user from the website.</p>
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Sent</th>
					<th>Subject</th>
				</tr>
			</thead>
			<tbody>
				{foreach from = $loggedEmails item = itemLoggedEmail}
				<tr>
					<td>{$itemLoggedEmail.id}</td>
					<td>{$itemLoggedEmail.sent}</td>
					<td>{$itemLoggedEmail.subject}</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
	{/if}
</div>
{/if}
