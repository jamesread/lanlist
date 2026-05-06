<h2>News</h2>
<p>{$news|@count} article(s).</p>

{foreach from = $news item = article}
	<h2>Article: {$article.title|escape:'html'}</h2>
{/foreach}
