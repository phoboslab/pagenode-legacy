<?php
require_once(PN_ROOT.'pagenode/config.php');
require_once(PN_ROOT.'pagenode/lib/parsedown.php');

class Format {
	public static function Text($s) {
		return nl2br(htmlspecialchars($s));
	}

	public static function DateTime($time) {
		return date(CONFIG::FORMAT_DATETIME, $time);
	}

	public static function Date($time) {
		return date(CONFIG::FORMAT_DATE, $time);
	}

	public static function TextWithURLs($s) {
		return self::UrlsToHtml(nl2br(htmlSpecialChars($s)));
	}

	public static function Shorten($value, $length = 80) {
		return (mb_strlen($value) > $length)
			? mb_substr($value, 0, $length - 1).'…'
			: $value;
	}

	public static function ShortenMid($value, $length = 80) {
		$shortened = mb_strlen($value) > $length
			? (mb_substr($value, 0, intval($length*0.33)) . '…' . mb_substr($value, intval(-$length*0.66)))
			: $value;
		return $shortened;
	}

	public static function TitleCase($s) {
		$s = implode(' ', preg_split('/(?=[A-Z])/', $s));
		$s = implode(' ', preg_split('/[\-\_]+/', $s));
		return ucwords($s);
	}
	
	public static function UrlsToHtml($s) {
		return preg_replace_callback('/\b(https?:\/\/(?:www\.)?|www\.)([^\s()<>]+(?:\([\w\d]+\)|([^,\.\(\)<>!?\s]|\/)))/', 
			function($m) {
				$url = $m[0];
				$httpwww = $m[1];
				$hostAndPath = $m[2];

				if ($httpwww === 'www.') {
					$url = 'http://' . $url;
				}
				return '<a target="_blank" href="'.$url.'">'.self::ShortenMid($hostAndPath).'</a>';
			}, $s);
	}

	public static function Pluralize($s) {
		switch ($s[strlen($s)-1]) {
			case 'y':
				return substr($s, 0, -1).'ies';
			case 's':
				return $s.'es';
			default:
				return $s.'s';
		}
	}

	public static function ResizeImage($path, $width, $height) {
		require_once(PN_ROOT.'pagenode/lib/image.php');

		if ($width === 0 && $height === 0) {
			return null;
		}

		$name = pathInfo($path)['filename'].'-'.$width.'x'.$height.'.jpg';
		$cacheFile = CONFIG::CACHE_PATH."resized/$name";
		$cacheFileFull = PN_ROOT.$cacheFile;

		if (
			file_exists($cacheFileFull) &&
			filemtime($cacheFileFull) > filemtime($path)
		) {
			return PN_ABS.$cacheFile;
		}

		if (!is_dir(dirname($cacheFileFull))) {
			if (!mkdir(dirname($cacheFileFull), CONFIG::CHMOD, true)) {
				return null;
			}
		}

		$image = new Image($path);
		if (!$image->valid) {
			return null;
		}

		if ($width === 0) {
			$width = $image->width * ($height / $image->height);
		}
		else if ($height === 0) {
			$height = $image->height * ($width / $image->width);
		}

		$success = $image
			->rotateToExifOrientation()
			->resize($width, $height)
			->sharpen()
			->writeJPEG($cacheFileFull, CONFIG::JPEG_QUALITY);

		$image->destroy();
		return $success ? PN_ABS.$cacheFile : null;
	}

	public static function SyntaxHighlight($s) {
		$s = htmlSpecialChars($s);
		$s = str_replace('\\\\','\\\\<e>', $s); // break escaped backslashes

		$tokens = [];
		$transforms = [
			// Insert helpers to find regexps
			'/
				([\[({=:+,]\s*)
					\/
				(?![\/\*])
			/x'
				=> '$1<h>/',

			// Extract Comments, Strings & Regexps, insert them into $tokens
			// and return the index
			'/(
				\/\*.*?\*\/|
				\/\/.*?\n|
				\#.*?\n|
				--.*?\n|
				(?<!\\\)&quot;.*?(?<!\\\)&quot;|
				(?<!\\\)\'(.*?)(?<!\\\)\'|
				(?<!\\\)<h>\/.+?(?<!\\\)\/\w*
			)/sx'
				=> function($m) use (&$tokens) {
					$id = '<r'.count($tokens).'>';
					$block = $m[1];

					if ($block{0} === '&' || $block{0} === "'") {
						$type = 'string';
					}
					else if ($block{0} === '<') {
						$type = 'regexp';
					}
					else {
						$type = 'comment';
					}
					$tokens[$id] = '<span class="'.$type.'">'.$block.'</span>';
					return $id;
				},

			// Punctuation
			'/((
				&\w+;|
				[-\/+*=?:.,;()\[\]{}|%^!]
			)+)/x'
				=> '<span class="punct">$1</span>',

			// Numbers (also look for Hex encoding)
			'/(?<!\w)(
				0x[\da-f]+|
				\d+
			)(?!\w)/ix'
				=> '<span class="number">$1</span>',

			// Make the bold assumption that an all uppercase word has a 
            // special meaning
            '/(?<!\w|>)(
                [A-Z_0-9]{2,}
            )(?!\w)/x'
          	  => '<span class="def">$1</span>',

			// Keywords
			'/(?<!\w|\$)(
				and|or|xor|not|for|do|while|foreach|as|endfor|endwhile|endforeach|
				break|continue|return|die|exit|if|then|else|elsif|elseif|endif|
				new|delete|try|throw|catch|finally|switch|case|default|goto|
				class|function|extends|this|self|parent|public|private|protected|
				published|friend|virtual|
				string|array|object|resource|var|let|bool|boolean|int|integer|float|
				double|real|char|short|long|const|static|global|enum|struct|typedef|
				signed|unsigned|union|extern|true|false|null|void
			)(?!\w|=")/ix'
				=> '<span class="keyword">$1</span>',

			// PHP-Style Vars: $var, $var->var
			'/(?<!\w)(
				\$(\-&gt;|\w)+
			)(?!\w)/ix'
				=> '<span class="var">$1</span>'
		];

		foreach ($transforms as $search => $replace) {
			$s = is_string($replace)
				? preg_replace($search, $replace, $s)
				: preg_replace_callback($search, $replace, $s);
		}

		// Paste the comments and strings back in again
		$s = strtr($s, $tokens);

		// Delete the escaped backslash breaker and replace tabs with 4 spaces
		$s = str_replace(['<e>', '<h>', "\t" ], ['', '', '    '], $s);

		return $s;
	}
}

class ParsedownPlusSyntaxHighlight extends Parsedown {
	protected function blockFencedCodeComplete($Block) {
		$class = $Block['element']['element']['attributes']['class'] ?? null;
		if (
			empty($class) || 
			!preg_match('/language-('.CONFIG::SYNTAX_HIGHLIGHT_LANGS.')/', $class)
		) {
			return $Block;
		}
		
		$text = $Block['element']['element']['text'];
		unset($Block['element']['element']['text']);
		$Block['element']['element']['rawHtml'] = Format::SyntaxHighlight($text);
		$Block['element']['element']['allowRawHtmlInSafeMode'] = true;
		return $Block;
	}
}

function f($value, ...$formats) {
	foreach ($formats as $format) {
		$value = Format::$format($value);
	}
	return htmlSpecialChars($value);
}


