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
		<li><strong>Primary group:</strong> <a href = "viewGroup.php?id={$viewUser.groupId}">{$viewUser.groupTitle}</a></li>
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

{if isset($userAuditLogs)}
<div>
	<h3>Audit log</h3>

	{if count($userAuditLogs) eq 0}
	<p>No audit log entries for this user.</p>
	{else}
	<p>Recent audit log entries where this user is the related account (last {count($userAuditLogs)}).</p>
	<table>
		<thead>
			<tr>
				<th>Event type</th>
				<th>Organizer</th>
				<th>Timestamp</th>
				<th>Content</th>
			</tr>
		</thead>
		<tbody>
			{foreach from = $userAuditLogs item = itemAuditLog}
			<tr>
				<td>
					{if not empty($itemAuditLog.eventType)}
					{$itemAuditLog.eventType|escape:'html'}
					{else}
					<span class = "subtle">&mdash;</span>
					{/if}
				</td>
				<td>
					{if not empty($itemAuditLog.relatedOrganizer)}
					<a href = "viewOrganizer.php?id={$itemAuditLog.relatedOrganizer}">{if $itemAuditLog.relatedOrganizerTitle}{$itemAuditLog.relatedOrganizerTitle|escape:'html'}{else}#{$itemAuditLog.relatedOrganizer}{/if}</a>
					{else}
					<span class = "subtle">&mdash;</span>
					{/if}
				</td>
				<td>{$itemAuditLog.timestamp|escape:'html'}</td>
				<td>{$itemAuditLog.content|escape:'html'}</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
	<p><a href = "listLogs.php?full=1">View all logs</a></p>
	{/if}
</div>
{/if}
