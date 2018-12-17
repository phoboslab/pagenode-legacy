<!DOCTYPE html>
<html lang="en"
	class="<?= f($user->theme) ?> <?= f($user->font) ?>"
>
<head>
	<meta charset="utf-8">
	<title>Select File</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width">
	<link rel="stylesheet" href="<?=PN_ABS?>pagenode/media/pagenode.css">
	<link rel="icon" type="image/png" href="<?=PN_ABS?>pagenode/media/pagenode-icon.png">
</head>
<body class="overlay force-scroll">

	<div class="row">
		<form action="<?=PN_ABS?>admin/assets" method="GET" class="search-bar">
			<a href="<?=PN_ABS?>admin/assets" class="cancel">×</a>
				<input 
					type="text" name="q" placeholder=""
					value="<?= f($_GET['q'] ?? '') ?>"/>
			
				<button class="button primary" role="submit">Search</button>
		</form>
	</div>

	<?php if (empty($files)) { ?>
		<em>No assets found</em>
	<?php } ?>

	<div class="thumbs resize-items" data-item-max-width="<?= CONFIG::THUMB_SIZE ?>">
		<?php foreach ($files as $i => $f) { ?>
			<?php if ($f['thumb']) { ?>
				<img 
					class="action thumb post-message"
					data-type="select-path"
					data-param="<?= f($f['path']) ?>"
					title="<?= f($f['name']) ?> – <?= f($f['time'], 'date') ?>"
					src="<?= f($f['thumb']) ?>"/>
			<?php } else { ?>
				<div
					class="action thumb post-message"
					data-type="select-path"
					data-param="<?= f($f['path']) ?>"
					title="<?= f($f['name']) ?> – <?= f($f['time'], 'date') ?>"
				>
					<div class="file-icon">
						.<?= f(pathinfo($f['name'], PATHINFO_EXTENSION)) ?>
					</div>
					<div><?= f($f['name']) ?></div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>

	<script 
		data-path="<?=PN_ABS?>"
		type="text/javascript" src="<?=PN_ABS?>pagenode/media/pagenode.js"
		></script>
</body>
</html>
