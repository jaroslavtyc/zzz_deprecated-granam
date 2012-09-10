<?php
namespace granam;

class AnglerAutoloader_FoundScriptFilenames extends \granam\Object
 implements \granam\Iterator, \Countable {

	protected $foundScriptFilenames;
	/**
	 * @var mixed index of actual data position
	 */
	protected $currentIteratorKey;

	public function __construct()
	{
		parent::__construct();
		$this->initializeFoundScriptFilenames();
		$this->rewind();
	}

	/**
	 * @param string $observanceName
	 * @return bool
	 */
	public function isObservanceScriptFound($observanceName)
	{
		return (bool)$this->getObservanceType($observanceName);
	}

	/**
	 * @param string $observanceName
	 * @return string|bool type of observance or false if unknown
	 */
	public function getObservanceType($observanceName)
	{
		foreach ($this->foundScriptFilenames as
		 $observanceType => $container) {
			if ($container->isScriptFound($observanceName)) {

				return $observanceType;
			}
		}

		return FALSE;
	}

	// ---- COUNTABLE FACILITIES ----

	/**
	 * Counts number of all knwown scripts of every type of observance
	 *
	 * @return int
	 */
	public function count()
	{
		$numberOfKnownScripts = 0;
		foreach ($this->foundScriptFilenames as $foundObservanceScriptFilenames) {
			$numberOfKnownScripts += count($foundObservanceScriptFilenames);
		}

		return $numberOfKnownScripts;
	}

	// ---- ITERATOR FACILITIES ----

	/**
	 * Resets pointer on array to first element
	 *
	 * @return void
	 */
	public function rewind() {
		reset($this->foundScriptFilenames);
		$this->currentIteratorKey = $this->key();
	}

	/**
	 * Gives item from data array on actual position
	 *
	 * @return mixed item from data array
	 */
	public function current() {
		return current($this->foundScriptFilenames);
	}

	/**
	 * Gives index item from data array on pointer position
	 *
	 * @return mixed index on actual position of item from data array
	 */
	public function key() {
		return key($this->foundScriptFilenames); // returns NULL if out of array
	}

	/**
	 * Moves pointer to next item in data array
	 *
	 * @return void
	 */
	public function next() {
		next($this->foundScriptFilenames);
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
		next($this->foundScriptFilenames);
		$isLast = is_null(key($this->foundScriptFilenames));
		prev($this->foundScriptFilenames);

		return $isLast;
	}

	// ---- LOCAL HELPERS ----

	private function initializeFoundScriptFilenames()
	{
		$this->foundScriptFilenames = array(
			\granam\Observances_Utilities::OBSERVANCE_CLASS =>
				new \granam\Autoloader_FoundScriptFilenames_Container,
			\granam\Observances_Utilities::OBSERVANCE_INTERFACE =>
				new \granam\Autoloader_FoundScriptFilenames_Container,
		);
	}
}