<?php
namespace granam;

class Catcher extends \granam\SingletonObject {

	private $failureDistributor;

	protected function __construct(\granam\FailureDistributor $failureDistributor)
	{
		$this->initializeDistributor($failureDistributor);
	}

	public static function getInstance(
		\granam\ByBitmaskSearchableContainer $container = NULL
	){
		parent::getInstance($container);
	}

	public function catchFailure(\granam\Failure $failure)
	{
		$this->failureDistributor->distribute($failure);
	}

	// ---- LINEAGE FACILITIES ----

	protected static function initializeInstance(
		\granam\ByBitmaskSearchableContainer $container = NULL
	){
		if (is_null($container)) {
			throw new Exception(
				'First require of [' . get_called_class() .
					'] need to be initialized with [\granam\ByBitmaskSearchableContainer] instance',
				Exception::CONTENT | Exception::PROCESS_START
			);
		}

		parent::initializeInstance($container);
	}

	protected function validateFailureBitmask($failureBitmask)
	{
		if (!is_numeric($failureBitmask)) {
			throw new Exception(
				'Bitmask has to be valid decadic representation of error codes range.' .
					' Given bitmask has value [' . var_dump($failureBitmask, TRUE) . ']',
				\granam\Exception::CONTENT_VALUE
			);
		}

		$integerFailureBitmask = int_val($failureBitmask);
		if ($integerFailureBitmask != $failureBitmask) {
			throw new Exception(
				'Decadic part of given bitmask [' . $integerFailureBitmask .
					']differs from bitmask itself [' . $failureBitmask . ']',
				\granam\Exception::CONTENT_VALUE
			);
		}

		return $integerFailureBitmask;
	}

	// ---- LOCAL HELPERS ----
	private function initializeDistributor(
	\granam\FailureDistributor $failureDistributor
	){
		$this->failureDistributor = $failureDistributor;
	}
}