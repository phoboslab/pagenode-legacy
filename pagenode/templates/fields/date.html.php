<div class="row field">
	<div class="six columns">
		<label><?= f($name, 'TitleCase') ?></label>
		<input
			name="data[<?= f($name) ?>][date]" value="<?= $field->date('Y-m-d') ?>"
			type="date" class="full-width"/>
	</div>
	<div class="six columns">
		<label>&nbsp;</label>
		<input 
			name="data[<?= f($name) ?>][time]" value="<?= $field->date('H:i') ?>"
			type="text" class="full-width"/>
	</div>
</div>
