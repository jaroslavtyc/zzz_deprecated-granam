<?php
namespace granam;

abstract class ContainerIndexedExternalOnly  extends \granam\Object
 implements Container {

	public function __construct() {
		$this->initializeStorage();
	}

	public function add($item, $index = NULL) {
		$this->checkIndex($index); // externally indexed items has to get explicitly
		// specified index
		$this->addOnIndex($item, $index);
	}

	abstract public function addOnIndex($item, $index);

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