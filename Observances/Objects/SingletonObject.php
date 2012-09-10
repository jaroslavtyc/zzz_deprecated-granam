<?php
namespace granam;

abstract class SingletonObject extends \granam\Object
 implements \granam\Singleton {

	private static $instance;

	protected function __construct($initializingParameter = NULL)
	{}

	public static function getInstance($initializingParameter = NULL)
	{
		if (!isset(self::$instance)) {
			self::initializeInstance($initializingParameter);
		}

		return self::$instance;
	}

	protected static function initializeInstance($initializingParameter = NULL)
	{
		$currentlyCalledClass = get_called_class();
		self::$instance = new $currentlyCalledClass($initializingParameter);
	}
}