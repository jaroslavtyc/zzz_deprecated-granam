<?php
namespace granam;

/**
 * Supportive functions for autoloading
 */
class Autoloaders_Utilities extends \granam\StaticMethodsOnly {

	/**
	 * Register proper method as responsible for searching and loading PHP script
	 * with desired class or interface.
	 *
	 * @throws \granam\Exception_ServiseRegistering
	 * @return bool
	 */
	public static function register(\granam\Autoloader $autoloader)
	{
		if (self::isAutoloaderRegistered(
				get_class($autoloader),
				'that'
			)
		) {
			throw new Exception(
				'Autoloader [' . get_class($autoloader) . '] is already registered',
				Exception::SERVICE_REGISTERING
			);
		}

		if (!self::registerAutoloader(
			$autoloader,
			'that'
		)) {
			throw new Exception(
				'Registering of autoloader [' . get_class($autoloader) . '] fails',
					Exception::SERVICE_REGISTERING
			);
		}

		return self::isAutoloaderRegistered(
			get_class($autoloader),
			'that'
		);
	}

	/**
	 * Search for given autoloader identification in list of registered autoloaders.
	 *
	 * @param string $autoloaderClassName
	 * @param string $autoloaderMethodName
	 * @return bool
	 */
	public static function isAutoloaderRegistered(
		$autoloaderClassName,
		$autoloaderMethodName = FALSE // any method, respective only class is searched for
	) {
		foreach (self::getRegisteredAutoloaders() as $registeredAutoloader) {
			if ($registeredAutoloader[0] == $autoloaderClassName
			 && (FALSE === $autoloaderMethodName
				|| $registeredAutoloader[1] == $autoloaderMethodName)
			){

				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Gather list of already registered autoloaders.
	 *
	 * @return array list of registered autoloading functions and classes
	 */
	public static function getRegisteredAutoloaders() {

		$registeredAutoloaders = spl_autoload_functions();

		if (!$registeredAutoloaders) {
			return array();
		}

		return $registeredAutoloaders;
	}

	/**
	 * Tries to register given autoloader class
	 *
	 * @param string $autoloaderClassName
	 * @param string $autoloaderMethodName
	 * @param bool $prependInsteadOfAppend
	 * @throws \granam\Exception_ServiceRegistering
	 * @return bool
	 */
	public static function registerAutoloader(
		$autoloaderClassName,
		$autoloaderMethodName,
		$prependInsteadOfAppend = FALSE
	) {
		// first, check if autoloader is not registered yet
		if (self::isAutoloaderRegistered($autoloaderClassName, $autoloaderMethodName)) {
			trigger_error( // repeated registering is failure
				'Autoloader [' . $autoloaderClassName . '] with method [' .
					$autoloaderMethodName . '] is already registered',
				E_USER_WARNING
			);

			return FALSE; // method is already registered
		}

		// second, register autoloader
		try {
			spl_autoload_register(
				array($autoloaderClassName, $autoloaderMethodName),
				TRUE,
				$prependInsteadOfAppend
			);
		} catch (\Exception $originalRegisteringException) {
			throw new Exception(
				'Registering autoloader [' . $autoloaderClassName .
					'] with method [' . $autoloaderMethodName . '] failed',
				Exception::SERVICE_REGISTERING,
				$originalRegisteringException
			);
		}

		return TRUE;
	}

	/**
	 * Deregister autoloader, represented by given class and method name
	 *
	 * @param string $autoloaderClassName
	 * @param string $autoloaderMethodName
	 * @throws \granam\Exception_ServiceUnregistering
	 * @return bool
	 */
	public static function unregisterAutoloader(
		$autoloaderClassName,
		$autoloaderMethodName
	) {
		if (!self::isAutoloaderRegistered($autoloaderClassName, $autoloaderMethodName)) {
			trigger_error(
				'Autoloader [' . $autoloaderClassName . '] with method ' .
					'[' . $autoloaderMethodName . '] is not registered',
				E_USER_WARNING
			);

			return FALSE;
		}

		if (!spl_autoload_unregister(
				array($autoloaderClassName, $autoloaderMethodName)
		)) {
			throw new Exception(
				'Unregistering autoloader [' . $autoloaderClassName .
					'] with method [' . $autoloaderMethodName . '] failed',
				Exception::SERVICE_UNREGISTERING
			);
		}

		return TRUE;
	}
}