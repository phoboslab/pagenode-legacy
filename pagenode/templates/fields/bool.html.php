<div class="row field">
	<label class="check-label">
		<input <?php if($field->get()) {?>checked<?php } ?>  name="data[<?= f($name) ?>]" value="1" type="checkbox">
		<?= f($name, 'TitleCase') ?>
	</label>
</div>