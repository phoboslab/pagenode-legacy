<div class="row field">
	<div class="nine columns">
		<label><?= f($name, 'TitleCase') ?></label>
		<input
			placeholder="URL"
			name="data[<?= f($name) ?>]" type="text" 
			class="field url full-width upload-<?= f($name) ?>" 
			value="<?= f($field->get()) ?>"/>
	</div>
	<div class="three columns">
		<label>&nbsp;</label>
		<button
			class="full-width button-secondary select-file"
			data-target=".upload-<?= f($name) ?>"
			data-q="*.*">
			Select
		</button>
	</div>
</div>