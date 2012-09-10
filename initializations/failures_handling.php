<?php
if (!defined('GRANAM_ROOT_DIRECTORY')) {
	throw new Exception('Vital constant [GRANAM_ROOT_DIRECTORY] is not defined');
}

require_once GRANAM_ROOT_DIRECTORY . '/Developments/Failures/Error.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Failures/Exception.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Handlers/Handlers_Utilities.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Handlers/FailureHandler.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Handlers/ErrorHandler.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Handlers/ExceptionHandler.php';
require_once GRANAM_ROOT_DIRECTORY . '/Developments/Viewers/Snitcher.php';

\granam\Handlers_Utilities::registerErrorHandler(
	\granam\ErrorHandler::getInstance(\granam\Snitcher::getInstance())
);
\granam\Handlers_Utilities::registerExceptionHandler(
	\granam\ExceptionHandler::getInstance(\granam\Snitcher::getInstance())
);