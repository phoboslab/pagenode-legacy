<div class="row field">
	<label><?= f($name, 'TitleCase') ?></label>
	<input name="data[<?= f($name) ?>]" type="text" class="field text full-width" value="<?= f($field->get()) ?>"/>
</div>