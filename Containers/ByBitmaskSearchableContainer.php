<?php
namespace granam;

class ByBitmaskSearchableContainer extends \granam\Object
 implements \granam\Container {

	private $storage;

	public function __construct() {

	}

	public function addItem($item, $bitmaskIndex) {
		$this->checkBitmask($bitmaskIndex);

	}

	// ---- LINEAGE FACILITIES ----

	protected function checkBitmask($bitmask) {
		if (!is_numeric($bitmaskIndex)) {
			throw new Exception(
				'Given bitmask does not containt a numeric value, but [' .
					gettype($bitmaskIndex) . ']',
				Exception::CONTENT_TYPE
			);
		}

		if (empty($bitmaskIndex)) {
			throw new Exception(
				'Given bitmask is empty.',
				Exception::CONTENT_VALUE
			);
		}

		return TRUE;
	}
}