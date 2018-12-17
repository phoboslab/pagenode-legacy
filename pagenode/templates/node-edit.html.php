<?php $title = $node->title->get(); include(PN_TEMPLATES.'head.html.php') ?>


<?php if ($mode === 'new') { ?>
	<h2>Create <?= f($type, 'TitleCase') ?></h2>
<?php } else if ($mode === 'edit') { ?>
	<h2><?= $node->title ?></h2>
	<div class="section">Keyword: <?= $node->keyword ?></div>
<?php } ?>


<?php if (isset($_GET['saved'])) { ?>
	<p class="notice ok temporary">Saved</p>
<?php } ?>

<form 
	method="post"
	<?php if ($mode === 'new') { ?>
		action="<?=PN_ABS?>admin/nodes/<?= f($typeName) ?>/new/save"
	<?php } else if ($mode === 'edit') { ?>
		action="<?=PN_ABS?>admin/nodes/<?= f($typeName) ?>/<?= f($node->keyword) ?>/save"
	<?php } ?>
>
	<input type="hidden" name="nonce" value="<?= f($user->nonce()) ?>"/>
	<input type="hidden" name="kw" value="<?= f($node->keyword) ?>"/>
	<div class="row">
		<div class="nine columns">
			<label>Title</label>
			<input name="title" value="<?= $node->title ?>" type="text" placeholder="Title" class="full-width"/>
		</div>
		<div class="three columns">
			<label>&nbsp;</label>
			<label class="check-label">
				<input <?php if($node->active) {?>checked<?php } ?> name="active" value="1" type="checkbox">
				Active
			</label>
		</div>
	</div>
	
	<div class="row">
		<div class="six columns">
			<label>Tags</label>
			<input
				placeholder="Separate by comma"
				name="tags" type="text" class="field text full-width" 
				value="<?= implode(', ', $node->tags) ?>"/>
		</div>
		<div class="three columns">
			<label>Date</label>
			<input name="date" value="<?= $node->date->format('Y-m-d') ?>" type="date" class="full-width"/>
		</div>
		<div class="three columns">
			<label>Time</label>
			<input 
				name="time" value="<?= $node->date->format('H:i') ?>"
				type="text" class="full-width"/>
		</div>
	</div>

	<?php foreach ($type::FIELDS as $name => $fieldClass) { ?>
		<?php $field = $node->$name; include($fieldClass::TEMPLATE) ?>
	<?php } ?>

	<div class="row">
		<div class="four columns">
	
			<?php if ($mode === 'new') { ?>
				<button 
					role="submit" class="primary full-width" 
					>Create <?= f($type, 'TitleCase') ?></button>
			<?php } else if ($mode === 'edit') { ?>
				<button
					role="submit" class="primary full-width"
					>Save <?= f($type, 'TitleCase') ?></button>
			<?php } ?>
		</div>
		<div class="four columns">
			
			<?php if ($mode === 'edit') { ?>
				<a 
					class="button-alternative"
					href="<?=PN_ABS?>admin/nodes/<?= f($typeName) ?>/<?= f($node->keyword) ?>/confirm-delete">
					Delete <?= f($type, 'TitleCase') ?>
				</a>
			<?php } ?>
		</div>
	</div>
</form>

<?php include(PN_TEMPLATES.'foot.html.php') ?>
