<?php
namespace granam;

final class ErrorHandler extends \granam\FailureHandler {

	private static $instance;

	final public static function getInstance(\granam\Snitcher $snitcher = NULL) {
		if (!isset(self::$instance)) {
			if (is_null($snitcher)) {
				throw new Exception(
					'Snitcher is needed for first require of [' . get_called_class() .
						'] instance as child of [' . __CLASS__ .'] class',
					Exception::CONTENT_VALUE | Exception::SERVICE_LAUNCHING
				);
			}

			$actualCalledClass = get_called_class();
			self::$instance = new $actualCalledClass($snitcher);
		} elseif (!is_null($snitcher) && $snitcher !== self::$instance->getSnitcher()) {
			throw new Exception(
				'Instance of [' . get_called_class() . '] is already built ' .
					'with another snitcher',
				Exception::CONTENT_VALUE | Exception::PROCESS_STATE
			);
		}

		return self::$instance;
	}

	public function catchError(
		$errorCode,
		$message,
		$file = NULL,
		$line = NULL,
		$context = NULL
	) {
		$error = new \granam\Error($message, $errorCode, $file, $line, $context);
		// every handled failure in granam FailureHandler is based on \Exception
		parent::catchFailure($error);
	}
}