<html>

<body>

<p>Hey {$user.username|escape:'html'},</p>

<p>This is an automated notification from <a href="{$siteBaseUrl}/">{$siteTitle|escape:'html'}</a>.</p>

<p>
{if $entityType eq 'event'}
Your event <strong><a href="{$entityUrl|escape:'html'}">{$entityTitle|escape:'html'}</a></strong> was updated by <strong>{$editorUsername|escape:'html'}</strong>.
{else}
Your organizer <strong><a href="{$entityUrl|escape:'html'}">{$entityTitle|escape:'html'}</a></strong> was updated by <strong>{$editorUsername|escape:'html'}</strong>.
{/if}
</p>

{if $changes|@count gt 0}
<p>Changes:</p>
<ul>
{foreach from=$changes item=change}
	<li><strong>{$change.label|escape:'html'}:</strong> {$change.old|escape:'html'} &rarr; {$change.new|escape:'html'}</li>
{/foreach}
</ul>
{/if}

<p><a href="{$entityUrl|escape:'html'}">View {if $entityType eq 'event'}event{else}organizer{/if}</a></p>

</body>

</html>
