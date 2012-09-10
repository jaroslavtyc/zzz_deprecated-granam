<?php
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300) {
	throw new Exception(
		'Version of PHP has to be 5.3 or newer. Current version is ' . phpversion()
	);
}

// ----- Basic initialization ------
require_once __DIR__ . '/basic_functions.php';
require_once __DIR__ . '/basic_constants.php';
require_once __DIR__ . '/basic_observances.php';
require_once __DIR__ . '/failures_handling.php';
require_once __DIR__ . '/autoloading.php';