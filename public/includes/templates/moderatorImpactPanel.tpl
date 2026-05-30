<div class="box moderator-impact">
	<div class="moderator-impact-header">
		<h3>Summary &amp; thanks</h3>
		<p class="moderator-impact-lede">what's open right now, and what the moderation team has been contributing lately. issue trends are compared with past newsletter runs.</p>
	</div>

	<section class="moderator-impact-section">
		<h3 class="moderator-impact-section-title">Issues</h3>
		<div class="moderator-impact-grid">
			<a href="#events-with-issues" class="moderator-impact-stat moderator-impact-stat--link">
				<span class="moderator-impact-stat-label">Open now</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.siteHealth.currentIssueCount}</span>
				{if $moderatorImpact.siteHealth.currentIssueCount == 0}
				<span class="moderator-impact-stat-note good">Nothing flagged</span>
				{/if}
			</a>
			{if $moderatorImpact.siteHealth.issueCount7DaysAgo !== null}
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Past week</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.siteHealth.issueCount7DaysAgo} → {$moderatorImpact.siteHealth.currentIssueCount}</span>
				<span class="moderator-impact-stat-note {if $moderatorImpact.siteHealth.trend7Days == 'improved'}good{elseif $moderatorImpact.siteHealth.trend7Days == 'increased'}warn{/if}">
					{if $moderatorImpact.siteHealth.trend7Days == 'improved' || $moderatorImpact.siteHealth.trend7Days == 'increased'}
						{$moderatorImpact.siteHealth.delta7DaysLabel|escape:'html'}
					{else}
						No change
					{/if}
				</span>
			</div>
			{/if}
			{if $moderatorImpact.siteHealth.issueCount30DaysAgo !== null}
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Past month</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.siteHealth.issueCount30DaysAgo} → {$moderatorImpact.siteHealth.currentIssueCount}</span>
				<span class="moderator-impact-stat-note {if $moderatorImpact.siteHealth.trend30Days == 'improved'}good{elseif $moderatorImpact.siteHealth.trend30Days == 'increased'}warn{/if}">
					{if $moderatorImpact.siteHealth.trend30Days == 'improved' || $moderatorImpact.siteHealth.trend30Days == 'increased'}
						{$moderatorImpact.siteHealth.delta30DaysLabel|escape:'html'}
					{else}
						No change
					{/if}
				</span>
			</div>
			{/if}
		</div>
	</section>

	<section class="moderator-impact-section">
		<h3 class="moderator-impact-section-title">Team contributions <span class="moderator-impact-period">last {$moderatorImpact.team.days} days</span></h3>
		<div class="moderator-impact-grid">
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Organizers checked</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.team.organizersChecked}</span>
			</div>
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Issues cleared</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.team.issuesAddressed}</span>
			</div>
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Join requests handled</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.team.joinRequestsHandled}</span>
			</div>
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Moderators active</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.team.activeModerators}<span class="moderator-impact-stat-of">/{$moderatorImpact.team.totalModerators}</span></span>
			</div>
		</div>
	</section>

	{if $moderatorImpact.personal}
	<section class="moderator-impact-section moderator-impact-section--personal">
		<h3 class="moderator-impact-section-title">Your contributions <span class="moderator-impact-period">last {$moderatorImpact.personal.days} days</span></h3>
		<div class="moderator-impact-grid">
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Actions logged</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.personal.totalActions}</span>
			</div>
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Organizers checked</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.personal.organizersChecked}</span>
			</div>
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Issues cleared</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.personal.issuesAddressed}</span>
			</div>
			<div class="moderator-impact-stat">
				<span class="moderator-impact-stat-label">Join requests handled</span>
				<span class="moderator-impact-stat-value">{$moderatorImpact.personal.joinRequestsHandled}</span>
			</div>
		</div>

		{if $moderatorImpact.personal.recentActions|@count gt 0}
		<div class="moderator-impact-table-wrap">
			<h4 class="moderator-impact-subtitle">Recent actions</h4>
			<table class="moderator-impact-table">
				<thead>
					<tr>
						<th>When</th>
						<th>Action</th>
						<th>Detail</th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$moderatorImpact.personal.recentActions item="action"}
					<tr>
						<td class="moderator-impact-table-when">{$action.timestamp|escape:'html'}</td>
						<td>{$action.label|escape:'html'}</td>
						<td class="moderator-impact-table-detail">{$action.content|escape:'html'}</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		{/if}
	</section>
	{/if}

	{if $moderatorImpact.team.byModerator|@count gt 0}
	<section class="moderator-impact-section">
		<h3 class="moderator-impact-section-title">By moderator <span class="moderator-impact-period">this month</span></h3>
		<div class="moderator-impact-table-wrap">
			<table class="moderator-impact-table">
				<thead>
					<tr>
						<th>Moderator</th>
						<th>Actions</th>
						<th>Checks</th>
						<th>Fixes</th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$moderatorImpact.team.byModerator item="row"}
					{if $row.total gt 0}
					<tr>
						<td><strong>{$row.username|escape:'html'}</strong></td>
						<td>{$row.total}</td>
						<td>{$row.organizersChecked}</td>
						<td>{$row.issuesAddressed}</td>
					</tr>
					{/if}
				{/foreach}
				</tbody>
			</table>
		</div>
	</section>
	{/if}

	<p class="moderator-impact-footnote">Figures as of {$moderatorImpact.generatedAt|escape:'html'}. Counts come from audit logs; issue trends from past newsletter runs.</p>
</div>
