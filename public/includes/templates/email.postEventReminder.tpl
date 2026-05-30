<html>

<body>

<p>Hey {$user.username|escape:'html'},</p>

<p>
We hope your <strong>{$event.title|escape:'html'}</strong> LAN party went well!
</p>

<p>
We noticed that <strong>{$organizer.title|escape:'html'}</strong> does not have any other upcoming events listed on
<a href="{$siteBaseUrl|escape:'html'}/">{$siteTitle|escape:'html'}</a> at the moment.
If you already have dates for your next event, it only takes a minute to add them — players use lanlist to find LAN parties in their area.
</p>

<p>
<a href="{$addEventUrl|escape:'html'}">Add your next event</a>
&nbsp;·&nbsp;
<a href="{$organizerUrl|escape:'html'}">View your organizer page</a>
</p>

<p>
If you are not planning another event any time soon, no worries — thanks for listing with us, and we hope to see you back when the next one is on the calendar.
</p>

</body>

</html>
