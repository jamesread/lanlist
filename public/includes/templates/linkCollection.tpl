{if $linkCollection->hasLinks()}
<div class = "infobox">

	<h2>{$linkCollection->getTitle()}</h2>
	<ul>
	{foreach from = $linkCollection item = "link"} 
		<li>
			{if not empty($link.iconUrl)}
			<img class = "imageIcon" src = "resources/images/icons/{$link.iconUrl}" alt = "linkIcon" /> 
			{/if}

			{if empty($link.url)}
			{$link.title}
			{else}
			<a href = "{$link.url}">{$link.title}</a>
			{/if}

			{if count($link.children) > 0}
			<ul style = "list-style-type: none; padding-left: 2em;">
			{foreach from=$link.children item = "childLink"}
			<li>
				{if not empty($childLink.iconUrl)}
				<img class = "imageIcon" src = "resources/images/icons/{$childLink.iconUrl}" alt = "linkIcon" /> 
				{/if}

				<a href = "{$childLink.url}">{$childLink.title}</a>
			</li>
			{/foreach}
			</ul>
			{/if}
		</li>
	{/foreach}
	</ul>
</div>
{/if}
