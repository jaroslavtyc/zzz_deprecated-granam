<?php
namespace granam;

class FailureDistributor {

	private $failureHandlersContainer;

	public function __construct(\granam\ByBitmaskSearchableContainer $container) {
		$this->initializeFailureHandlersContainer($container);
	}

	public function distribute(\granam\Failure $failure) {

	}

	public function registerHandler(
		\granam\FailureHandler $failureHandler,
		$failureBitmask
	){
		$validatedFailureBitmask = $this->validateFailureBitmask($failureBitmask);
		if (is_int($validatedFailureBitmask)) {
			$this->failureHandlersContainer->addItem(
				$failureHandler,
				$validatedFailureBitmask
			);
		}
	}
}