<div class="row field">
	<label><?= f($name, 'TitleCase') ?></label>
	<input
		name="data[<?= f($name) ?>]" type="number" 
		class="field number full-width" value="<?= f($field->get()) ?>"/>
</div>