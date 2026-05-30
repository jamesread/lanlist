<h2>Useful related sites</h2>

{if $relatedSiteGroups|@count == 0}
<p><em>No related site links have been added yet.</em></p>
{else}
{foreach from=$relatedSiteGroups item="group"}
<section class="usefulRelatedSites-group">
	<h3>
		{if $group.isGlobal}
		{$group.label|escape}
		{elseif $group.eventsListUrl}
		<a href="{$group.eventsListUrl|escape:'html'}" title="LAN Parties in {$group.country|escape}">{if $group.flagHtml != ''}<span class="usefulRelatedSites-groupFlag" aria-hidden="true">{$group.flagHtml nofilter}</span> {/if}{$group.label|escape}</a>
		{else}
		{if $group.flagHtml != ''}<span class="usefulRelatedSites-groupFlag" aria-hidden="true">{$group.flagHtml nofilter}</span> {/if}{$group.label|escape}
		{/if}
	</h3>
	<ul>
	{foreach from=$group.sites item="site"}
		<li>
			<a href="{$site.url|escape:'html'}" target="_blank" rel="noopener noreferrer">{$site.title|escape:'html'}</a>
			{if $site.description != ''} &mdash; {$site.description|escape:'html'}{/if}
		</li>
	{/foreach}
	</ul>
</section>
{/foreach}
{/if}
