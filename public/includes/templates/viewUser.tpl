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
