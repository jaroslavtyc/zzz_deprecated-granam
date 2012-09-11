<?php
namespace granam;

abstract class ContainerIndexedExternalOnly  extends \granam\Object
 implements Container {

	public function __construct() {
		$this->initializeStorage();
	}

	public function addItem($item, $index = NULL) {
		$this->checkIndex($index); // externally indexed items has to get explicitly
		// specified index
		$this->addItemToIndex($item, $index);
	}

	abstract public function addItemOnIndex($item, $index);

	protected function checkIndex($index) {
		if (!is_null($index)) {
			throw new \granam\Exception(
				'Given index can not be null',
				\granam\Exception::CONTENT_VALUE
			);
		}

		return TRUE;
	}
}