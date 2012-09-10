<?php
namespace granam;

class CodeChopper extends CodeChopper_InnerFacilities {

	const MEANING_NAUGHT = 0,
		MEANING_CODE = 255, // b11111111
		MEANING_CODE_COMMENT = 4, // b11
		MEANING_CODE_COMMENT_SINGLELINE = 1, // b01
		MEANING_CODE_COMMENT_MULTILIINE = 2; // b10

	public function __construct()
	{
		parent::__construct();
	}

	public function addCodeRow($row)
	{
		if (!is_scalar($row) || is_bool($row)
		 && !(is_object($row) && method_exists($row, '__toString'))
		) {
			throw new \granam\Exception(
				'Given value has to be a scalar except boolean or to string ' .
					'convertable object, is type of [' . gettype($row) .']',
				\granam\Exception::CONTENT_TYPE
			);
		}

		$this->codeRows[] = (string)$row;
	}

	public function setSkipComments($skip)
	{
		$this->setSkipSinglelineCommnet($skip);
		$this->setSkipMultilineCommnet($skip);
	}

	public function setSkipSinglelineCommnet($skip)
	{
		$this->skipSinglelineComment = (bool)$skip;
	}

	public function setSkipMultilineCommnet($skip)
	{
		$this->skipMultilineComment = (bool)$skip;
	}
}