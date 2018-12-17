<div class="row field">
	<label class="field"><?= f($name, 'TitleCase') ?></label>
	<textarea
		placeholder="Text"
		name="data[<?= f($name) ?>]" class="field longtext auto-height"
	><?= f($field->get()) ?></textarea>
</div>
