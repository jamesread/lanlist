<h2>Join requests</h2>
<p>Be careful before approving requests, you could be granting a random person access to events they have no legitimate control over. Check the organizer website if possible.</p>

{if count($requests) eq 0}
	<p>No join requests.</p>
{else}
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>username</th>
				<th>Organizer</th>
				<th>Organizer URL</th>
				<th>Actions</th>
			</tr>
		</thead>

		<tbody>
		{foreach from = "$requests" item = "request"}
			<tr>
				<td>{$request.id}</td>
				<td><a href = "viewUser.php?id={$request.userId}">{$request.username}</a></td>
				<td><a href = "viewOrganizer.php?id={$request.organizerId}">{$request.organizerTitle}</a></td>
				<td><a target = "_new" href = "{$request.organizerUrl}">{$request.organizerUrl}</a></td>
				<td>
					<a href = "joinRequests.php?action=approve&amp;id={$request.id}">Approve</a> 
					<a href = "joinRequests.php?action=deny&amp;id={$request.id}">Deny</a> 
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{/if}
