<?php
namespace granam;

abstract class ObjectIterableCountable extends \granam\ObjectIterable
 implements \Countable {

	/**
	 * Determine number of items in inner storage, respective size of container
	 * array
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->iteratorData);
	}
}