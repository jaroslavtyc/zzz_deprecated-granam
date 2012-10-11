<?php
namespace granam;

abstract class Failure extends \Exception {

	public function __construct(
		$errorMessage,
		$errorNumber,
		$errorFile = NULL,
		$errorLine = NULL,
		\Exception $previousFailure = NULL
	) {
		parent::__construct($errorMessage, $errorNumber, $previousFailure);
		$this->setFile($errorFile);
		$this->setLine($errorLine);
	}

	// ---- LOCAL HELPERS ----

	protected function setFile($file) {
		$this->file = $file;
	}

	protected function setLine($line) {
		$this->line = $line;
	}
}