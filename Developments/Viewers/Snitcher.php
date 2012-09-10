<?php
namespace granam;
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/Object.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/Logger.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/FireLogger.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/BlueScreen.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/Bar.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/IBarPanel.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/DefaultBarPanel.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/Debugger.php';
require_once GRANAM_ROOT_DIRECTORY . '/libraries/Developments/NetteDebug/Helpers.php';

/**
 * Wrapper for adopted Nette::Debugger
 * Thank to the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the http://nette.org/en/license
 */
class Snitcher extends \granam\Object implements Singleton {

	const ENVIRONMENT_DEVELOPMENT = \Nette\Diagnostics\Debugger::DEVELOPMENT,
		ENVIRONMENT_PRODUCTION = \Nette\Diagnostics\Debugger::PRODUCTION,
		ENVIRONMENT_AUTODETECT = \Nette\Diagnostics\Debugger::DETECT;
	const DISPLAY_MODE_HTML = 'html',
		DISPLAY_MODE_CONSOLE = 'cli';

	private static $instance;
	private $logToStandardOutput = TRUE;
	private $logToDirectory = FALSE;
	private $logDirectory;
	private $logToEmail = FALSE;
	private $logEmail;

	protected function __construct()
	{
		parent::__construct();
		$this->initializeNetteDebugger();
		$this->setEnvironment(self::ENVIRONMENT_AUTODETECT);
	}

	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			$actualClassName = get_called_class();
			self::$instance = new $actualClassName();
		}

		return self::$instance;
	}

	// ---- CAPABILITIES ----

	public function snitchMessageToStandardOutput($message)
	{
		if ($this->isLogToStandardOutputAllowed()) {
			echo (string)$message;
		} else {
			trigger_error(
				'Snitching message to standard output fails cause of restriction. ' .
					'Message was [' . $message .']',
				E_USER_WARNING
			);
		}
	}

	public function snitchException(\Exception $exception)
	{
		\Nette\Diagnostics\Debugger::_exceptionHandler($exception);
		if ($this->isLogToStandardOutputAllowed()) {
			$this->snitchMessageToStandardOutput(
				'Exception [' . get_class($exception) . '] : [' .
				$exception->getMessage() . '] in ' . $exception->getFile() .
				':' . $exception->getLine()
			);
		}
	}

	public function snitchError($severity, $message, $file, $line, $context)
	{
		\Nette\Diagnostics\Debugger::
			_errorHandler($severity, $message, $file, $line, $context);
		if ($this->isLogToStandardOutputAllowed()) {
			$this->snitchMessageToStandardOutput(
				'Error (errno: ' . $severity . ') : [' . $message . '] in ' .
				$file . ':' . $line
			);
		}
	}

	// ---- GETTERS ----

	public function isLogToStandardOutputAllowed()
	{
		return $this->logToStandardOutput;
	}

	public function getLogDirectory()
	{
		return $this->logDirectory;
	}

	public function isLogToDirectoryAllowed()
	{
		return $this->logToDirectory;
	}

	public function getLogEmail()
	{
		return $this->logEmail;
	}

	public function isLogToEmailAllowed()
	{
		return $this->logToEmail;
	}

	// ---- SETTERS ----

	public function setEnvironment($environment)
	{
		if ($environment !== self::ENVIRONMENT_AUTODETECT
		 && $environment !== self::ENVIRONMENT_PRODUCTION
		 && $environment !== self::ENVIRONMENT_DEVELOPMENT
		) {
			throw new Exception(
				'Unknown mode representation by value [' . var_export($environment, TRUE) . ']',
				Exception::CONTENT_VALUE
			);
		}

		\Nette\Diagnostics\Debugger::$productionMode = $environment;
	}

	public function setDisplayMode($mode)
	{
		switch($mode) {
			case self::DISPLAY_MODE_HTML :
				\Nette\Diagnostics\Debugger::$consoleMode = FALSE;
				break;
			case self::DISPLAY_MODE_CONSOLE :
				\Nette\Diagnostics\Debugger::$consoleMode = TRUE;
				break;
			default:
				trigger_error(
					'Given display mode value [' . $mode . '] is unknown',
					E_USER_WARNING
				);
		}
	}

	public function enableLogToStandardOutput()
	{
		$this->logToStandardOutput = TRUE;
	}

	public function disableLogToStandardOutput()
	{
		$this->logToStandardOutput = FALSE;
	}

	public function setLogDirectory($logDirectory)
	{
		if (!file_exists($logDirectory)) {
			throw new Exception(
				'Given directory [' . $logDirectory . '] does not exists',
				Exception::CONTENT_VALUE
			);
		}

		if (!is_dir($logDirectory)) {
			throw new Exception(
				'Given folder [' . $logDirectory . '] is not a directory',
				Exception::CONTENT_VALUE | Exception::SERVICE_REGISTERING
			);
		}

		\Nette\Diagnostics\Debugger::$logDirectory = $logDirectory;
	}

	public function enableLogToDirectory()
	{
		$this->logToDirectory = TRUE;
		if (!empty($this->logDirectory)) {
			\Nette\Diagnostics\Debugger::$logDirectory = $this->logDirectory;
		}
	}

	public function disableLogToDirectory()
	{
		$this->logToDirectory = FALSE;
		\Nette\Diagnostics\Debugger::$logDirectory = FALSE;
	}

	public function setLogEmail($email)
	{
		if (!Strings_Utilities::isStringOrToStringConvertableObject($email)) {
			throw new Exception(
				'Given email address is not a string but [' .gettype($email) .
					']',
				Exception::CONTENT_TYPE
			);
		}

		$stringEmail = (string)$email;
		if (!$stringEmail !== var_filter($stringEmail, FILTER_SANITIZE_EMAIL)) {
			throw new Exception(
				'Given email address [' . $stringEmail . '] is not valid',
				Exception::CONTENT_VALUE | Exception::SERVICE_REGISTERING
			);
		}

		$this->logEmail = $stringEmail;
		if ($this->logToEmail) {
			\Nette\Diagnostics\Debugger::$email = $this->logEmail;
		}
	}

	public function enableLogToEmail()
	{
		$this->logToEmail = TRUE;
		if (!empty($this->logEmail)) {
			\Nette\Diagnostics\Debugger::$email = $this->logEmail;
		}
	}

	public function disableLogToEmail()
	{
		$this->logToEmail = FALSE;
		\Nette\Diagnostics\Debugger::$email = FALSE;
	}


	/**
	 * Omit HTML formatting
	 */
	public function setConsoleMode($consoleMode)
	{
		\Nette\Diagnostics\Debugger::$consoleMode = (bool)$consoleMode;
	}

	public function setBrowserPath($browserPath)
	{
		if (empty(\Nette\Diagnostics\Debugger::$consoleMode)) {
			trigger_error(
				'Settings browser does not have sense without output to console',
				E_USER_NOTICE
			);
		}

		\Nette\Diagnostics\Debugger::$browser = $browserPath;
	}

	// ---- LOCAL HELPERS ----

	private function initializeNetteDebugger()
	{
		\Nette\Diagnostics\Debugger::_init();
	}
}