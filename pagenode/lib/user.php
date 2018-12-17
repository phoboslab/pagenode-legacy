<?php
require_once(PN_ROOT.'pagenode/config.php');
require_once(PN_ROOT.'pagenode/lib/node.php');

class SecureNode extends Node {
	const FILE_EXTENSION = '.php';
	const EXPOSED = false;

	public static function IsSequentialArray($a) {
		for ($k = 0, reset($a); key($a); next($a), $k++) {
			if ($k !== key($a)) {
				return false;
			}
		}
		return true;
	}

	public static function SerializeArray($a, $level = 0) {
		if (is_array($a)) {
			$parts = [];
			if (static::IsSequentialArray($a)) {
				foreach ($a as $v) {
					$parts[] = static::SerializeArray($v, $level + 1);
				}
				
			}
			else {
				foreach ($a as $k => $v) {
					$parts[] = 
						"\n".str_repeat("\t", $level).
						"'".addcslashes($k,"'\\")."' => ".
						static::SerializeArray($v, $level + 1);
				}
			}
			return "[".implode(",", $parts)."]";
		}
		else if (is_numeric($a)) {
			return $a + 0;
		}
		else if (is_string($a)) {
			return "'".addcslashes($a,"'\\")."'";
		}
		else if (is_bool($a)) {
			return $a ? 'true' : 'false';
		}
		else {
			return 'null';
		}
	}

	public static function LoadFile($path) {
		return require($path);
	}

	public static function SaveFile($path, $data) {
		file_put_contents($path, '<?php return '.static::SerializeArray($data).';', LOCK_EX);
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($path, true);
		}
	}
}


class Field_Select_UserTheme extends Field_Select {
	public static $Options = ['light', 'dark'];
}

class Field_Select_UserFont extends Field_Select {
	public static $Options = ['monospaced', 'proportional'];
}

class User extends SecureNode {
	const EXPOSED = true;
	const FIELDS = [
		'password' => Field_Password::class,
		'theme' => Field_Select_UserTheme::class,
		'font' => Field_Select_UserFont::class,
		'sessions' => Field_Raw::class
	];

	const E_PASSWORD_TOO_SHORT = 'passwordTooShort';
	
	public $session = null;
	
	public static function GetBySession() {
		if (empty($_COOKIE[CONFIG::SESSION_COOKIE_NAME])) {
			return null;
		}

		$parts = explode(':', $_COOKIE[CONFIG::SESSION_COOKIE_NAME]);
		if (count($parts) !== 2) {
			return null;
		}

		list($kw, $sessionId) = $parts;
		$node = static::One(['keyword' => $kw]);
		if (!$node) {
			return null;
		}

		$hashedSession = User::HashedSession($sessionId);
		$sessions = $node->sessions->get();

		if (empty($sessions[$hashedSession])) {
			setcookie(CONFIG::SESSION_COOKIE_NAME, false, time()-3600, '/');
			return null;
		}

		if (time() - $sessions[$hashedSession]['lastUsed'] > 3600) {
			$sessions[$hashedSession]['lastUsed'] = time();
			$node->sessions->set($sessions);
			$node->save();
		}
		
		$node->session = $sessionId;
		return $node;
	}	
	
	public static function GetByLogin($title, $password) {
		$kw = static::GetKeyword($title);
		$node = static::One(['keyword' => $kw]);

		if (empty($node) || !password_verify($password, $node->password)) {
			return null;
		}

		$sessions = $node->sessions->get();
		if (!is_array($sessions)) {
			$sessions = [];
		}

		// Delete all Sessions older than SESSION_LIFETIME
		$sessions = array_filter($sessions, function($s) {
			return (time() - $s['lastUsed'] < CONFIG::SESSION_LIFETIME);
		});

		// Create new session
		$sessionId = md5(random_bytes(64));
		$hashedSession = User::HashedSession($sessionId);
		$sessions[$hashedSession] = [
			'created' => time(), 'lastUsed' => time()
		];
		$node->sessions->set($sessions);
		$node->save();

		$node->session = $sessionId;
		return $node;

	}

	public static function Register($title, $password, $roles, &$error) {
		$error = null;
		if (strlen($password) < CONFIG::PASSWORD_MIN_LENGTH) {
			$error = static::E_PASSWORD_TOO_SHORT;
			return null;
		}

		$sessionId = md5(random_bytes(64));
		$hashedSession = User::HashedSession($sessionId);
		$node = User::Create($title, true, time(), $roles, [
			'password' => password_hash($password, PASSWORD_DEFAULT),
			'theme' => 'light',
			'font' => 'monospaced',
			'sessions' => [$hashedSession => [
				'created' => time(), 'lastUsed' => time()
			]]
		]);
		$node->save();

		$node->session = $sessionId;
		return $node;
	}

	public static function HashedSession($sessionId) {
		return md5(CONFIG::SESSION_SECRET.$sessionId);
	}

	public function setSessionCookie() {
		setcookie(
			CONFIG::SESSION_COOKIE_NAME, ($this->keyword.':'.$this->session), 
			time()+CONFIG::SESSION_LIFETIME, '/', '',
			CONFIG::SESSION_COOKIE_SECURE
		);
	}

	public function verifyNonce($nonce) {
		return ($this->nonce() === $nonce);
	}

	public function nonce() {
		return substr(md5(CONFIG::NONCE_SECRET.$this->session), 0, 16);
	}

	public function deleteSession() {
		$hashedSession = static::HashedSession($this->session);
		$sessions = $this->sessions->get();
		unset($sessions[$hashedSession]);
		$this->sessions->set($sessions);

		$this->save();
		$this->session = md5(random_bytes(64));
	}
	
	public function logout() {
		$this->deleteSession();
		setcookie(CONFIG::SESSION_COOKIE_NAME, false, time()-3600, '/');
	}
}
