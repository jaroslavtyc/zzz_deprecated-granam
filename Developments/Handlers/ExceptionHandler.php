<?php
namespace granam;

final class ExceptionHandler extends \granam\FailureHandler {

	private $instance;

	final public static function getInstance(\granam\Snitcher $snitcher = NULL)
	{
		if (!isset(self::$instance)) {
			if (is_null($snitcher)) {
				throw new Exception(
					'Snitcher is needed for first require of [' . __CLASS__ . '] instance',
					Exception::CONTENT_VALUE | Exception::SERVICE_LAUNCHING
				);
			}

			$actualCalledClass = get_called_class();
			self::$instance = new $actualCalledClass($snitcher);
		} elseif (!is_null($snitcher) && $snitcher !== self::$instance->getSnitcher()) {
			throw new Exception(
				'Instance of [' . get_class(self::$instance) . '] is already built ' .
					'with another snitcher',
				Exception::CONTENT_VALUE | Exception::PROCESS_STATE
			);
		}

		return self::$instance;
	}

	public function catchException(\Exception $exception)
	{
		parent::catchFailure($exception);
	}
}