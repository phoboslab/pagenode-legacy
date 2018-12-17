<?php

function getFirstTypePath() {
	return Node::GetSubClasses()[0]::PathName();
}

function clearCache() {
	$path = PN_ROOT.CONFIG::CACHE_PATH.'sites/';
	if (is_dir($path)) {
		array_map('unlink', glob($path.'*.html'));
	}
}

function requireType($typeName) {
	$type = Format::TitleCase($typeName);
	$type = str_replace(' ', '', $type);

	if (!is_subclass_of($type, 'Node')) {
		exitError(404, 'Not found', "No such type: $type");
	}
	return $type;
}

function requireNode($type, $keyword) {
	$node = $type::One(['status' => Node::STATUS_ANY, 'keyword' => $keyword]);

	if (!$node) {
		exitError(404, 'Not found', "No such node: $keyword");
	}
	return $node;
}

function requireUser() {
	$user = User::getBySession();
		
	if (!$user) {
		redirect('/admin/account/login');
	}
	$user->setSessionCookie();
	return $user;
}

function requireAdminUser() {
	$user = requireUser();
	if (!$user->hasTag('admin')) {
		exitError(403, 'Forbidden', 'Requires Admin Access');
	}
	return $user;
}

function verifyNonce($user) {
	if (empty($_REQUEST['nonce']) || $user->nonce() !== $_REQUEST['nonce']) {
		exitError(403, 'Invalid Nonce');	
	}
}

function verifyPost($user) {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		exitError(405, 'Method Not Allowed');
	}
	verifyNonce($user);
}

function exitError($code, $status, $message = '') {
	header("HTTP/1.1 $code $status");
	include(PN_TEMPLATES.'error.html.php');
	exit();
}

function processUpload($file, &$error) {
	$info = pathInfo($file['name']);
	$path = CONFIG::ASSETS_PATH.$info['basename'];

	if (!in_array(strtolower($info['extension']), CONFIG::ALLOWED_UPLOAD_TYPES)) {
		$error = 'Type is not allowed';
		return null;
	}

	if (!is_dir(PN_ROOT.CONFIG::ASSETS_PATH)) {
		if (!mkdir(PN_ROOT.CONFIG::ASSETS_PATH, CONFIG::CHMOD, true)) {
			$error = 'Could not create asset directory';
			return null;
		}
	}

	for ($i = 2; file_exists(PN_ROOT.$path); $i++) {
		$path = CONFIG::ASSETS_PATH.$info['filename'].'-'.$i.'.'.$info['extension'];
	}

	if (!move_uploaded_file($file['tmp_name'], PN_ROOT.$path)) {
		$error = 'Could not write file';
		return null;
	}
	return PN_ABS.$path;
}



// -----------------------------------------------------------------------------
// Nodes

route('/admin/?(nodes)?', function() {
	redirect(PN_ABS.'admin/nodes/'.getFirstTypePath());
});

route('/admin/nodes/{type}', function($typeName){
	$user = requireAdminUser();
	$type = requireType($typeName);

	$params = ['status' => $type::STATUS_ANY];
	if (!empty($_GET['q'])) {
		if (!empty($_GET['tags'])) {
			$params['tags'] = $_GET['q'];
		}
		else {
			$params['titleHas'] = $_GET['q'];
		}
	}
	$nodes = $type::Newest(0, $params);

	include(PN_TEMPLATES.'node-list.html.php');
});


route('/admin/nodes/{type}/new', function($typeName) {
	$mode = 'new';
	$user = requireAdminUser();
	$type = requireType($typeName);
	$node = $type::Create('Untitled');

	include(PN_TEMPLATES.'node-edit.html.php');
});

route('/admin/nodes/{type}/{keyword}', function($typeName, $keyword) {
	$mode = 'edit';
	$user = requireAdminUser();
	$type = requireType($typeName);
	$node = requireNode($type, $keyword);

	include(PN_TEMPLATES.'node-edit.html.php');
});

route('/admin/nodes/{type}/{keyword}/save', function($typeName, $keyword) {
	$user = requireAdminUser();
	$type = requireType($typeName);
	verifyPost($user);

	$node = $keyword === 'new'
		? $type::Create($_POST['title'])
		: requireNode($type, $keyword);
	
	$node->active = !!($_POST['active'] ?? false);

	$node->title->attach($_POST['title']);
	$node->date->attach($_POST['date'].' '.$_POST['time']);
	$node->attachTags($_POST['tags']);
	$node->attachData($_POST['data']);

	$node->save();

	clearCache();
	redirect(PN_ABS.'admin/nodes/'.$typeName.'/'.$node->keyword, ['saved' => true]);
});


route('/admin/nodes/{type}/{keyword}/confirm-delete', function($typeName, $keyword) {
	$user = requireAdminUser();
	$type = requireType($typeName);
	$node = requireNode($type, $keyword);

	include(PN_TEMPLATES.'node-delete.html.php');
});

route('/admin/nodes/{type}/{keyword}/delete', function($typeName, $keyword) {
	$user = requireAdminUser();
	$type = requireType($typeName);
	verifyPost($user);

	$node = requireNode($type, $keyword);
	$node->delete();

	clearCache();
	redirect(PN_ABS.'admin/nodes/'.$typeName);
});


// -----------------------------------------------------------------------------
// Assets

route('/admin/assets', function() {
	$user = requireAdminUser();
	$pattern = !empty($_GET['q']) ? '*'.$_GET['q'].'*' : '*.*';
	$globPath = PN_ROOT.CONFIG::ASSETS_PATH;

	$files = [];
	foreach (glob($globPath.$pattern, GLOB_BRACE) as $f) {
		$name = basename($f);
		$thumb = preg_match('/png|jpe?g|gif/', $name)
			? Format::ResizeImage($f, CONFIG::THUMB_SIZE, CONFIG::THUMB_SIZE)
			: null;

		$files[] = [
			'path' => PN_ABS.CONFIG::ASSETS_PATH.$name,
			'thumb' => $thumb,
			'name' => $name,
			'time' => filemtime($f)
		];
	}

	usort($files, function($a, $b) {
		return $b['time'] <=> $a['time'];
	});

	include(PN_TEMPLATES.'assets.html.php');
});

route('/admin/assets/upload', function() {
	$user = requireAdminUser();
	$path = processUpload($_FILES['file'], $error);

	header('Content-type: application/json');
	echo json_encode([
		'success' => empty($error),
		'error' => $error, 
		'url' => $path
	]);
	exit;
});


// -----------------------------------------------------------------------------
// Account

route('/admin/account/login', function() {
	if (!User::One()) {
		redirect(PN_ABS.'admin/account/create');
	}

	if (User::GetBySession()) {
		redirect(PN_ABS.'admin/nodes/'.getFirstTypePath());
	}

	$error = null;
	if (!empty($_POST['email']) && !empty($_POST['password'])) {
		$user = User::GetByLogin($_POST['email'], $_POST['password']);
		if ($user) {
			$user->setSessionCookie();
			redirect(PN_ABS.'admin/nodes/'.getFirstTypePath());
		}
		else {
			$error = 'invalidLogin';
		}
	}
	include(PN_TEMPLATES.'account-login.html.php');
});

route('/admin/account/create', function() {
	if (!is_writable(PN_ROOT.CONFIG::CONTENT_PATH)) {
		exitError(
			500, 'Installation Error', 
			'The '.CONFIG::CONTENT_PATH.'/ directory is not writeable.'
		);
	}

	if (User::One()) {
		redirect(PN_ABS.'admin/account/login');
	}

	$error = null;
	if (!empty($_POST['email']) && !empty($_POST['password'])) {
		$user = User::Register($_POST['email'], $_POST['password'], ['admin'], $error);
		if ($user) {
			$user->setSessionCookie();
			redirect(PN_ABS.'admin/nodes/'.getFirstTypePath());
		}
	}
	include(PN_TEMPLATES.'account-create.html.php');
});

route('/admin/account/logout', null, function($p) {
	$user = requireAdminUser();
	verifyNonce($user);
	$user->logout();
	redirect(PN_ABS.'admin/account/login');
});

route('/admin/*', function(){
	exitError(404, 'Not found');
});
