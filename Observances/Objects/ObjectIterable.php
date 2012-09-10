<?php
namespace granam;

/**
 * Works as wrapper of an array, basicaly filled by referenced outher values.
 */
abstract class ObjectIterable extends \granam\Object
 implements \granam\Iterator {

	/**
	 * @var array list of elements to iterate
	 */
	protected $iteratorData;
	/**
	 * @var mixed index of actual data position
	 */
	protected $currentIteratorKey;

	/**
	 * Adds main array and restart pointer to first element
	 *
	 * @param array $iteratorData list of items to walk throught
	 * @return void
	 */
	public function __construct($iteratorData = array()) {
		parent::__construct();
		$this->setIteratorData($iteratorData);
		$this->rewind();
	}

	/**
	 * Resets pointer on array to first element
	 *
	 * @return void
	 */
	public function rewind() {
		reset($this->iteratorData);
		$this->currentIteratorKey = $this->key();
	}

	/**
	 * Gives item from data array on actual position
	 *
	 * @return mixed item from iteratorData array
	 */
	public function current() {
		return current($this->iteratorData);
	}

	/**
	 * Gives index item from data array on pointer position
	 *
	 * @return mixed index on actual position of item from iteratorData array
	 */
	public function key() {
		return key($this->iteratorData); // returns NULL if out of array
	}

	/**
	 * Moves pointer to next item in iteratorData array
	 *
	 * @return void
	 */
	public function next() {
		next($this->iteratorData);
		$this->currentIteratorKey = $this->key(); // if out of array, sets NULL
	}

	/**
	 * Check if key of actual position in data array is set
	 *
	 * @return bool existence of element on current pointer position
	 */
	public function valid() {
		return !is_null($this->currentIteratorKey);
	}

	/**
	 * Check if actual position is last
	 *
	 * @return bool
	 */
	public function isLast() {
		next($this->iteratorData);
		$isLast = is_null(key($this->iteratorData));
		prev($this->iteratorData);

		return $isLast;
	}

	// ----- LOCAL HELPERS -----

	/**
	 * Inner setter for input array with data type check
	 *
	 * @param array $iteratorData
	 * @throws \granam\\granam\Exception_ContentType
	 * @return void
	 */
	private function setIteratorData($iteratorData) {
		if (!is_array($iteratorData)) {
			throw new \granam\Exception(
				'Data for iteration have to be an array',
				\granam\Exception::CONTENT_TYPE
			);
		}
		$this->iteratorData = $iteratorData;
	}
}