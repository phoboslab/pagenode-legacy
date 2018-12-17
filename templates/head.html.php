<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?= $article->title ?? 'Pagenode' ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width">
	<link rel="stylesheet" href="/templates/styles.css">
	<link rel="icon" type="image/png" href="/pagenode/media/pagenode-icon.png">
</head>
<body>
	<navigation>
		<?php foreach (Article::Newest(0, ['tags' => 'main-menu']) AS $m) { ?>
			<a href="/<?= $m->keyword ?>"><?= $m->title ?></a>
		<?php } ?>
	</navigation>

