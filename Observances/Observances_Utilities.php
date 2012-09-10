<?php
namespace granam;

class Observances_Utilities extends \granam\StaticMethodsOnly {
	/**
	 * String representation of observance type class
	 * @var string
	 */
	const OBSERVANCE_CLASS = 'class';
	/**
	 * String representation of observance type interface
	 * @var string
	 */
	const OBSERVANCE_INTERFACE = 'interface';

	/**
	 * Search for observance, determine if is class or interface and return name
	 * of that observance type
	 *
	 * @param string $observanceName
	 * @return string|bool
	 */
	public static function getObservanceType($observanceName)
	{
		if (class_exists($observanceName)) {
			return self::OBSERVANCE_CLASS;
		}

		if (interface_exists($observanceName)) {
			return self::OBSERVANCE_INTERFACE;
		}

		return FALSE;
	}

		/**
	 * Withdraw namespace string from given class or interface name
	 *
	 * @param string $namespacedObservance
	 * @return string|bool found namespace or empty string, or FALSE on failure
	 */
	public static function extractNamespace($namespacedObservance)
	{
		return trim(substr(
			$namespacedObservance,
			0,
			strrpos($namespacedObservance, '\\')
		));
	}

	/**
	 * Delete prepending namespace from given class or interface name
	 *
	 * @param type $namespacedObservance
	 * @return string|bool residual basename or empty string, or FALSE on failure
	 */
	public static function removeNamespace($namespacedObservance)
	{
		return trim(
			ltrim(
				substr( // from last backslash to end
					$namespacedObservance,
					strrpos($namespacedObservance, '\\')
				),
				'\\'
			)
		);
	}

	/**
	 * Checks if given class or interface name is able to use.
	 *
	 * If granam\Autoload is used for autoloading, requirement is directly passed
	 * to him.
	 * If not, generic way by class_exists and interface_exists is used to find out
	 * usability of class or interface.
	 *
	 * @param string $name full name of class, including namespace
	 * @param bool $askAutoloader if use autoloader to detect availability of
	 *  observance (class or interface)
	 * @return bool
	 */
	public static function isAvailable($observanceName, $askAutoloader = TRUE)
	{
		if ($askAutoloader
		 && \granam\Autoloaders_Utilities::isAutoloaderRegistered('Autoload')
		){

			return \granam\Autoload::isAvailable($observanceName); // \granam\Autoload
			// has special, faster way to determine availability than standard way
		}

		return class_exists($observanceName, $askAutoloader)
		 || interface_exists($observanceName, $askAutoloader);
	}

	public static function isClassAvailable($className, $askAutoloader = TRUE)
	{
		if ($askAutoloader
		 && \granam\Autoloaders_Utilities::isAutoloaderRegistered('Autoload')
		){

			return \granam\Autoload::isClassAvailable($className); // \granam\Autoload
			// has special, faster way to determine availability than standard way
		}

		return class_exists($className, $askAutoloader);
	}

	public static function isInterfaceAvailable($interfaceName, $askAutoloader = TRUE)
	{
		if ($askAutoloader
		 && \granam\Autoloaders_Utilities::isAutoloaderRegistered('Autoload')
		){

			return \granam\Autoload::isAvailable($interfaceName); // \granam\Autoload
			// has special, faster way to determine availability than standard way
		}

		return interface_exists($interfaceName, $askAutoloader);
	}
}