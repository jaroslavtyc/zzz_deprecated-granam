<?php
namespace granam;

class BitmaskIndexedMultipleItemsContainer // on same index can be more items, sharing
// that index
 extends \granam\ContainerIndexedExternalOnly {

	private $storage;
	private $mergedBitmask;

	public function __construct() {
		$this->initializeStorage();
		$this->initializeMergedBitmask();
	}

	public function addOnIndex($item, $bitmaskIndex) {
		$this->checkBitmask($bitmaskIndex);
		$this->mergeBitmask($bitmaskIndex);
		$this->ensureIndexInStorage($bitmaskIndex);
		$this->insertItem($item, $bitmaskIndex);
	}

	//@TODO - removing item, therefore merged bitmask has to be altered

	// ---- LINEAGE FACILITIES ----

	protected function checkBitmask($bitmaskIndex) {
		if (empty($bitmaskIndex)) {
			throw new Exception(
				'Given bitmask is empty',
				Exception::CONTENT_VALUE
			);
		}

		if (!is_numeric($bitmaskIndex)) {
			throw new Exception(
				'Given bitmask does not containt a numeric bitmask expression, but [' .
					gettype($bitmaskIndex) . ']',
				Exception::CONTENT_TYPE
			);
		}

		return TRUE;
	}

	protected function ensureIndexInStorage($index) {
		if (!array_key_exists($index, $this->storage)) {
			$this->storage[$index] = array();
		}
	}

	//@TODO - add trotlefest check of integer bitmask | bitwise bitmask (not allowed)
	// using; especially combinating of these is dangerous and therefore prohibited
	protected function mergeBitmask($bitmask) {
		$this->mergedBitmask |= $bitmask;
	}

	protected function initializeStorage() {
		if (isset($this->storage)) {
			throw new \granam\Exception(
				'Storage of this container is already set on',
				\granam\Exception::SERVICE_REGISTERING | \granam\Exception::PROCESS_STATE
			);
		}

		$this->storage = array();
	}

	protected function insertItem($item, $index) {
		$this->storage[$index][] = $item;
	}

	protected function initializeMergedBitmask() {
		if (isset($this->mergedBitmask)) {
			throw new \granam\Exception(
				'Merged bitmask of this container is already set on',
				\granam\Exception::SERVICE_REGISTERING | \granam\Exception::PROCESS_STATE
			);
		}

		$this->mergedBitmask = 0;
	}
}