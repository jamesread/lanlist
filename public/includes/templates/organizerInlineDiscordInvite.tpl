<div class="inline-edit"
	data-inline-entity="organizer"
	data-inline-id="{$organizer.id}"
	data-inline-field="discordInviteUrl"
	data-inline-value="{$organizer.discordInviteUrl|default:''|escape:'html'}">
	<div class="inline-edit__read">
		<span class="inline-edit__value">
			{if not empty($organizer.discordInviteUrl)}
				<a class="inline-edit__link" target="_blank" rel="noopener noreferrer" href="{$organizer.discordInviteHref}">{$organizer.discordInviteUrl|escape:'html'}</a>
			{else}
				<em class="inline-edit__empty">Not set</em>
			{/if}
		</span>
		<button type="button" class="inline-edit__edit">Edit</button>
		{if $organizer.discordInviteRowClass eq 'warn'}
			<em class="inline-edit__hint">&nbsp;(Does not match typical Discord URLs — verify manually.)</em>
		{/if}
	</div>
	<div class="inline-edit__form" hidden>
		<input type="url" class="inline-edit__input" value="{$organizer.discordInviteUrl|default:''|escape:'html'}" maxlength="255" placeholder="https://discord.gg/…" />
		<button type="button" class="inline-edit__save">Save</button>
		<button type="button" class="inline-edit__cancel">Cancel</button>
	</div>
	<p class="inline-edit__error" hidden></p>
</div>
