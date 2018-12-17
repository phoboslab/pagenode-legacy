<!DOCTYPE html>
<html lang="en"
	<?php if (!empty($user)) { ?>
		class="<?= f($user->theme) ?> <?= f($user->font) ?>"
	<?php } ?>
>
<head>
	<meta charset="utf-8">
	<title>
		<?php if (!empty($title)) { ?><?= f($title) ?> – <?php } ?>
		Pagenode Admin
	</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width">
	<link rel="stylesheet" href="<?=PN_ABS?>pagenode/media/pagenode.css">
	<link rel="icon" type="image/png" href="<?=PN_ABS?>pagenode/media/pagenode-icon.png">
</head>
<body>

	<div class="container">

		<div class="row navigation">
			<?php if (!empty($user)) { ?>
				<?php foreach (Node::GetSubClasses() as $class) { ?>
					<a
						href="<?=PN_ABS?>admin/nodes/<?= f($class::PathName()) ?>" 
						class="tab<?php if (($type ?? '') === $class) {?> active<?php } ?>"
					><?= f($class, 'TitleCase', 'Pluralize') ?></a>
				<?php } ?>
				
				<span class="aside">
					<a class="logout-button"
						href="<?=PN_ABS?>admin/account/logout?nonce=<?= f($user->nonce()) ?>"
					>
						Logout 
						<span class="mw-960">(<?= f($user->title) ?>)</span>
					</a>
				</span>
			<?php } ?>
		</div>