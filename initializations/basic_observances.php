<?php
if (!defined('GRANAM_ROOT_DIRECTORY')) {
	throw new Exception('Vital constant [GRANAM_ROOT_DIRECTORY] is not defined');
}

require_once GRANAM_ROOT_DIRECTORY . '/Observances/Interfaces/Singleton.php';
require_once GRANAM_ROOT_DIRECTORY . '/Observances/Objects/Object.php'; // every
// granam class, except Exception, has this class as ancestor
require_once GRANAM_ROOT_DIRECTORY . '/Observances/Objects/StaticMethodsOnly.php';
require_once GRANAM_ROOT_DIRECTORY . '/Observances/Observances_Utilities.php';
require_once GRANAM_ROOT_DIRECTORY . '/Observances/Objects/Objects_Utilities.php';
require_once GRANAM_ROOT_DIRECTORY . '/Autoloads/Autoloads_Utilities.php';
require_once GRANAM_ROOT_DIRECTORY . '/Autoloads/Autoloaders/Autoloaders_Utilities.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Failures/Failure.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Failures/Exception.php';