<div class="row field">
	<label><?= f($name, 'TitleCase') ?></label>
	<select name="data[<?= f($name) ?>]" class="full-width">
		<?php foreach ($field::$Options as $o) { ?>
			<option 
				<?php if ($o === $field->get()) {?>selected<?php } ?>
				value="<?= f($o) ?>"
			><?= f($o) ?></option>
		<?php } ?>
	</select>
</div>