<?php include('templates/head.html.php'); ?>

<?= $article->body ?>

<p>
	<em><?= $article->date->format('l, F jS Y'); ?></em>
</p>

<?php include('templates/foot.html.php'); ?>
