<?php
namespace granam;

abstract class FailureHandler extends \granam\Object implements \granam\Singleton {

	private $snitcher;

	final protected function __construct(\granam\Snitcher $snitcher) {
		$this->snitcher = $snitcher;
	}

	abstract public static function getInstance(\granam\Snitcher $snitcher = NULL);

	// --- LINEAGE FACILITIES ----

	final protected function catchFailure(\Exception $exception) {
		$this->snitcher->snitchException($exception);
	}

	protected function getSnitcher() {
		return $this->snitcher;
	}
}