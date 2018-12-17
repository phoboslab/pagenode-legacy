<?php

class CONFIG {
	const SITE_CACHE_ENABLED = false;
	const SITE_CACHE_MAX_AGE = 24 * 60 * 60;  // 24 hours

	const CHMOD = 0777;
	const CONTENT_PATH = 'content';
	const CACHE_PATH = 'content/cache/';
	const ASSETS_PATH = 'content/assets/';
	const NODES_PATH = 'content/nodes/';
	
	const SESSION_COOKIE_NAME = 'pn';
	const SESSION_COOKIE_SECURE = false;
	const SESSION_LIFETIME = 60 * 60 * 24 * 365; // 1 year
	const SESSION_SECRET = 'CJ2woMVxyYHcZ4PbKrYyXA6KoMFXrLqhKmoae4eYzTRxfv3Emcc7TVEZ98MyKC4B';
	const NONCE_SECRET = 'eupZEooiGtR3m6Q4NHt4NPvLgW8ei3tcY4rW3HYmPQTbEBL2NRUcQoPQuQ8GWGiq';

	const TIMEZONE = 'Europe/Berlin';
	const PASSWORD_MIN_LENGTH = 8;

	const FORMAT_DATETIME = 'M d, Y - H:i:s';
	const FORMAT_DATE = 'M d, Y';

	const THUMB_SIZE = 192;
	const JPEG_QUALITY = 90;
	const SYNTAX_HIGHLIGHT_LANGS = 'php|js|sql|c';

	const DEBUG = false;
	const JSON_OPTIONS = JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES;
	const ALLOWED_UPLOAD_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'pdf', 'zip'];
}

date_default_timezone_set(CONFIG::TIMEZONE);
