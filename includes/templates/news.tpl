<h2>News</h2>
<p>{$news|@count} article(s).</p>

{foreach from = $news item = article}
	<h2>Article: {$article.title}</h2>
{/foreach}
