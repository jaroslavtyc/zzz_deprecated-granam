<?php
namespace granam;

class Error extends \granam\Failure {

	// commented values of constants relates to PHP 5.4
	const
		GENERIC = 0, // b0 ~ d0
		ERROR = E_ERROR, // b1 ~ d1
			CORE_ERROR = E_CORE_ERROR, // b10000 ~ d16
			COMPILE_ERROR = E_COMPILE_ERROR, // b100000 ~ d64
			USER_ERROR = E_USER_ERROR, // b100000000 ~ d256
			RECOVERABLE_ERROR = E_RECOVERABLE_ERROR, // b1000000000000 ~ d4096
		WARNING = E_WARNING, // b10 ~ d2
			CORE_WARNING = E_CORE_WARNING, // b10000 ~ d32
			COMPILE_WARNING = E_COMPILE_WARNING, // b10000000 ~ d128
			USER_WARNING = E_USER_WARNING, // b1000000000 ~ d512
			ALL_WARNINGS = E_ALL_WARNINGS, // b1010100010 ~ d674
		PARSE = E_PARSE, // b100 ~ d4
		NOTICE = E_NOTICE, // b1000 ~ d8
			USER_NOTICE = E_USER_NOTICE, // b10000000000 ~ d1024
		STRICT = E_STRICT, // b100000000000 ~ d2048
		DEPRECATED = E_DEPRECATED, // b10000000000000 ~ d8192
			USER_DEPRECATED = E_USER_DEPRECATED, // b100000000000000 ~ d16348
		ALL_ERRORS = 32767; // b111111111111111 ~ d32767 ; E_ALL in PHP 5.4;
			// -1 in any version of PHP

	private $context;

	public function __construct(
		$errorMessage,
		$errorNumber,
		$errorFile = NULL,
		$errorLine = NULL,
		$errorContext = array()
	) {
		$this->setContext($errorContext);
		parent::__construct($errorMessage, $errorNumber, $errorFile, $errorLine);
	}

	public function getContext() {
		return $this->context;
	}

	// ---- LOCAL HELPERS ----

	private function setContext($context) {
		$this->context = $context;
	}
}