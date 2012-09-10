<?php
namespace granam;

class Handlers_Utilities extends \granam\StaticMethodsOnly {

	private static $registeredErrorHandler;
	private static $registeredExceptionHandler;

	public static function registerErrorHandler(\granam\ErrorHandler $errorHandler)
	{
		$previousErrorHandler = set_error_handler(array($errorHandler, 'catchError'));
		if (!is_null($previousErrorHandler)) {
			throw new Exception(
				'Error handler is already set by [' .
					var_export($previousErrorHandler, TRUE) . ']',
					Exception::PROCESS_STATE | Exception::SERVICE_REGISTERING
			);
		}

		self::$registeredErrorHandler = $errorHandler;
	}

	public static function registerExceptionHandler(
		\granam\ExceptionHandler $exceptionHandler
	) {
		$previousExceptionHandler = set_exception_handler(array($exceptionHandler, 'catchException'));
		if (!is_null($previousExceptionHandler)) {
			throw new Exception(
				'Exception handler is already set by [' .
					var_export($previousExceptionHandler, TRUE) . ']',
					Exception::PROCESS_STATE | Exception::SERVICE_REGISTERING
			);
		}

		self::$registeredExceptionHandler = $exceptionHandler;
	}

	public static function getRegisteredErrorHandler()
	{
		return self::$registeredErrorHandler;
	}

	public static function getRegisteredExceptionHandler()
	{
		return self::$registeredExceptionHandler;
	}
}