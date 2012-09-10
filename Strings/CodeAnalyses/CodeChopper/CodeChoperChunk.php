<?php
namespace granam;

class CodeChoperChunk extends \granam\Object {

	private $code;
	private $meaning;

	public function __construct($code, $meaning)
	{
		parent::__construct();
		$this->setCode($code);
		$this->setMeaning($code);

		$this->meaning = $meaning;
	}

	private function setCode($code)
	{
		$this->code = $code;
	}

	private function setMeaning($meaining)
	{
		if (!is_numeric($meaining)) {
			throw new \granam\Exception(
				'Unknown meaning type [' . gettype($meaning) . ']',
				\granam\Exception::CONTENT_TYPE
			);
		}

		$this->meaining = $meaining;
	}
}