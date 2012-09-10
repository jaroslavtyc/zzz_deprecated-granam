<?php
namespace granam;

require_once(__DIR__ . '/../Observances/Objects/StaticMethodsOnly.php');

class Folders_PathUtilities extends StaticMethodsOnly {

	const DEFAULT_DIRECTORY_SEPARATOR = '/';

	public static function getSystemRealPathOfDir($dirPath)
	{
		if (!is_dir($dirPath)){//if given name is realy directory
			throw new RuntimeException("Directory of name '$dirPath' was not found", E_USER_WARNING);
		}
		if (!is_readable($dirPath)) {
			throw new RuntimeException("Directory '$dirPath' can not be readed", E_USER_WARNING);
		}
		$originalCwd = getcwd();
		if (!chdir($dirPath)) {//using directory as working place to get standarized name in next step
			throw new RuntimeException("Directory of name '$dirPath' can not be entered", E_USER_WARNING);
		}
		$systemRealPath = getcwd();
		if (!chdir($originalCwd)) {//returning previus cwd back
			throw new RuntimeException("Original working directory of name '$dirPath' could not be entered", E_USER_WARNING);
		}

		return $systemRealPath;
	}

	/**
	* Replaces non-default directory separators by default directory separator
	*
	* @param string $path path to standarize
	* @return string standarized path
	*/
	public static function makeStandarizedPath($path)
	{
		if (file_exists($path)) {
			$path = realpath($path);
		}

		$otherDirectorySeparators = array (
			'\\',
		);

		return str_replace(
			$otherDirectorySeparators,
			self::DEFAULT_DIRECTORY_SEPARATOR,
			realpath($path)
		);
	}

	/**
	 * Returns list of subdirectories of given directory
	 *
	 * @param string $directory full path to directory to scan for subdirectories
	 * @param string $returnWithFullPath
	 * @return array list of found subdirectories
	 */
	public static function getSubdirectories(
		$dirPath,
		$subdirectoriesWithFullPath = FALSE,
		$recursive = FALSE
	){
		$subdirs = self::getDirsFromDir($dirPath, FALSE, FALSE);
		if ($returnWithFullPath) {
			if ($subdirs !== FALSE) {
				foreach($subdirs as &$subdir){
					$subdir = self::makeStandarizedDirpath(self::makeStandarizedDirpath($dirPath) . $subdir);
				}
				if ($recursive) {
					$subsubdirs = array();
					foreach($subdirs as &$subdir){
						$subsubdirs = array_merge($subsubdirs, self::getSubdirectories($subdir, $returnWithFullPath, $recursive));
					}
					$subdirs = array_merge($subdirs, $subsubdirs);
				}
			}
		} elseif ($recursive) {
			throw new Exception('List of recursively readed subdirectories has to contain full path of directories, otherways does not have sense', E_USER_WARNING);
		}

		return $subdirs;
	}
}