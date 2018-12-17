<?php $title = $node->title->get(); include(PN_TEMPLATES.'head.html.php') ?>

<h2>
	Delete “<?= $node->title ?>”?
</h2>

<form 
	method="post"
	action="<?=PN_ABS?>admin/nodes/<?= f($typeName) ?>/<?= f($node->keyword) ?>/delete"
>
	<input type="hidden" name="nonce" value="<?= f($user->nonce()) ?>"/>
	<p>
		This will permanently delete the <?= f($typeName, 'TitleCase') ?>
		“<em><?= $node->title ?></em>”. Are you sure you want to continue?
	</p>

	<div class="row">
		<div class="four columns">
			<button 
				role="submit" class="primary full-width" 
				>Delete <?= f($typeName, 'TitleCase') ?></button>
		</div>
		<div class="four columns">
			<a 
				class="button-alternative"
				href="<?=PN_ABS?>admin/nodes/<?= f($typeName) ?>/<?= f($node->keyword) ?>">
				Cancel
			</a>
		</div>
	</div>
</form>

<?php include(PN_TEMPLATES.'foot.html.php') ?>
