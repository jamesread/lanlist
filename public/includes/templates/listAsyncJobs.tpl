<h2>Background jobs</h2>

<p class="subtle">Jobs are executed by <a href="https://github.com/OliveTin/OliveTin">OliveTin</a> (scheduled moderator newsletter, moderation favicon queue). The legacy PHP cron scheduler is no longer used.</p>

<h3>OliveTin connection</h3>
{if !$oliveTinConnection.configured}
<p class="warn">OliveTin is not configured (set <code>OLIVETIN_BASE_URL</code> and <code>OLIVETIN_API_KEY</code>).</p>
{elseif $oliveTinConnection.ok}
<p><strong>Connected</strong> to {$oliveTinConnection.baseUrl|escape:'html'} (Init RPC succeeded).</p>
<dl style="margin-top:0;">
	{if !empty($oliveTinConnection.init.pageTitle)}
	<dt>Page title</dt>
	<dd>{$oliveTinConnection.init.pageTitle|escape:'html'}</dd>
	{/if}
	{if !empty($oliveTinConnection.init.currentVersion)}
	<dt>Version</dt>
	<dd>{$oliveTinConnection.init.currentVersion|escape:'html'}{if !empty($oliveTinConnection.init.availableVersion)} (update available: {$oliveTinConnection.init.availableVersion|escape:'html'}){/if}</dd>
	{/if}
	{if !empty($oliveTinConnection.init.authenticatedUser)}
	<dt>Authenticated as</dt>
	<dd>{$oliveTinConnection.init.authenticatedUser|escape:'html'}{if !empty($oliveTinConnection.init.authenticatedUserProvider)} via {$oliveTinConnection.init.authenticatedUserProvider|escape:'html'}{/if}</dd>
	{/if}
</dl>
{else}
<p class="warn"><strong>Connection failed</strong> for {$oliveTinConnection.baseUrl|escape:'html'}: {$oliveTinConnection.error|escape:'html'}</p>
{/if}

{if $newsletterWatermark}
<p><strong>Last moderator newsletter run:</strong> {$newsletterWatermark|escape:'html'}</p>
{/if}

<table>
	<thead>
		<tr>
			<th>ID</th>
			<th>Type</th>
			<th>Organizer</th>
			<th>Status</th>
			<th>Queued</th>
			<th>Started</th>
			<th>Finished</th>
			<th>OliveTin tracking</th>
			<th>Details</th>
			<th>Actions</th>
		</tr>
	</thead>

	<tbody>
	{foreach from=$listAsyncJobs item="job"}
		<tr>
			<td>{$job.id}</td>
			<td>{$job.job_type_label|escape:'html'}</td>
			<td>
				{if not empty($job.organizer_id)}
					<a href="viewOrganizer.php?id={$job.organizer_id}">#{$job.organizer_id}{if $job.organizer_title} — {$job.organizer_title|escape:'html'}{/if}</a>
				{else}
					<span class="subtle">&mdash;</span>
				{/if}
			</td>
			<td>{$job.status|escape:'html'}</td>
			<td>{$job.created_at|escape:'html'}</td>
			<td>{if $job.started_at}{$job.started_at|escape:'html'}{else}<span class="subtle">&mdash;</span>{/if}</td>
			<td>{if $job.finished_at}{$job.finished_at|escape:'html'}{else}<span class="subtle">&mdash;</span>{/if}</td>
			<td>{if $job.execution_tracking_id}{$job.execution_tracking_id|escape:'html'}{else}<span class="subtle">&mdash;</span>{/if}</td>
			<td>
				{if !empty($job.error_message)}
					<span class="warn">{$job.error_message|escape:'html'}</span>
				{elseif !empty($job.metadataDecoded.updateCount)}
					{$job.metadataDecoded.updateCount} update(s){if $job.metadataDecoded.emailSent}, email sent{/if}
				{elseif isset($job.metadataDecoded.emailSent) && !$job.metadataDecoded.emailSent}
					No updates in window
				{else}
					<span class="subtle">&mdash;</span>
				{/if}
			</td>
			<td>{if $job.status != 'completed'}<a href="misc.php?action=abandonAsyncJob&amp;id={$job.id}">Abandon</a>{else}<span class="subtle">&mdash;</span>{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{if $listAsyncJobs|@count == 0}
<p><em>No job rows yet.</em></p>
{/if}
