<div class = "box">
	<h2>Events with issues</h2>
	<p>Even if there are lots of issues with the event, only one is shown. Checks for (in order): non-null event website, existing organizer, ticket prices are not &pound;0,  duration(finish - start) is not 0. </p>

	<table>
		<thead>
			<tr>
				<th>Event</th>
				<th>Organizer</th>
				<th>Website</th>
				<th>Issue Description</th>
			</tr>
		</thead>

		<tbody>
		{foreach from = "$listEventsWithIssues" item = "issueEvent"}
			<tr>
				<td><a href = "viewEvent.php?id={$issueEvent.id}">{$issueEvent.title}</a></td>
				<td><a href = "viewOrganizer.php?id={$issueEvent.organizerId}">{$issueEvent.organizerTitle}</a></td>
				<td><a href = "{$issueEvent.website}" target = "_new">{$issueEvent.website}</a></td>
				<td>{$issueEvent.issueDescription}</td>
			</tr>
		{/foreach}
		</body>
	</table>
</div>
