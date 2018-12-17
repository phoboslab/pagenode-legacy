<?php $title = 'Error'; include(PN_TEMPLATES.'head.html.php') ?>

<h1><?= f($code); ?></h1>
<h2><?= f($status); ?></h2>
<?php if (!empty($message)) { ?>
	<p class="notice warn"><?= f($message); ?></p>
<?php } ?>

<?php include(PN_TEMPLATES.'foot.html.php') ?>
