<?php
namespace granam;

class TagAndPosition extends \granam\Object {

	const UNKNOWN_POSITION = FALSE;

	private $tag;
	private $startPosition;

	/**
	 * @param string|bool $tag
	 * @param int|bool $startPosition
	 */
	public function __construct($tag, $startPosition)
	{
		parent::__construct();
		$this->initializeTag($tag);
		$this->initializeStartPosition($startPosition);
	}

	public function getTag()
	{
		return $this->tag;
	}

	public function getStartPosition()
	{
		$this->startPosition;
	}

	public function getAfterEndPosition()
	{
		return is_int($this->startPosition) && is_string($this->tag)
			? $this->startPosition + strlen($this->tag)
			: FALSE;
	}

	// ---- LOCAL HELPERS ----

	private function initializeTag($tag)
	{
		if (!Strings_Utilities::isStringOrToStringConvertable($tag)) {
			throw new \granam\Exception(
				'Given tag value is not scalar or to string convertable object, but [' .
					gettype($tag) . ']',
				\granam\Exception::CONTENT_TYPE
			);
		} else { // is to string equivalent convertable
			$this->tag = (string)$tag;
		}
	}

	private function initializeStartPosition($startPosition)
	{
		if (!is_numeric($startPosition)) {
			if (!is_bool($startPosition)) {
				throw new \granam\Exception(
					'Given starting tag position is not a number nor boolean , but [' .
						gettype($startPosition) . ']',
					\granam\Exception::CONTENT_TYPE
				);
			} elseif (FALSE !== $startPosition) {
				throw new \granam\Exception(
					'Given starting position can be only [' .
						var_export(CodeAnalyses_Utilities::UNKNOWN_POSITION, TRUE).
						'] as boolean',
					\granam\Exception::CONTENT_VALUE
				);
			} else { // position is false (not found)
				$this->startPosition = self::UNKNOWN_POSITION;
			}
		} else {
			$this->startPosition = (int)$startPosition;
		}
	}
}