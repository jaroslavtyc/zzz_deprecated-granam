<?php
namespace granam;
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Exceptions/NetteDebug/Debugger.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Exceptions/NetteDebug/Helpers.php';

class Dumper extends \granam\Object implements Singleton {

	private static $instance;
	private $publisher;

	final protected function __construct(\granam\Publisher $publisher)
	{
		parent::__construct();
		$this->setPublisher($publisher); // is responsible for required
		// publication of traced error
	}

	public static function getInstance(\granam\Publisher $publisher)
	{
		if (!isset(self::$instance)) {
			$actualClassName = get_called_class();
			self::$instance = new $actualClassName($publisher);
		}

		return self::$instance;
	}

	// ---- CAPABILITIES ----

	public static function dumpThat($variable)
	{
		return \Nette\Diagnostics\Debugger::dump($variable, FALSE);
	}

	public static function makeHtmlDescriptedVariable($variable)
	{
		return \Nette\Diagnostics\Debugger::dump($variable, TRUE);
	}

	// ---- SETTERS ----

	public function setPublisher(\granam\Publisher $publisher)
	{
		$this->publisher = $publisher;
	}

	public function setShowLocationOfDumped($show)
	{
		\Nette\Diagnostics\Debugger::$showLocation = (bool)$show;
	}

	public function setMaximalDepthOfDumpedStructure($maximalStructureDepth)
	{
		\Nette\Diagnostics\Debugger::$maxDepth = (int)$maximalStructureDepth;
	}

	public function setMaximalLengthOfDumpedString($maximalStringLength)
	{
		\Nette\Diagnostics\Debugger::$maxLen = (int)$maximalStringLength;
	}

	public function setConsoleColorsOfDumpedVariableType(array $colors)
	{
		\Nette\Diagnostics\Debugger::$consoleColors = $colors;
	}
}