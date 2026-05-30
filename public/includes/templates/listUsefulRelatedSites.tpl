<h2>Related site links</h2>

<p><a href="formHandler.php?formClazz=FormNewUsefulRelatedSite">Add link</a> &middot; <a href="usefulRelatedSites.php">View public page</a></p>

{if $relatedSites|@count == 0}
<p><em>No related site links yet.</em></p>
{else}
<table>
	<thead>
		<tr>
			<th>Title</th>
			<th>URL</th>
			<th>Countries</th>
			<th>Sort</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	{foreach from=$relatedSites item="site"}
		<tr>
			<td>{$site.title|escape:'html'}</td>
			<td><a href="{$site.url|escape:'html'}" target="_blank" rel="noopener noreferrer">{$site.url|escape:'html'}</a></td>
			<td>{$site.countrySummary nofilter}</td>
			<td>{$site.sortOrder|escape:'html'}</td>
			<td>
				<a href="formHandler.php?formClazz=FormEditUsefulRelatedSite&amp;formEditUsefulRelatedSite-id={$site.id}">Edit</a>
				&middot;
				<a href="formHandler.php?formClazz=FormDeleteUsefulRelatedSite&amp;formDeleteUsefulRelatedSite-id={$site.id}">Delete</a>
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>
{/if}
