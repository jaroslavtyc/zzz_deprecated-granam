<?php
if (!defined('DOCUMENT_ROOT')) { // document root directory can be already
// defined; because of crone for example
	if (!empty($_SERVER['DOCUMENT_ROOT'])) {
		define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
	} else {
		define(
			'DOCUMENT_ROOT',
			realpath(__DIR__ . '/../..')
		);
	}
}

if (defined('GRANAM_ROOT_DIRECTORY')) {
	if (GRANAM_ROOT_DIRECTORY === realpath(__DIR__ . '/..')) {
		trigger_error( // repeated definning of this constant is failure
			'GRANAM_ROOT_DIRECTORY is already defined',
			E_USER_WARNING
		);
	} else {
		throw new Exception(
			'GRANAM_ROOT_DIRECTORY should not be defined alternative way.'
		);
	}
} else {
	define(
		'GRANAM_ROOT_DIRECTORY',
		realpath(__DIR__ . '/..')
	);
}