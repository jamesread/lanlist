<html>
<body>

<style type = "text/css">
{literal}
.good {
	color: green;
}

.bad {
	color: red;
}

h1 {
	font-size: 9pt;
}
{/literal}
</style>

<p>Hey.</p>

<p>This is a lanlist.org newsletter for updates between {$newsletterStartDate} and {$newsletterFinishDate}. </p>

{if !empty($listNewUsers) || !empty($listNewEvents) || !empty($listNewOrganizers)}
<h1>News</h1>
{/if}

{if $listNewUsers|@count gt 0}
	<ul>
	{foreach from = "$listNewUsers" item = "item"}
		<li>User "{$item.username}" registered.</li>
	{/foreach}
	</ul>
{/if}

{if $listNewEvents|@count gt 0}
	<ul>
	{foreach from = "$listNewEvents" item = "item"}
		<li>Event "{$item.title}" created by "{$item.createdBy}"</li>
	{/foreach}
	</ul>
{/if}

{if $listNewOrganizers|@count gt 0}
	<ul>
	{foreach from = "$listNewOrganizers" item = "item"}
		<li>New organizer "{$item.title}" registered.</li>
	{/foreach}
	</ul>
{/if}

{if !empty($listJoinRequests)}
<h1>Actions Needed</h1>
{/if}

{if $listJoinRequests|@count gt 0}
	<ul>
	{foreach from = "$listJoinRequests" item = "item"}
		<li class = "bad">The user "{$item.username}" wants to join organizer "{$item.organizerName}".</li>
	{/foreach}
	</ul>
{/if}

{if $issuesList|@count gt 0}
<h1>Issues ({$issuesList|@count})</h1>

	<ul>
	{foreach from = "$issuesList" item = "issue"}
		<li><a href = "http://lanlist.org/viewEvent.php?id={$issue.id}">{$issue.title}</a>: {$issue.issueDescription}</li>
	{/foreach}
	</ul>
{/if}

<p>End of newsletter.</p>
</body>
</html>
