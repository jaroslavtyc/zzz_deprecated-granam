<?php
namespace granam;
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Exceptions/NetteDebug/Debugger.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Exceptions/NetteDebug/Helpers.php';

/**
 * Wrapper for adopted Nette::Debugger, errors and exceptions part
 * Thank to the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the http://nette.org/en/license
 */
class FaultStalker extends \granam\Object implements Singleton {

	private static $instance;
	private $snitcher;

	final protected function __construct(\granam\Snitcher $snitcher)
	{
		parent::__construct();
		$this->setSnitcher($snitcher); // is responsible for required
		// publication of traced error
		$this->initializeExternalStalker(); // stalker itself does not track
		// errors, uses external services
	}

	public static function getInstance(\granam\Snitcher $snitcher)
	{
		if (!isset(self::$instance)) {
			$actualClassName = get_called_class();
			self::$instance = new $actualClassName($snitcher);
		}

		return self::$instance;
	}

	// ---- GETTERS ----

	public function getStartMicrotimeInMiliseconds()
	{
		return \Nette\Diagnostics\Debugger::$time;
	}

	// ---- SETTERS ----

	public function setSnitcher(\granam\Snitcher $snitcher)
	{
		$this->snitcher = $snitcher;
	}

	public function setBlueScreen(\Nette\Diagnostics\BlueScreen $blueScreen)
	{
		\Nette\Diagnostics\Debugger::$blueScreen = $blueScreen;
	}

	public function setStrictMode($errorLevelOrBool)
	{
		\Nette\Diagnostics\Debugger::$strictMode = $errorLevelOrBool;
	}
}