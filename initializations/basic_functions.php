<?php
// list of directories to be searched for php scripts
$directoriesToLoad = array(
	__DIR__ . '/../extensions/developments/functions',
);

do {
	$directoryToLoad = current($directoriesToLoad);
	if (!empty($directoryToLoad)) {
		foreach (scandir($directoryToLoad) as $folder) {
			if (!preg_match('~^\.{1,2}$~', $folder)) { //not current
				// directory nor parent
				if (is_file($directoryToLoad . '/' . $folder)) {
					if (preg_match('~\.php$~', $folder)) {
						require_once($directoryToLoad . '/' . $folder);
					}
				} else {
					$directoriesToLoad[] = $directoryToLoad . '/' . $folder;
				}
			}
		}
	}
} while (next($directoriesToLoad));