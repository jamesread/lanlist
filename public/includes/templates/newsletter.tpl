<html>
<body>

<style type="text/css">
{literal}
.bad {
	color: red;
}

h1 {
	font-size: 11pt;
	margin-top: 1.2em;
}

ul {
	padding-left: 1.2em;
}
{/literal}
</style>

<p>Hey.</p>

<p>This is a {$siteTitle} <strong>moderator newsletter</strong> — the same site-check snapshot as the <a href="{$siteBaseUrl}/siteChecks.php">moderator control panel</a>, as of {$newsletterFinishDate}.</p>

{if $listEventsWithIssues|@count gt 0}
<h1>Events with issues ({$listEventsWithIssues|@count})</h1>
<ul>
{foreach from=$listEventsWithIssues item="issueEvent"}
	<li>
		<a href="{$siteBaseUrl}/viewEvent.php?id={$issueEvent.id}">{$issueEvent.title|escape:'html'}</a>
		{if !empty($issueEvent.organizerTitle)} ({$issueEvent.organizerTitle|escape:'html'}){/if}:
		{$issueEvent.issueDescription|escape:'html'}
		{if $issueEvent.issueDescription == 'No tickets defined for event'}
			&mdash; <a href="{$siteBaseUrl}/misc.php?action=markTicketsNotReleased&amp;id={$issueEvent.id}">Mark tickets not yet released</a>
		{/if}
	</li>
{/foreach}
</ul>
{/if}

{if $listEventsWithSilencedTicketWarning|@count gt 0}
<h1>Silenced ticket warnings ({$listEventsWithSilencedTicketWarning|@count})</h1>
<ul>
{foreach from=$listEventsWithSilencedTicketWarning item="silencedEvent"}
	<li>
		<a href="{$siteBaseUrl}/viewEvent.php?id={$silencedEvent.id}">{$silencedEvent.title|escape:'html'}</a>
		{if !empty($silencedEvent.organizerTitle)} ({$silencedEvent.organizerTitle|escape:'html'}){/if}:
		Ticket warning silenced for {$silencedEvent.ticketsNotReleasedDaysRemaining} more day{if $silencedEvent.ticketsNotReleasedDaysRemaining != 1}s{/if}
	</li>
{/foreach}
</ul>
{/if}

{if $listUnpublishedOrganizers|@count gt 0}
<h1>Unpublished organizers ({$listUnpublishedOrganizers|@count})</h1>
<ul>
{foreach from=$listUnpublishedOrganizers item="itemOrganizer"}
	<li>
		<a href="{$siteBaseUrl}/viewOrganizer.php?id={$itemOrganizer.id}">{$itemOrganizer.title|escape:'html'}</a>
		{if !empty($itemOrganizer.websiteUrl)} — {$itemOrganizer.websiteUrl|escape:'html'}{/if}
	</li>
{/foreach}
</ul>
{/if}

{if $listOrganizers|@count gt 0}
<h1>Organizers with no upcoming events ({$listOrganizers|@count})</h1>
<ul>
{foreach from=$listOrganizers item="itemOrganizer"}
	<li>
		<a href="{$siteBaseUrl}/viewOrganizer.php?id={$itemOrganizer.id}">{$itemOrganizer.title|escape:'html'}</a>
		{if !empty($itemOrganizer.websiteUrl)} — {$itemOrganizer.websiteUrl|escape:'html'}{/if}
	</li>
{/foreach}
</ul>
{/if}

<p>End of newsletter.</p>
</body>
</html>
