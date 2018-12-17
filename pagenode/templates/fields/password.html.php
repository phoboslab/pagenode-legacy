<div class="row field">
	<label><?= f($name, 'TitleCase') ?></label>
	<input 
		name="data[<?= f($name) ?>][new]" 
		type="password" class="field text full-width auto-clear" 
		value="" autocomplete="new-password"/>
	<input name="data[<?= f($name) ?>][current]" type="hidden" value="<?= f($field->get()) ?>"/>
</div>