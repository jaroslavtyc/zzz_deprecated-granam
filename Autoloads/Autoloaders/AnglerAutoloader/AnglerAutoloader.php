<?php
namespace granam;

/**
 * Singleton Autoload class for automaticaly loading files with php code.
 *
 * Searched filenames with scripts of interfaces and classes have to correspond
 * to settings in configurations/autoload/granam/autoload.ini
 */
class AnglerAutoloader extends \granam\Object
 implements \granam\Autoloader, \granam\Singleton {
	/**
	 * Container for directories searched for scripts
	 *
	 * @var AnglerAutoloader_SourceDirectories
	 */
	private $sourceDirectories;
	/**Container for already found scripts
	 *
	 * @var AnglerAutoloader_FoundScriptFilenames
	 */
	private $foundScriptFilenames;
	/**
	 * Holder of \granam\Autoloader instance
	 *
	 * @var \granam\Autoloader
	 */
	protected static $instance;

	protected function __construct()
	{
		$this->initializeSourceDirectories();
		$this->initializeFoundScriptFilenames();
		$this->addSourceDirectory(GRANAM_ROOT_DIRECTORY); //everytime is granam
		// directories marked for searching scripts to autoload them
	}

	/**
	 * Getter for instance of this class
	 *
	 * If instance of this class does not exists yet, newone is created and returned.
	 * If already exists, is returned existing one.
	 * (@see http://en.wikipedia.org/wiki/Singleton_pattern for answers)
	 *
	 * @return \granam\AnglerAutoloader
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$actualAutoloaderName = get_called_class();
			self::$instance = new $actualAutoloaderName();
		}

		return self::$instance;
	}

	/**
	 * First search for script file in list of already known script files.
	 * If desired scriptfile is not found, scanning of directories is performed.
	 * At last, require_once script.
	 *
	 * @param string $observanceName name of script to load
	 * @throws \granam\Exception_AccessReading if script could not be loaded
	 * @return bool if script file is found and loaded, bool is returned;
	 *  void when searching was unsuccessfull, but more autoloaders are registered
	 *  and not used yet.
	 */
	public function that($observanceName)
	{
		if (!$this->isObservanceScriptFound($observanceName)) { // position
		// of script is not known yet, try to found it
			$this->findObservanceScript($observanceName);
			if (!$this->isObservanceScriptFound($observanceName)) { // position
			// of script is not know neither after searching
				if (\granam\Autoloaders_Utilities::isLastRegisteredAutoloader(
						get_class($this),
						__FUNCTION__
					)
				) { // this autoloader and method is last in list of registered autoloaders
					throw new \granam\Exception(
						'Script for required observance [' . $observanceName .
							'] was not found in [' .
							implode(',', $this->getSourceDirectories()) . ']',
						\granam\Exception::ACCESS_READING
					);
				} else { // this autoloader is not the last, lets next autoloader to do
				// his work
					return;
				}
			}
		}

		return $this->loadObservanceScriptWithKnownLocation($observanceName);

		/*
		if (array_key_exists($observanceName, $this->listOfFoundScripts)) { // searching
			//of script has been successfull
			require_once $this->listOfFoundScripts[$observanceName];

			return TRUE;
		}

		// proper script file has not been found
		$autoloadFunctions = spl_autoload_functions(); // get all registered
		// autoload functions
		foreach($autoloadFunctions as $tier => $autoloadFunction) { // passing
			// throught list of registered autoloaders, tier reflects sequence
			// of registering autoloaders, include prepending
			if (($autoloadFunction[0] === $this)
			 && ($autoloadFunction[1] == __FUNCTION__)) { // found this function
				if ($tier < (sizeof($autoloadFunctions) -1)) { // and this is not
					// last registered autoloader

					return; // end without exception to let next autoloader to do
					// his work
				}
			}
		}
		 */
	}

	//-----------

	public function extendRange()
	{
		foreach(ArrayUtilities::trimArray(func_get_args()) as $folder){
			$this->addSourceDirectory($folder);
		}
	}

	public static function includePath($folder)
	{
		$autoload = self::getInstance();
		$autoload->addSourceDirectory($folder);
	}

	public function catchException($errorCode, $errorMessage, $nameOfScriptWithError, $rowWithError){//error handler is setted up to give errors to this function, which uses proper class to manage it in dependency of error level
		switch($errorCode){
			case E_USER_NOTICE:
				$this->noteCatching->catchOnce($errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
			case E_USER_WARNING:
				$this->warningCatching->catchOnce($errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
			case E_USER_ERROR:
				$this->errorCatching->catchOnce($errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
			default:
				$this->noticeCatching->catchOnce("(script error(code $errorCode))\t".$errorMessage, $nameOfScriptWithError, $rowWithError);
				break;
		}
	}

	/**
	* Returns list of scripts of given directory, not recursively
	*
	* @param String $dirPath full path to directory to non-recursively scan for scripts
	* @return Array list of founded script files, without suffix .php
	*/
	protected function getScriptsInDirectory($dirPath)
	{
		$scripts = array();
		foreach(scandir($dirPath) as $folder){
			if (is_file($dirPath . $folder) and (strstr($folder,'.') == '.php')) {
				$scripts[] = substr($folder,0,strrpos($folder,'.'));
			}
		}

		return $scripts;
	}

	/**
	* Set scripts of given directory to list of scripts, indexed by holding directory name, recursively
	*
	* @param String $dirPath full path to directory to non-recursively scan for scripts
	* @return Int number of founded scripts
	*/
	protected function setScriptsToDir($dirPath)
	{
		$dirPath = $dirPath . '/';
		$count = 0;
		$scriptsInDir = $this->getScriptsInDirectory($dirPath);
		$count = sizeof($scriptsInDir);
		if ($count) {
			$this->listOfRootSourceDirectories[$dirPath] = $scriptsInDir;
		}
		foreach($this->getSubdirectories($dirPath) as $subdir){
			$count += $this->setScriptsToDir($subdir);
		}

		return $count;
	}

		/**
	* Returns list of subdirectories of given directory, not recursively
	*
	* @param String $dirPath full path to directory to non-recursively scan subdirectories
	* @return Array list of founded subdirectories
	*/
	protected function getSubdirectories($dirPath)
	{
		$subdirs = array();
		foreach(scandir($dirPath) as $folder){
			if (is_dir($dirPath . $folder) and $folder != '.' and $folder != '..'){
				$subdirs[] = $dirPath . $folder;
			}
		}

		return $subdirs;
	}

		/**
	 * Getter for directories where are scripts searched
	 *
	 * @return array
	 */
	protected function getSourceDirectories()
	{
		return $this->sourceDirectories->getSourceDirectories();
	}

	/**
	 * Search for script location in list of scripts with already known locations
	 *
	 * @param string $objectName full name of class or interface, including namespace
	 * @return bool
	 */
	protected function isObservanceScriptFound($observanceName)
	{
		return $this->foundScriptFilenames
			->isObservanceScriptFound($observanceName);
	}

	protected function findObservanceScript($observanceName)
	{
		// searching for script in earmarked directories scope
		foreach ($this->sourceDirectories as $sourceDirectory) {
			foreach (scandir($sourceDirectory) as $folder) {
				if ($folder != '.' && $folder != '..') { // not current or parent
				//	directory
					if (is_dir($folder)) { // it is another directory, we put it into
					// list of source directories for later scan
						$this->addSourceDirectory($sourceDirectory . '/' . $folder);
					} elseif (preg_match('~\.php$~', $folder)) {
						$this->addFoundScript($sourceDirectory . '/' . $folder);
						if ($observanceName == $this->getLastFoundObservanceName()) {

							return; // we found desired script, searching can end
						}
					}
				}
			}
		}
	}

	/**
	 * Load script file
	 *
	 * @throws \granam\Exception if script location is not known in fact
	 * @return bool
	 */
	protected function loadObservanceScriptWithKnownLocation($observanceName)
	{
		if (class_exists($observanceName, FALSE)) { // class is already known - without
		// using autoloader
			trigger_error(
				'Required observance of name [' . $observanceName .
					'] is already usable as class. Loading script is unreasonable.',
				E_USER_WARNING
			);

			return FALSE;
		}

		if (interface_exists($observanceName, FALSE)) {  // interface is already known
		 // - without using autoloader
			trigger_error(
				'Required observance of name [' . $observanceName .
					'] is already usable as interface. Loading script is unreasonable.',
				E_USER_WARNING
			);

			return FALSE;
		}

		if ($this->isLocationOfClassScriptKnown($observanceName)) {
			$observanceType = \granam\Observances_Utilities::OBSERVANCE_CLASS;
		} elseif ($this->isLocationOfInterfaceScriptKnown($observanceName)) {
			$observanceType = \granam\Observances_Utilities::OBSERVANCE_INTERFACE;
		} else {
			throw new \granam\Exception(
				'Location of script with [' . $observanceName . '] is not known',
				\granam\Exception::ACCESS_READING
			);
		}

		// loading of script itself
		require_once $this->foundScriptFilenames[$observanceType][$observanceName];

		switch ($observanceType) { // checking availability of loaded observance
			case \granam\Observances_Utilities::OBSERVANCE_CLASS :
				if (!class_exists($observanceName, FALSE)) { // class is still not
				//available
					throw new \granam\Exception(
						'Loading required observance of name [' . $observanceName .
							'] and detected as [class] fails.',
						\granam\Exception::ACCESS_EXECUTION
					);
				}
				break;
			case \granam\Observances_Utilities::OBSERVANCE_INTERFACE :
				if (!interface_exists($observanceName, FALSE)) { // interface is still
				// not available
					throw new \granam\Exception(
						'Loading required observance of name [' . $observanceName .
							'] and detected as [interface] fails.',
						\granam\Exception::ACCESS_EXECUTION
					);
				}
				break;
			default:
				throw new \granam\Exception(
					'Unknown type of observance [' . $observanceType . ']',
					\granam\Exception::CONTENT_TYPE
				);
		}

		return TRUE;
	}

	/**
	 * Add filename of PHP script into list of known PHP scripts
	 */
	protected function addFoundScript($scriptFilename) {
		$scriptFileAnalyzer =
			new \granam\TimidScriptfileAnalyzer($scriptFilename);
		$scriptFileAnalyzer->getObservanceType();
		$scriptFileAnalyzer->getObservanceName();
	}

	// ----- LOCAL HELPERS -----

	/**
	 * Sets list of scripts with already known location and type of observance
	 * (class or interface)
	 *
	 * @return void
	 */
	private function initializeFoundScriptFilenames()
	{
		$this->foundScriptFilenames =
			new \granam\AnglerAutoloader_FoundScriptFilenames;
	}

	/**
	 * Sets container for directories which are inteneded to be searched for
	 * observance scripts
	 */
	private function initializeSourceDirectories()
	{
		$this->sourceDirectories = new \granam\AnglerAutoloader_SourceDirectories();
	}

	/**
	 * Adds new directory to list of potential sources of observance scripts
	 *
	 * @param string $folder
	 */
	private function addSourceDirectory($folder)
	{
		$this->sourceDirectories->addNewDirectory($folder);
	}

	private function getLastFoundObservanceName()
	{

	}
}