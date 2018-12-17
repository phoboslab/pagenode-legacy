<div class="row field">
	<label><?= f($name, 'TitleCase') ?></label>
	<textarea
		placeholder="Markdown"
		id="md" name="data[<?= f($name) ?>]" 
		class="field markdown auto-height"
	><?= f($field->get()) ?></textarea>
</div>
