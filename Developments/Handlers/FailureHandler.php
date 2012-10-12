<?php
namespace granam;

abstract class FailureHandler extends \granam\Object implements \granam\FailureSingleton {

	private $snitcher;

	final protected function __construct(\granam\Snitcher $snitcher) {
		$this->snitcher = $snitcher;
	}

	// --- LINEAGE FACILITIES ----

	final protected function catchFailure(\Exception $exception) {
		$this->snitcher->snitchException($exception);
	}

	protected function getSnitcher() {
		return $this->snitcher;
	}
}