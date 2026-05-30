<style type="text/css">
.organizer-moderator-fields .bad {
	background-color: salmon;
}

.organizer-moderator-fields .warn {
	background-color: #fff8dc;
	border-left: 3px solid goldenrod;
	padding: 0.35em 0.5em;
}

.organizer-moderator-fields dl {
	display: grid;
	grid-template-columns: max-content auto;
	gap: 1em;
	margin-top: 0;
}

.organizer-moderator-fields dt {
	font-weight: 600;
}
</style>

<h3>Moderation helper</h3>

<dl>
	<dt>ID</dt>
	<dd>{$organizer.id}</dd>

	<dt>Published</dt>
	<dd>
	{include file='organizerInlinePublished.tpl' organizer=$organizer canPublishOrganizer=$canPublishOrganizer}
	</dd>

	<dt>Last checked</dt>
	<dd>{if $organizer.lastChecked}{$organizer.lastChecked|escape:'html'}{else}Never{/if}</dd>

	{if !empty($organizer.genericEmail)}
	<dt>Generic email</dt>
	<dd><a href="formHandler.php?formClazz=FormSendEmailToUser&amp;formSendEmailToUser-email={$organizer.genericEmail|escape:'url'}">{$organizer.genericEmail|escape:'html'}</a></dd>
	{/if}

	<dt>Steam group</dt>
	<dd class="{$organizer.steamGroupRowClass}">
	{if not empty($organizer.steamGroupUrl)}
		<a target="_blank" rel="noopener noreferrer" href="{$organizer.steamGroupHref}">{$organizer.steamGroupUrl|escape:'html'}</a>
		{if $organizer.steamGroupRowClass eq 'warn'}
			<em>&nbsp;(Does not match typical Steam URLs — verify manually.)</em>
		{/if}
	{else}
		<em>Not set</em>
	{/if}
	</dd>

	<dt>Discord invite</dt>
	<dd class="{$organizer.discordInviteRowClass}" data-inline-row-class-target>
	{include file='organizerInlineDiscordInvite.tpl' organizer=$organizer}
	</dd>

	<dt>Banner logo</dt>
	<dd class="{$organizer.logoRowClass}">
	{if $organizer.logoFileExists}
		<img src="resources/images/organizer-logos/{$organizer.id}.jpg" alt="Organizer banner" style="max-height: 100px; max-width: 240px; border: 1px solid #ccc;" />
	{else}
		<em>Missing file (expected resources/images/organizer-logos/{$organizer.id}.jpg)</em>
	{/if}
	</dd>

	<dt>LPPS feed</dt>
	<dd>
	<p class="subtle"><a href="lpps.php">About LPPS</a> (optional; manual event entry is always supported.)</p>
	{if not empty($organizer.lppsUrl)}
		<a target="_blank" rel="noopener noreferrer" href="{$organizer.lppsUrl|escape:'html'}">{$organizer.lppsUrl|escape:'html'}</a>
	{else}
		<em>Not set</em>
	{/if}
	{if !empty($organizer.lppsAdminDisabled)}
		<p class="warn"><strong>LPPS crawl disabled by admin.</strong></p>
	{elseif !empty($organizer.lppsUrl)}
		<p><em>Eligible for LPPS crawl when the job is enabled.</em></p>
	{/if}
	{if $organizer.lppsLastCrawl}
		<p style="margin-top:.75em;"><strong>Last LPPS crawl:</strong> {$organizer.lppsLastCrawl|escape:'html'}
		{if $organizer.lppsCrawlSuccess === null}
			&mdash; <em>success unknown</em>
		{elseif $organizer.lppsCrawlSuccess}
			&mdash; <span>success</span>
		{else}
			&mdash; <span class="bad">failed</span>
		{/if}
		</p>
		{if !empty($organizer.lppsCrawlResult)}
		<p><strong>Result:</strong> {$organizer.lppsCrawlResult|escape:'html'}</p>
		{/if}
	{else}
		<p style="margin-top:.75em;"><em>Never crawled.</em></p>
	{/if}
	</dd>

	<dt>Favicon</dt>
	<dd class="{$organizer.faviconRowClass}">
	<p><strong>Collect favicon from website:</strong> {if $organizer.useFaviconEnabled}yes{else}no{/if}</p>
	{if !empty($organizer.faviconRefetch)}<p><strong>Refetch on next crawl:</strong> yes</p>{/if}
	{if $organizer.faviconFileExists}
		<img src="resources/images/organizer-favicons/{$organizer.id}.png" width="32" height="32" alt="Collected favicon" />
	{else}
		<em>No favicon PNG (resources/images/organizer-favicons/{$organizer.id}.png)</em>
	{/if}
	{if $organizer.useFaviconEnabled && !$organizer.faviconFileExists}
		<p><em>Favicon collection is enabled but file is missing (job may not have run yet, or fetch failed).</em></p>
	{/if}

	{if $latestFaviconAsyncJob}
		<p style="margin-top:1em;"><strong>Latest OliveTin favicon job:</strong></p>
		<dl style="margin-top:0;">
			<dt>Job ID</dt>
			<dd>{$latestFaviconAsyncJob.id}</dd>
			<dt>Status</dt>
			<dd>{$latestFaviconAsyncJob.status|escape:'html'}</dd>
			<dt>OliveTin tracking</dt>
			<dd>{if !empty($latestFaviconAsyncJob.execution_tracking_id)}{$latestFaviconAsyncJob.execution_tracking_id|escape:'html'}{else}<em>Awaiting dispatch or not yet confirmed</em>{/if}</dd>
			<dt>Queued</dt>
			<dd>{$latestFaviconAsyncJob.created_at|escape:'html'}</dd>
			<dt>Accepted by OliveTin</dt>
			<dd>{if !empty($latestFaviconAsyncJob.started_at)}{$latestFaviconAsyncJob.started_at|escape:'html'}{else}&mdash;{/if}</dd>
			<dt>Finished</dt>
			<dd>{if !empty($latestFaviconAsyncJob.finished_at)}{$latestFaviconAsyncJob.finished_at|escape:'html'}{else}&mdash;{/if}</dd>
			{if !empty($latestFaviconAsyncJob.error_message)}
			<dt>Note</dt>
			<dd class="warn">{$latestFaviconAsyncJob.error_message|escape:'html'}</dd>
			{/if}
			{if !empty($latestFaviconAsyncJob.metadataDecoded.enqueuedByUserId)}
			<dt>Queued by user id</dt>
			<dd>{$latestFaviconAsyncJob.metadataDecoded.enqueuedByUserId}</dd>
			{/if}
		</dl>
	{/if}

	{if $organizer.useFaviconEnabled && !$hasActiveFaviconAsyncJob && !empty($organizer.websiteUrl)}
	<form method="post" action="misc.php" style="margin-top:.75em;">
		<input type="hidden" name="action" value="enqueueOrganizerFaviconFetch" />
		<input type="hidden" name="organizerId" value="{$organizer.id}" />
		<button type="submit">Queue favicon fetch (async via OliveTin)</button>
	</form>
	{elseif $organizer.useFaviconEnabled && $hasActiveFaviconAsyncJob}
		<p style="margin-top:.75em;"><em>A favicon queue job is already pending or processing for this organizer.</em></p>
	{/if}
	</dd>
</dl>

<p>
	<a href="formHandler.php?formClazz=FormEditOrganizer&amp;formEditOrganizer-id={$organizer.id}">Edit organizer</a>
	&nbsp;|&nbsp;
	<a href="moderation-rando.php?updateLastChecked={$organizer.id}">Mark last checked (no events)</a>
	&nbsp;|&nbsp;
	<a href="moderation-rando.php?stale={$organizer.id}">Mark stale</a>
</p>
