<?php
require_once(PN_ROOT.'pagenode/config.php');
require_once(PN_ROOT.'pagenode/lib/format.php');
require_once(PN_ROOT.'pagenode/lib/parsedown.php');

abstract class Field {
	const TEMPLATE = null;
	public $value = null;

	public function __construct($value) {
		$this->value = $value;
	}

	public function get() {
		return $this->value;
	}

	public function set($value) {
		$this->value = $value;
	}

	public function attach($value) {
		$this->value = $value;
	}

	public function __toString() {
		return (string)$this->value;
	}
}


class Field_Bool extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/bool.html.php';

	public function attach($value) {
		$this->value = !!$value;
	}
}

class Field_Number extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/number.html.php';

	public function attach($value) {
		$this->value = floatval($value);
	}
}

class Field_Select extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/select.html.php';
	public static $Options = [];
	public function attach($value) {
		$this->value = in_array($value, static::$Options)
			? $value
			: reset(static::$Options);
	}

	public function __toString() {
		return htmlSpecialChars($this->value);
	}
}

class Field_Password extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/password.html.php';

	public function attach($value) {
		$this->value = !empty($value['new'])
			? password_hash($value['new'], PASSWORD_DEFAULT)
			: $value['current'];
	}

	public function __toString() {
		return htmlSpecialChars($this->value);
	}
}

class Field_Raw extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/raw.html.php';

	public function attach($value) {
		$this->value = json_decode($value, true);
	}
}

class Field_Text extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/text.html.php';

	public function __toString() {
		return htmlSpecialChars($this->value);
	}
}

class Field_LongText extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/longtext.html.php';

	public function __toString() {
		return Format::TextWithURLs($this->value);
	}
}

class Field_Image extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/image.html.php';

	public function __toString() {
		return htmlSpecialChars($this->value);
	}
}

class Field_DateTime extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/date.html.php';

	public function attach($value) {
		if (preg_match('/(\d{4})\-(\d{2})-(\d{2})( (\d{2}):(\d{2}))?/', $value, $r)) {
			$y = $r[1];
			$m = $r[2];
			$d = $r[3];
			$h = !empty($r[5]) ? $r[5] : 0;
			$i = !empty($r[6]) ? $r[5] : 0;
			$this->value = mktime($h, $i, 0, $m, $d, $y);
		}
	}

	public function format($format = CONFIG::FORMAT_DATETIME) {
		return htmlSpecialChars(date($format, $this->value));
	}
}

class Field_URL extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/url.html.php';

	public function __toString() {
		return htmlSpecialChars($this->value);
	}
}

class Field_Markdown extends Field {
	const TEMPLATE = PN_TEMPLATES.'fields/markdown.html.php';

	public function __toString() {
		return !empty(CONFIG::SYNTAX_HIGHLIGHT_LANGS)
			? ParsedownPlusSyntaxHighlight::instance()->text($this->value)
			: Parsedown::instance()->text($this->value);
	}
}
