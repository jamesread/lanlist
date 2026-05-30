<h2>Export to OrgaTalk LAN Party Database</h2>

<p>
	For <strong>{$organizer.title|escape}</strong>. This page generates
	<a href="{$lanpartydbRepoUrl|escape}" rel="noopener noreferrer">OrgaTalk LAN Party Database</a>
	(<code>lanpartydb/data</code>) TOML you can paste into a GitHub pull request.
	See the <a href="{$lanpartydbFormatUrl|escape}" rel="noopener noreferrer">format specification</a>
	for field details.
</p>

<h3>Why archive your LANs?</h3>
<p>
	LAN party history is easy to lose when forums shut down, domains expire, or old sites go offline.
	The OrgaTalk database is a community-maintained, open archive of public LAN parties and series.
	Contributing under <em>your own GitHub username</em> keeps provenance clear (who added what),
	helps other organizers discover past events, and preserves dates, venues, and links for researchers
	and future attendees. A PR is a small, durable gift to the wider LAN community.
</p>

<h3>How to contribute</h3>
<ol>
	<li>Fork <a href="{$lanpartydbRepoUrl|escape}" rel="noopener noreferrer">github.com/lanpartydb/data</a>.</li>
	<li>Create the files below at the suggested paths (one file per section).</li>
	<li>Review slugs, <code>city</code> (shown as <code>TBC</code> when lanlist has no city), and URLs before you submit.</li>
	<li>Open a pull request from your fork — commits should be on your account, not a shared bot.</li>
</ol>

<p>
	<strong>Included:</strong> finished, published events only.
	{if $export.party_count eq 0}
	There are no eligible past public events to export yet.
	{else}
	{$export.party_count} party file{if $export.party_count ne 1}s{/if} plus one series file.
	{/if}
</p>

{if $export.not_eligible|@count gt 0}
<h3>Events not exported</h3>
<ul>
	{foreach from=$export.not_eligible item=row}
	<li>
		{if $row.id gt 0}<a href="viewEvent.php?id={$row.id}">{$row.title|escape}</a>{else}{$row.title|escape}{/if}
		— {$row.reason|escape}
	</li>
	{/foreach}
</ul>
{/if}

<h3>Series — <code>{$export.series_filename|escape}</code></h3>
<p><button type="button" class="lanpartydb-copy-btn" data-target="lanpartydb-series-toml">Copy series TOML</button></p>
<textarea id="lanpartydb-series-toml" class="lanpartydb-export-toml" readonly rows="12">{$export.series_toml|escape}</textarea>

{if $export.parties|@count gt 0}
<h3>Parties</h3>
{foreach from=$export.parties item=party}
<h4><code>{$party.filename|escape}</code> — {$party.title|escape}</h4>
<p><button type="button" class="lanpartydb-copy-btn" data-target="lanpartydb-party-{$party.id}">Copy party TOML</button></p>
<textarea id="lanpartydb-party-{$party.id}" class="lanpartydb-export-toml" readonly rows="18">{$party.toml|escape}</textarea>
{/foreach}
{/if}

<h3>All files (for one-shot copy)</h3>
<p><button type="button" class="lanpartydb-copy-btn" data-target="lanpartydb-all-toml">Copy everything</button></p>
<textarea id="lanpartydb-all-toml" class="lanpartydb-export-toml lanpartydb-export-toml--all" readonly rows="28">{$export.all_toml|escape}</textarea>

<p><a href="account.php">&larr; Back to account</a></p>

<script>
(function () {
	function copyFromTextarea(id) {
		var el = document.getElementById(id);
		if (!el) {
			return;
		}
		el.select();
		el.setSelectionRange(0, el.value.length);
		try {
			document.execCommand('copy');
		} catch (e) {
			navigator.clipboard.writeText(el.value).catch(function () {});
		}
	}
	document.querySelectorAll('.lanpartydb-copy-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			copyFromTextarea(btn.getAttribute('data-target'));
			var label = btn.textContent;
			btn.textContent = 'Copied!';
			setTimeout(function () { btn.textContent = label; }, 1500);
		});
	});
})();
</script>
