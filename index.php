<?php
require_once('pagenode/pagenode.php');

class Article extends Node {
	const FIELDS = [
		'body' => Field_Markdown::class
	];
}

route('/', function(){ reRoute('/welcome'); });

route('/{keyword}', function($keyword) {
	$article = Article::One(['keyword' => $keyword]);
	if (!$article) {
		return false;
	}

	include('templates/article.html.php');
});


route('/*', function(){
	include('templates/404.html.php');
});

dispatch();
