<html>

<body>

<p>Hey {$user.username|escape:'html'},</p>

<p>This is an automated notification from <a href="{$siteBaseUrl}/">{$siteTitle|escape:'html'}</a>.</p>

<p>
A new event for your organizer <strong>{$event.organizerTitle|escape:'html'}</strong> was created:
<strong><a href="{$eventUrl|escape:'html'}">{$event.eventTitle|escape:'html'}</a></strong>.
</p>

<p>
Created by <strong>{$creatorUsername|escape:'html'}</strong>{if $creatorSiteRole} ({$creatorSiteRole|escape:'html'}){/if}.
</p>

<p><a href="{$eventUrl|escape:'html'}">View event</a></p>

</body>

</html>
