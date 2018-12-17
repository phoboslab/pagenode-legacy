<?php
require_once(PN_ROOT.'pagenode/lib/node.php');

class Router {
	public static $Routes = [];

	public static function AddRoute($path, $resolver) {
		$r = str_replace('/', '\\/', $path);
		$r = str_replace('*', '.*?', $r);
		$r = preg_replace('/{(\w+)}/', '(?<$1>[^\\/]+)', $r);
		$regexp = '/^'.$r.'$/';

		self::$Routes[$path] = [
			'regexp' => $regexp,
			'resolver' => $resolver
		];
	}

	public static function Dispatch($request) {
		foreach (self::$Routes as $path => $r) {
			if (preg_match($r['regexp'], $request, $m)) {
				$found = self::Resolve($r['resolver'], $m);
				return ($found && $path !== '/*');
			}
		}
		return self::ErrorNotFound();
	}

	public static function Resolve($resolver, $regexpMatch, $resolveNotFound = true) {
		$params = array_filter($regexpMatch, function($key) {
			return !is_int($key); 
		}, ARRAY_FILTER_USE_KEY);

		if (call_user_func_array($resolver, $params) !== false) {
			return true;
		};
		
		return self::ErrorNotFound($resolveNotFound);
	}

	public static function ErrorNotFound($resolveNotFound = true) {
		if ($resolveNotFound && !empty(self::$Routes['/*'])) {
			self::Resolve(self::$Routes['/*']['resolver'], [], false);
		}
		else {
			header("HTTP/1.1 404 Not Found");
			echo "Not Found";
		}
		return false;
	}
}

function route($path, $resolver = null) {
	Router::AddRoute($path, $resolver);
}

function reRoute($request) {
	Router::Dispatch($request);	
}

function redirect($path = '/', $params = []) {
	$query = !empty($params) 
		? '?'.http_build_query($params) 
		: '';
	header('Location: '.$path.$query);
	exit();
}
