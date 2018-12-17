<div class="row field">
	<div class="six columns">
		<label><?= f($name, 'TitleCase') ?></label>

		<div class="row">
			<div class="eight columns">
				<input 
					placeholder="URL"
					type="text" class="full-width upload-<?= f($name) ?>" name="data[<?= f($name) ?>]" 
					value="<?= f($field->get()) ?>"/>
			</div>
			<div class="four columns">
				<button
					class="full-width button-secondary select-file"
					data-target=".upload-<?= f($name) ?>"
					data-q="*.{jpg,jpeg,png,gif}">
					Select
				</button>
			</div>
		</div>

		<button
			class="full-width button-secondary drop-upload"
			data-target=".upload-<?= f($name) ?>"
			data-q="*.{jpg,jpeg,png,gif}">
			Click or drop here to Upload
		</button>
	</div>	
	<div class="six columns">
		<label>&nbsp;</label>
		<div class="form-image-column">
			<img
				alt=""
				<?php if (!empty($field->get())) { ?>
					src="<?= f($field->get()) ?>"
				<?php } else { ?>
					width="0" height="0"
					src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
				<?php } ?>
				class="form-image upload-<?= f($name) ?>"
				/>
			</div>
	</div>
</div>
