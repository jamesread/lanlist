<p><strong>Active moderators in this time</strong> (last {$moderatorImpact.team.days} days)</p>
{if $moderatorImpact.team.activeModerators > 0}
<ul>
{foreach from=$moderatorImpact.team.byModerator item="mod"}
	{if $mod.total > 0}
	<li>{$mod.username|escape:'html'}</li>
	{/if}
{/foreach}
</ul>
{else}
<p><em>No moderator activity was logged in this period.</em></p>
{/if}

<p><strong>Team impact (last {$moderatorImpact.team.days} days)</strong></p>
<ul>
	<li>{$moderatorImpact.team.organizersChecked} organizer check{if $moderatorImpact.team.organizersChecked != 1}s{/if}</li>
	<li>{$moderatorImpact.team.issuesAddressed} likely issue fix{if $moderatorImpact.team.issuesAddressed != 1}es{/if}</li>
	<li>{$moderatorImpact.team.joinRequestsHandled} join request{if $moderatorImpact.team.joinRequestsHandled != 1}s{/if} handled</li>
</ul>

{if $moderatorImpact.personal}
<p><strong>Your contributions (last {$moderatorImpact.personal.days} days)</strong></p>
<ul>
	<li>{$moderatorImpact.personal.totalActions} maintenance action{if $moderatorImpact.personal.totalActions != 1}s{/if}</li>
	<li>{$moderatorImpact.personal.organizersChecked} organizer check{if $moderatorImpact.personal.organizersChecked != 1}s{/if}</li>
	<li>{$moderatorImpact.personal.issuesAddressed} likely issue fix{if $moderatorImpact.personal.issuesAddressed != 1}es{/if}</li>
</ul>
{/if}

<p><em>Thank you for helping keep event listings accurate for the community.</em></p>

<p><strong>Site health snapshot</strong> (as of {$moderatorImpact.generatedAt|escape:'html'})</p>
<ul>
	<li>Open issues: {$moderatorImpact.siteHealth.currentIssueCount}{if $moderatorImpact.siteHealth.currentIssueCount == 0} — all clear{/if}</li>
	{if $moderatorImpact.siteHealth.issueCount7DaysAgo !== null}
	<li>7-day trend: {$moderatorImpact.siteHealth.issueCount7DaysAgo} → {$moderatorImpact.siteHealth.currentIssueCount}{if $moderatorImpact.siteHealth.trend7Days == 'improved'} (improved){elseif $moderatorImpact.siteHealth.trend7Days == 'increased'} (increased){else} (unchanged){/if}</li>
	{/if}
	{if $moderatorImpact.siteHealth.issueCount30DaysAgo !== null}
	<li>30-day trend: {$moderatorImpact.siteHealth.issueCount30DaysAgo} → {$moderatorImpact.siteHealth.currentIssueCount}{if $moderatorImpact.siteHealth.trend30Days == 'improved'} (improved){elseif $moderatorImpact.siteHealth.trend30Days == 'increased'} (increased){else} (unchanged){/if}</li>
	{/if}
</ul>
