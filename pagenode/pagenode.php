<?php
$timeStart = microtime(true);

header('Content-type: text/html');
define('PN_ROOT', realpath(__DIR__ . '/../').'/');
define('PN_TEMPLATES', PN_ROOT.'pagenode/templates/');
define('PN_ABS', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/').'/');

require_once(PN_ROOT.'pagenode/config.php');
require_once(PN_ROOT.'pagenode/lib/router.php');
require_once(PN_ROOT.'pagenode/lib/node.php');
require_once(PN_ROOT.'pagenode/lib/user.php');
require_once(PN_ROOT.'pagenode/admin.php');

function dispatch($request = null) {
	if ($request === null) {
		$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$request = '/'.substr($request, strlen(PN_ABS));
	}

	$cacheEnabled = 
		CONFIG::SITE_CACHE_ENABLED && 
		!preg_match('#^/admin(/|$)#', $request) &&
		$_SERVER['REQUEST_METHOD'] !== 'POST';

	if ($cacheEnabled) {
		$cacheKey = md5($request);
		$cachePath = PN_ROOT.CONFIG::CACHE_PATH.'sites/';
		$cacheFile = $cachePath.$cacheKey.'.html';

		if (file_exists($cacheFile)) {
			if ((time() - filemtime($cacheFile)) < CONFIG::SITE_CACHE_MAX_AGE) {
				header('X-PN-Cache-Hit: true');
				include($cacheFile);
				if (CONFIG::DEBUG) {
					printDebugInfo();
				}
				return;
			}
		}

		ob_start();
	}

	$found = Router::Dispatch($request);

	if ($cacheEnabled && $found) {
		$html = ob_get_contents();
			
		if (!is_dir($cachePath)) {
			mkdir($cachePath, CONFIG::CHMOD, true);
		}
		file_put_contents($cacheFile, $html);
	}
	
	ob_end_flush();
	if (CONFIG::DEBUG) {
		printDebugInfo();
	}
}

function printDebugInfo() {
	global $timeStart;
	echo
		"<pre>\n".
			"Runtime: ".round((microtime(true) - $timeStart)*1000, 3)." ms\n".
			"NodeSelector Queries: ".f(print_r(Node::$Queries, true))."\n".
			"Opened Nodes: ".f(print_r(Node::$OpenedNodes, true))."\n".
			"Get: ".f(print_r($_GET, true))."\n".
			"Post: ".f(print_r($_POST, true))."\n".
			"Cookie: ".f(print_r($_COOKIE, true))."\n".
		"</pre>";
}
