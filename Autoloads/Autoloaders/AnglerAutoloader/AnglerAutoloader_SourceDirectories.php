<?php
namespace granam;

class AnglerAutoloader_SourceDirectories extends \granam\Object
 implements \granam\Iterator, \Countable {

	protected $sourceDirectories;
	/**
	 * @var mixed index of actual data position
	 */
	protected $currentIteratorKey;

	public function __construct()
	{
		parent::__construct();
		$this->initializeSourceDirecotries();
		$this->rewind();
	}

	/**
	 * Checks if given folder exists, is directory and if can be readed.
	 * If is not in list of source directories, is putted into.
	 *
	 * @param string $directory full path of directory
	 * @throws \granam\Exception in case of reading directory fail
	 * @return bool if directory has been added to list; false if already is in list
	 */
	public function addNewDirectory($directory)
	{
		$trimmedDirecotry = trim($directory);
		if ($trimmedDirecotry === '') {
			throw new \granam\Exception(
				'Given source directory path is empty (variable type [' .
					gettype($directory) . '])',
				\granam\Exception::CONTENT_TYPE | \granam\Exception::CONTENT_VALUE
			);
		}

		if (preg_match('~^\.{1,2}~', $trimmedDirecotry)) {
			throw new \granam\Exception(
				'Given new source directory path [' . $trimmedDirecotry .
					'] has to be absolute to root.',
				\granam\Exception::CONTENT_VALUE
			);
		}

		$directoryRealpath = realpath($trimmedDirecotry);
		if (!is_string($directoryRealpath)) {
			throw new \granam\Exception(
				'New source directory [' . $directoryRealpath . '] has not been found',
				\granam\Exception::ACCESS_READING
			);
		}

		if (!is_dir($directoryRealpath)) {
			throw new \granam\Exception(
				'Given fodler [' . $directoryRealpath . '] is not directory',
				\granam\Exception::ACCESS_READING
			);
		}

		if (FALSE === open_dir($directoryRealpath)) {
			throw new \granam\Exception(
				'Given directory [' . $directoryRealpath . '] could not be readed',
				\granam\Exception::ACCESS_READING
			);
		}

		// directory is already in list
		if (in_array($directoryRealpath, $this->sourceDirectories)) {
			return FALSE;
		}

		// directory is not yet in list
		$this->sourceDirectories[] = $directoryRealpath;

		return TRUE;
	}

	/**
	 * Getter for list of directories
	 *
	 * @return array
	 */
	public function getSourceDirectories()
	{
		return $this->sourceDirectories;
	}

	// ---- COUNTABLE FACILITIES ----

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->sourceDirectories);
	}

	// ---- ITERATOR FACILITIES ----

	/**
	 * Resets pointer on array to first element
	 *
	 * @return void
	 */
	public function rewind() {
		reset($this->sourceDirectories);
		$this->currentIteratorKey = $this->key();
	}

	/**
	 * Gives item from data array on actual position
	 *
	 * @return mixed item from data array
	 */
	public function current() {
		return current($this->sourceDirectories);
	}

	/**
	 * Gives index item from data array on pointer position
	 *
	 * @return mixed index on actual position of item from data array
	 */
	public function key() {
		return key($this->sourceDirectories); // returns NULL if out of array
	}

	/**
	 * Moves pointer to next item in data array
	 *
	 * @return void
	 */
	public function next() {
		next($this->sourceDirectories);
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
		next($this->sourceDirectories);
		$isLast = is_null(key($this->sourceDirectories));
		prev($this->sourceDirectories);

		return $isLast;
	}

	// ---- LOCAL HELPERS ----

	private function initializeSourceDirecotries()
	{
		$this->sourceDirectories = array();
	}
}