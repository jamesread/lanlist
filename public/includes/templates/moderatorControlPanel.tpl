<h2>Moderator control panel</h2>

<p>Review site health, unpublished organizers, and organizers without upcoming events.</p>

<p class="moderator-newsletter-status{if $moderatorImpact.siteHealth.currentIssueCount > 0} moderator-newsletter-status--send{else} moderator-newsletter-status--skip{/if}">
	<strong>Moderator newsletter:</strong>
	{if $moderatorImpact.siteHealth.currentIssueCount > 0}
		will send on the next scheduled run.
	{else}
		will not send on the next scheduled run.
	{/if}
</p>

<ul>
	<li><a href="moderation-rando.php">Random organizer queue</a></li>
	<li><a href="joinRequests.php">Join requests</a></li>
	<li><a href="listSchedulerTasks.php">Background jobs</a></li>
</ul>
