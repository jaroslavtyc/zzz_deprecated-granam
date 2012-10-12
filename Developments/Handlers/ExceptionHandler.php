<?php
namespace granam;

final class ExceptionHandler extends \granam\FailureHandler {

	private static $instance;

	public static function initializeInstance(\granam\Snitcher $snitcher) {
		if (isset(self::$instance)) {
			throw new \granam\Exception(
				sprintf('Instance of %s is already initialized', get_called_class()),
				\granam\Exception::SERVICE_LAUNCHING | \granam\Exception::PROCESS_STATE
			);
		}

		$actuallyCalledClass = get_called_class();
		self::$instance = new $actuallyCalledClass($snitcher);
	}

	final public static function getInstance() {
		if (!isset(self::$instance)) {
			throw new Exception(
				'Instance of [' . get_called_class() . '] is not initialized yet.',
				\granam\Exception::PROCESS_STATE
			);
		}

		return self::$instance;
	}

	public function catchException(\Exception $exception) {
		parent::catchFailure($exception);
	}
}