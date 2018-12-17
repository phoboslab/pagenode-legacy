<div class="row field">
	<label class="field"><?= f($name, 'TitleCase') ?></label>
	<textarea
		placeholder="JSON"
		name="data[<?= f($name) ?>]" class="field longtext auto-height"
		><?= f(json_encode($field->get(), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></textarea>
</div>
