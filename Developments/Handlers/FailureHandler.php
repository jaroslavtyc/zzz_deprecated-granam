<?php
namespace granam;

abstract class FailureHandler extends \granam\Object implements \granam\Singleton {

	private $snitcher;

	final protected function __construct(\granam\Snitcher $snitcher)
	{
		$this->snitcher = $snitcher;
	}

	final public static function getInstance(\granam\Snitcher $snitcher = NULL)
	{
		$actualCalledClass = get_called_class();
		if (!is_object(self::getInstanceContainer())) {
			if (is_null($snitcher)) {
				throw new Exception(
					'Snitcher is needed for first require of [' . $actualCalledClass .
						'] instance as child of [' . __CLASS__ .'] class',
					Exception::CONTENT_VALUE | Exception::SERVICE_LAUNCHING
				);
			}

			self::$instance = new $actualCalledClass($snitcher);
		} elseif (!is_null($snitcher) && $snitcher !== self::$instance->getSnitcher()) {
			throw new Exception(
				'Instance of [' . $actualCalledClass . '] is already built ' .
					'with another snitcher',
				Exception::CONTENT_VALUE | Exception::PROCESS_STATE
			);
		}

		return self::$instance;
	}

	// --- LINEAGE FACILITIES ----

	final protected function catchFailure(\Exception $exception)
	{
		$this->snitcher->snitchException($exception);
	}

	protected function getSnitcher()
	{
		return $this->snitcher;
	}

	abstract protected static function getInstanceContainer();
}