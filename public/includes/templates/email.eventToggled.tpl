<html>

<body>

<p>Hey {$user.username}.</p>

<p>This is an automated notification, just to let you know your event "{$event.eventTitle}" has been changed to {if $event.published}published{else}unpublished{/if}</strong>.</p>

<p>It was changed by <strong>{$publisherUsername}</strong>.<p>

<p>View your event here: <a href = "https://www.lanlist.info/viewEvent.php?id={$event.id}">{$event.eventTitle}</a><p>

</body>

</html>
