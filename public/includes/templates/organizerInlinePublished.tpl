<div class="inline-edit inline-edit--published{if !empty($compact)} inline-edit--compact{/if}"
	data-inline-entity="organizer"
	data-inline-id="{$organizer.id}"
	data-inline-field="published"
	data-inline-mode="toggle"
	data-inline-value="{$organizer.published|default:0}">
	<div class="inline-edit__read">
		{if empty($compact)}
		<span class="inline-edit__value">
			{if !empty($organizer.published)}
				yes
			{else}
				<span class="bad">no</span>
			{/if}
		</span>
		{/if}
		{if !empty($canPublishOrganizer)}
			{if empty($organizer.published)}
				<button type="button" class="inline-edit__publish" onclick="return window.lanlistInlineEditToggle(this, 1)">Publish</button>
			{elseif empty($compact)}
				<button type="button" class="inline-edit__unpublish" onclick="return window.lanlistInlineEditToggle(this, 0)">Unpublish</button>
			{/if}
		{/if}
	</div>
	<p class="inline-edit__error" hidden></p>
</div>
