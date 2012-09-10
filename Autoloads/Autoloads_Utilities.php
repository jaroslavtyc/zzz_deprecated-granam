<?php
namespace granam;

class Autoloads_Utilities extends \granam\StaticMethodsOnly {

	private static $usedScripts = array();

	public static function requireScriptsFromDirectory($directory, $suffixes)
	{
		if (!file_exists($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] does not exists',
				Exception::CONTENT_VALUE | Exception::ACCESS_READING
			);
		}

		if (!is_dir($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] is not a directory at all',
				Exception::CONTENT_TYPE | Exception::ACCESS_READING
			);
		}

		$suffixesArray = (array)$suffixes;
		$realpathDirectory = realpath($directory);
		foreach (scandir($realpathDirectory) as $folder) {
			if (is_file($realpathDirectory . '/' . $folder)
			 && in_array(substr(strrchr($folder, '.'), 1), $suffixesArray) // suffix
			 // of found file compared with given suffix list
			) {
				self::requireScriptFile($realpathDirectory . '/' . $folder);
			}
		}
	}

	public static function requireOnceScriptsFromDirectory($directory, $suffixes)
	{
		if (!file_exists($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] does not exists',
				Exception::CONTENT_VALUE | Exception::ACCESS_READING
			);
		}

		if (!is_dir($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] is not a directory at all',
				Exception::CONTENT_TYPE | Exception::ACCESS_READING
			);
		}

		$suffixesArray = (array)$suffixes;
		$realpathDirectory = realpath($directory);
		foreach (scandir($realpathDirectory) as $folder) {
			if (is_file($realpathDirectory . '/' . $folder)
			 && in_array(substr(strrchr($folder, '.'), 1), $suffixesArray) // suffix
			 // of found file compared with given suffix list
			) {
				self::requireOnceScriptFile($realpathDirectory . '/' . $folder);
			}
		}
	}

	public static function requireRecursivelyScriptsFromDirectory(
		$directory,
		$suffixes
	) {
		if (!file_exists($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] does not exists',
				Exception::CONTENT_VALUE | Exception::ACCESS_READING
			);
		}

		if (!is_dir($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] is not a directory at all',
				Exception::CONTENT_TYPE | Exception::ACCESS_READING
			);
		}

		$suffixesArray = (array)$suffixes;
		$realpathDirectory = realpath($directory);
		foreach (scandir($realpathDirectory) as $folder) {
			if (is_dir($realpathDirectory . '/' . $folder)
			 && $folder != '.' && $folder != '..') {
				$methodName = __FUNCTION__;
				self::$methodName($realpathDirectory . '/' . $folder, $suffixes);
			} elseif (is_file($realpathDirectory . '/' . $folder)
			 && in_array(substr(strrchr($folder, '.'), 1), $suffixesArray) // suffix
			 // of found file compared with given suffix list
			) {
				self::requireScriptFile($realpathDirectory . '/' . $folder);
			}
		}
	}

	public static function requireOnceRecursivelyScriptsFromDirectory(
		$directory,
		$suffixes
	) {
		if (!file_exists($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] does not exists',
				Exception::CONTENT_VALUE | Exception::ACCESS_READING
			);
		}

		if (!is_dir($directory)) {
			throw new Exception(
				'Required directory [' . $directory . '] is not a directory at all',
				Exception::CONTENT_TYPE | Exception::ACCESS_READING
			);
		}

		$suffixesArray = (array)$suffixes;
		$realpathDirectory = realpath($directory);
		foreach (scandir($realpathDirectory) as $folder) {
			if (is_dir($realpathDirectory . '/' . $folder)
			 && $folder != '.' && $folder != '..'
			) {
				$methodName = __FUNCTION__;
				self::$methodName($realpathDirectory . '/' . $folder, $suffixes);
			} elseif (is_file($realpathDirectory . '/' . $folder)
			 && in_array(substr(strrchr($folder, '.'), 1), $suffixesArray) // suffix
			 // of found file compared with given suffix list
			) {
				self::requireOnceScriptFile($realpathDirectory . '/' . $folder);
			}
		}
	}

	public static function requireScriptFile($scriptFile)
	{
		if (!file_exists($scriptFile)) {
			throw new Exception(
				'Required script file [' . $scriptFile . '] does not exists',
				Exception::CONTENT_VALUE | Exception::ACCESS_READING
			);
		}

		if (!is_file($scriptFile)) {
			throw new Exception(
				'Required script file [' . $scriptFile . '] is not a file at all',
				Exception::CONTENT_TYPE | Exception::ACCESS_READING
			);
		}

		self::$usedScripts[$scriptFile] = require($scriptFile);

		return self::$usedScripts[$scriptFile];
	}

	public static function requireOnceScriptFile($scriptFile)
	{
		if (key_exists($scriptFile, self::$usedScripts)) {
			return self::$usedScripts[$scriptFile];
		}

		if (!file_exists($scriptFile)) {
			throw new Exception(
				'Required script file [' . $scriptFile . '] does not exists',
				Exception::CONTENT_VALUE | Exception::ACCESS_READING
			);
		}

		if (!is_file($scriptFile)) {
			throw new Exception(
				'Required script file [' . $scriptFile . '] is not a file at all',
				Exception::CONTENT_TYPE | Exception::ACCESS_READING
			);
		}

		self::$usedScripts[$scriptFile] = require_once($scriptFile);

		return self::$usedScripts[$scriptFile];
	}

	public static function includeScriptFile($scriptFile)
	{
		try {
			return self::requireScriptFile($scriptFile);
		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_WARNING);
		}
	}

	public static function includeScriptFileOnce($scriptFile)
	{
		try {
			return self::requireOnceScriptFile($scriptFile);
		} catch (\Exception $e) {
			trigger_error($e->getMessage(), E_USER_WARNING);
		}
	}
}