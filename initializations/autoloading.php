<?php
// ----- Loading autoloader class ------
require_once GRANAM_ROOT_DIRECTORY .
	'/Autoloads/Autoloaders/Autoloaders_Utilities.php';
require_once
	GRANAM_ROOT_DIRECTORY . '/Autoloads/Autoloaders/Autoloader_Interface.php';
require_once
	GRANAM_ROOT_DIRECTORY . '/Autoloads/Autoloaders/AnglerAutoloader/AnglerAutoloader.php';
// ----- Registering autoloader ------
// \granam\Autoloaders_Utilities::register(\granam\AnglerAutoloader::getInstance());