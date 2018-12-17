<?php
require_once(PN_ROOT.'pagenode/config.php');
require_once(PN_ROOT.'pagenode/lib/field.php');

class Node {
	const STATUS_INACTIVE = false;
	const STATUS_ACTIVE = true;
	const STATUS_ANY = 2;

	const SORT_DATE_DESC = 'desc';
	const SORT_DATE_ASC = 'asc';
	const SORT_ALPHABETICAL = 'alpha';

	const FILE_EXTENSION = '.json';
	const EXPOSED = true;

	public static $Queries = [];
	protected static $FoundNodes = 0;

	public static $NodeTypes = [];
	public static $FieldTypes = [];
	public static $IndexCache = [];
	public static $OpenedNodes = [];

	public static function PathName() {
		return strtolower(preg_replace('/(.)([A-Z])/','$1-$2', static::class));
	}
	
	public static function Path() {
		return PN_ROOT.CONFIG::NODES_PATH.static::PathName().'/';
	}

	protected static function LoadFile($path) {
		return json_decode(file_get_contents($path), true);
	}

	protected static function SaveFile($path, $data) {
		file_put_contents($path, json_encode($data, CONFIG::JSON_OPTIONS), LOCK_EX);
	}

	protected static function RebuildIndex() {
		$path = static::Path();
		$index = [];

		if (!is_dir($path)) {
			mkdir($path, CONFIG::CHMOD, true);
		}
		$files = glob($path.'*'.static::FILE_EXTENSION);
		$nodes = [];

		foreach ($files as $f) {
			if (basename($f) !== '~index'.static::FILE_EXTENSION) {
				$nodes[] = static::LoadFile($f);
			}
		}

		foreach ($nodes as $n) {			
			$index[$n['keyword']] = [
				'title' => $n['title'],
				'date' => $n['date'],
				'active' => $n['active'],
				'tags' => $n['tags']
			];
		}
		static::StoreIndex($index, true);
		return $index;
	}

	protected static function GetIndex() {
		$path = static::Path();

		if (!isset(static::$IndexCache[$path])) {
			$file = $path.'~index'.static::FILE_EXTENSION;
			if (file_exists($file)) {
				static::$IndexCache[$path] = static::LoadFile($file);
			}
			else {
				static::RebuildIndex();
			}
		}
		
		return static::$IndexCache[$path] ?? [];
	}

	protected static function StoreIndex($index, $needsSorting = false) {
		$path = static::Path();
		if ($needsSorting) {
			uasort($index, function ($a, $b) {
				return $b['date'] <=> $a['date'];
			});
		}
		static::$IndexCache[$path] = $index;
		static::SaveFile($path.'~index'.static::FILE_EXTENSION, $index);
	}

	protected static function GetKeyword($title) {
		$kw = str_replace("'", '', $title);
		$kw = preg_replace('/[^a-zA-Z\d]+/', '-', $kw);
		$kw = trim($kw, '-');
		$kw = strToLower($kw);

		return empty($kw) ? 'untitled' : $kw;
	}

	protected static function GetUniqueKeyword($title) {
		$kw = static::GetKeyword($title);
		$nodes = static::GetIndex();
		$taken = ['new'];
		foreach ($nodes as $nodeKeyword => $unused) {
			if (stripos($nodeKeyword, $kw) === 0) {
				$taken[] = $nodeKeyword;	
			}
		}
		
		$uniqueKw = $kw;
		for ($i = 2; in_array($uniqueKw, $taken); $i++) {
			$uniqueKw = $kw.'-'.$i;
		}
		return $uniqueKw;
	}

	public static function GetSubClasses() {
		$classes = array_filter(get_declared_classes(), function($name){
			return is_subclass_of($name, static::class) && $name::EXPOSED;
		});
		usort($classes, function($a, $b){ return $a === 'User'; });

		return $classes;
	}

	public static function Create(
		$title, $active = true,
		$timestamp = null, $tags = [], $data = []
	) {
		if ($timestamp === null) {
			$timestamp = time();
		}

		$keyword = static::GetUniqueKeyword($title);
		$node = new static($keyword, $title, !!$active, $timestamp, $tags);
		$node->setData($data);
		$node->hasFile = false;
		return $node;
	}

	protected static function FromFile($keyword) {
		$data = static::LoadFile(static::Path().$keyword.static::FILE_EXTENSION);
		$node = new static(
			$keyword, $data['title'], $data['active'], 
			$data['date'], $data['tags']
		);
		$node->data = $data;
		return $node;
	}

	protected static function FromIndex($keyword, $index) {
		$node = new static(
			$keyword, $index['title'], $index['active'],
			$index['date'], $index['tags']
		);
		return $node;
	}

	protected static function Select($sort, $count, $params) {
		if (CONFIG::DEBUG) {
			$timeStart = microtime(true);
		}

		$index = static::GetIndex();
		$scannedNodes = count($index);

		if (isset($params['keyword'])) {
			$index = isset($index[$params['keyword']])
				? [$params['keyword'] => $index[$params['keyword']]]
				: [];
		}

		if (isset($params['date'])) {
			$y = $params['date'][0];
			$m = $params['date'][1] ?? null;
			$d = $params['date'][2] ?? null;
			if (preg_match('/(\d{4}).(\d{2}).(\d{2})/', $y, $match)) {
				$y = $match[1];
				$m = $match[2];
				$d = $match[3];
			}
			$start = mktime(0, 0, 0, ($m ? $m : 1), ($d ? $d : 1), $y);
			$end = mktime(23, 59, 59, ($m ? $m : 12),  ($d ? $d : 31), $y);

			$index = array_filter($index, function($n) use ($start, $end) {
				return $n['date'] >= $start && $n['date'] <= $end;
			});
		}

		if (isset($params['tags'])) {
			$tags = array_map('trim', explode(',', $params['tags']));
			$index = array_filter($index, function($n) use ($tags) {
				return !array_diff($tags, $n['tags']);
			});
		}

		if (isset($params['titleHas'])) {
			$q = $params['titleHas'];
			$index = array_filter($index, function($n) use ($q) {
				return stripos($n['title'], $q) !== false;
			});
		}

		$status = $params['status'] ?? static::STATUS_ACTIVE;
		if ($status !== static::STATUS_ANY) {
			$index = array_filter($index, function($n) use ($status) {
				return $n['active'] === $status;
			});
		}

		if ($sort === static::SORT_DATE_DESC) {
			// Nothing to do here; index is sorted by SORT_DATE_DESC by default
		}
		else if ($sort === static::SORT_DATE_ASC) {
			$index = array_reverse($index);
		}
		else if ($sort === static::SORT_ALPHABETICAL) {
			ksort($index);
		}
		
		static::$FoundNodes = count($index);
		
		if ($count && count($index) > 1) {
			$offset = ($params['page'] ?? 0) * $count;			
			$index = array_slice($index, $offset, $count, true);
		}


		$nodes = [];
		foreach ($index as $keyword => $indexData) {
			$nodes[] = static::FromIndex($keyword, $indexData);
		}

		if (CONFIG::DEBUG) {
			static::$Queries[] = [
				'ms' => round((microtime(true) - $timeStart)*1000, 3),
				'scanned' => $scannedNodes,
				'returned' => count($nodes),
				'type' =>static::class,
				'params' => $params
			];
		}

		return $nodes;
	}

	public static function FoundNodes() {
		return static::$FoundNodes;
	}

	public static function One($params = []) {
		$nodes = static::Select(static::SORT_DATE_DESC, 1, $params);
		return !empty($nodes) ? $nodes[0] : null;
	}

	public static function Newest($count = 0, $params = []) {
		return static::Select(static::SORT_DATE_DESC, $count, $params);
	}

	public static function Oldest($count = 0, $params = []) {
		return static::Select(static::SORT_DATE_ASC, $count, $params);
	}

	public static function Alphabetical($count = 0, $params = []) {
		return static::Select(static::SORT_ALPHABETICAL, $count, $params);
	}




	public $keyword, $active, $title, $tags = [];
	protected $hasFile = true, $hasData = false, $originalTitle;
	protected $fields = [];

	protected function __construct($keyword, $title, $active, $timestamp, $tags) {
		$this->keyword = $keyword;
		$this->tags = $tags;
		$this->active = $active;
		$this->originalTitle = $title;
		$this->title = new Field_Text($title);
		$this->date = new Field_DateTime($timestamp);
	}

	protected function loadData() {
		if (!$this->hasFile || $this->hasData) {
			return;
		}

		$this->hasData = true;

		$file = static::Path().$this->keyword.static::FILE_EXTENSION;
		$data = static::LoadFile($file);
		foreach (static::FIELDS as $name => $className) {
			$this->fields[$name] = new $className($data[$name] ?? null);
		}

		if (CONFIG::DEBUG) {
			static::$OpenedNodes[] = $this->keyword;
		}
	}

	public function setData($data) {
		$this->hasData = true;

		foreach (static::FIELDS as $name => $className) {
			$this->fields[$name] = new $className($data[$name] ?? null);
		}
	}

	public function attachData($data) {
		$this->hasData = true;

		foreach (static::FIELDS as $name => $className) {
			$this->fields[$name] = new $className(null);
			$this->fields[$name]->attach($data[$name] ?? null);
		}
	}

	public function setTags($tags) {
		$this->tags = $tags;
	}

	public function attachTags($value) {
		$tags = !empty($value) 
			? array_map('self::GetKeyword', explode(',', $value)) 
			: [];
		$this->setTags($tags);
	}

	public function hasTag($tag) {
		return in_array($tag, $this->tags);
	}

	public function &__get($name) {
		$this->loadData();

		$result = null;
		if (isset($this->fields[$name])) {
			$result =& $this->fields[$name];
		}

		return $result;
	}
	
	public function save() {
		$this->loadData();

		if ($this->title->get() !== $this->originalTitle) {
			$this->delete();
			$this->keyword = static::GetUniqueKeyword($this->title->get());
		}

		$this->insert();
	}

	public function delete() {
		if (!$this->hasFile) {
			return;
		}

		$index = static::GetIndex();
		unset($index[$this->keyword]);
		static::StoreIndex($index);

		$file = static::Path().$this->keyword.static::FILE_EXTENSION;
		unlink($file);
	}

	protected function insert() {
		$fieldData = [];
		foreach ($this->fields as $name => $field) {
			$fieldData[$name] = $field->get();
		}
		
		$data = array_merge($fieldData, [
			'title' => $this->title->get(),
			'active' => !!$this->active,
			'date' => $this->date->get(),
			'tags' => $this->tags,
			'keyword' => $this->keyword
		]);

		$file = static::Path().$this->keyword.static::FILE_EXTENSION;
		static::SaveFile($file, $data);

		$index = static::GetIndex();
		$index[$this->keyword] = [
			'title' => $this->title->get(),
			'date' => $this->date->get(),
			'active' => !!$this->active,
			'tags' => $this->tags
		];
		static::StoreIndex($index, true);

		$this->hasFile = true;
	}
}

