<?php
namespace granam;

class Objects_Utilities extends \granam\StaticMethodsOnly {

	/**
	 * Tryes to build new class with desired name.
	 * Should inherit from a class and implements interfaces.
	 *
	 * Every name, include className, should contain optional namespace in string.
	 *
	 * @param string $className
	 * @param string $parenClassName
	 * @param string|array $interfaceNames
	 * @return bool if class is available after building
	 */
	public static function buildClass(
		$className,
		$parenClassName = FALSE,
		$interfaceNames = FALSE
	) {
		if (\granam\Observances_Utilities::isAvailable($className)) {
			throw new \granam\Exception(
				'Observance of name [' . $className . '] already exists',
				\granam\Exception::ACCESS_EXECUTION
			);
		}

		if (!empty($parenClassName)
		 && !\granam\Observances_Utilities::isClassAvailable($parenClassName)) {
			if (\granam\Observances_Utilities::isAvailable($parenClassName)) {
				throw new \granam\Exception(
					'Parent class of name [' . $className . '] is not available,' .
						'is used by observance of type [' .
						\granam\Observances_Utilities::OBSERVANCE_INTERFACE . ']',
					\granam\Exception::ACCESS_READING
				);
			}

			throw new \granam\Exception(
				'Parent class of name "' . $className . '" is not available',
				\granam\Exception::ACCESS_READING
			);
		}

		if (!empty($interfaceNames)) {
			foreach ((array)$interfaceNames as $interfaceName) {
				if (!\granam\ObjectUtilities::isInterfaceAvailable($interfaceName)) {
					if (\granam\Observances_Utilities::isAvailable($interfaceName)) {
						throw new \granam\Exception(
							'Interface of name [' . $interfaceName . '] is not available,' .
								'is used by observance of type [' .
								\granam\Observances_Utilities::OBSERVANCE_CLASS . ']',
							\granam\Exception::ACCESS_READING
						);
					}

					throw new \granam\Exception(
						'Interface of name [' . $interfaceName . '] is not available',
						\granam\Exception::ACCESS_READING
					);
				}
			}
		}

		$newClassCode = '';
		$newClassNamespace = \granam\Observances_Utilities::extractNamespace($className);
		if (!empty($newClassNamespace)) { // namespace as first part of new class, if any
			$newClassCode .= 'namespace ' . ltrim($newClassNamespace, '\\') . ";\n";
		}

		// adding name of new class itself
		$newClassCode .=
			'class ' . \granam\Observances_Utilities::removeNamespace($className);
		if (!empty($parenClassName)) { // parent class, if any
			$newClassCode .= ' extends \\' . ltrim($parenClassName, '\\');
		}

		if (!empty($interfaceNames)) { // interfaces, if any
			$newClassCode .= ' implements ';
			$delimiter = '';
			foreach ((array)$interfaceNames as $interfaceName) {
				$newClassCode .= $delimiter . '\\' . ltrim($interfaceName, '\\');
				$delimiter = ',';
			}
		}

		$newClassCode .= ' {}'; // building class body is not supported
		if (FALSE === eval($newClassCode)) {
			throw new \granam\Exception(
				'Building new class of name [' . $className . '] fails'
			);
		}

		// check if built class finaly exists
		return \granam\Observances_Utilities::isAvailable($className);
	}

	/**
	 * @param object $object
	 * @param sring $methodName
	 * @return bool
	 */
	public static function existsPublicDynamicMethod(object $object, $methodName)
	{
		if (!method_exists($object, $methodName)) {

			return FALSE;
		}

		$reflection = new \ReflectionClass($object);
		$reflectionMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
		if (empty($reflectionMethods)) {

			return FALSE;
		}

		$methods = array();
		foreach ($reflectionMethods as $publicReflectionMethod) {
			$methods[] = $publicReflectionMethod->name;
		}

		$publicStaticReflectionMethods = $reflection->getMethods(
			\ReflectionMethod::IS_PUBLIC
			& \ReflectionMethod::IS_STATIC
		);
		if (empty($publicStaticReflectionMethods)) {
			$publicDynamicMethods = $methods;
		} else {
			if (count($reflectionMethods) === count($publicStaticReflectionMethods)) {

				return FALSE; // every method is static
			}

			$publicStaticMethods = array();
			foreach ($publicStaticReflectionMethods as $publicStaticReflectionMethod) {
				$publicStaticMethods[] = $publicStaticReflectionMethod->name;
			}

			$publicDynamicMethods = array_diff($methods, $publicStaticMethods);
		}

		return in_array($methodName, $publicDynamicMethods, TRUE);
	}

	/**
	 * @param object $object
	 * @param sring $methodName
	 * @return bool
	 */
	public static function existsProtectedDynamicMethod(object $object, $methodName)
	{
		if (!method_exists($object, $methodName)) {

			return FALSE;
		}

		$reflection = new \ReflectionClass($object);
		$reflectionMethods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
		if (empty($reflectionMethods)) {

			return FALSE;
		}

		$methods = array();
		foreach ($reflectionMethods as $publicReflectionMethod) {
			$methods[] = $publicReflectionMethod->name;
		}

		$publicStaticReflectionMethods = $reflection->getMethods(
			\ReflectionMethod::IS_PROTECTED
			& \ReflectionMethod::IS_STATIC
		);
		if (empty($publicStaticReflectionMethods)) {
			$publicDynamicMethods = $methods;
		} else {
			if (count($reflectionMethods) === count($publicStaticReflectionMethods)) {

				return FALSE; // every method is static
			}

			$publicStaticMethods = array();
			foreach ($publicStaticReflectionMethods as $publicStaticReflectionMethod) {
				$publicStaticMethods[] = $publicStaticReflectionMethod->name;
			}

			$publicDynamicMethods = array_diff($methods, $publicStaticMethods);
		}

		return in_array($methodName, $publicDynamicMethods, TRUE);
	}

	public static function existsPublicMethod($object, $methodName)
	{
		if (!is_object($object)) {
			throw new \granam\Exception(
				'Given item is not a object, but [' . gettype($object) . ']',
				\granam\Exception::CONTENT_TYPE
			);
		}

		return in_array($methodName, get_class_methods($object), TRUE);
	}

	/**
	 * @param object $object
	 * @param sring $methodName
	 * @return bool
	 */
	public static function existsPublicProperty(object $object, $propertyName)
	{
		return in_array(
			$propertyName,
			self::getObjectPublicPropertiesNames($object),
			TRUE
		);
	}

	/**
	 * @param object $object
	 * @param sring $methodName
	 * @return bool
	 */
	public static function existsPublicDynamicProperty(object $object, $propertyName)
	{
		return in_array(
			$propertyName,
			self::getObjectPublicDynamicPropertiesNames($object),
			TRUE
		);
	}

	/**
	 * @param object $object
	 * @param sring $methodName
	 * @return bool
	 */
	public static function existsPublicStaticProperty(object $object, $propertyName)
	{
		return in_array(
			$propertyName,
			self::getObjectPublicStaticPropertiesNames($object),
			TRUE
		);
	}

	/**
	 * @param object $object
	 * @return array
	 */
	public static function getObjectPublicDynamicPropertiesNames(object $object)
	{
		return array_keys(get_object_vars($object));
	}

	/**
	 * @param object $object
	 * @return array
	 */
	public static function getObjectPublicPropertiesNames(object $object)
	{
		return array_keys(get_class_vars(get_class($object)));
	}

	/**
	 * @param object $object
	 * @return array
	 */
	public static function getObjectPublicStaticPropertiesNames(object $object)
	{
		return array_diff(
			self::getObjectPublicPropertiesNames($object),
			self::getObjectPublicDynamicPropertiesNames($object)
		);
	}

	public static function getCallingClass($calledObjectOrClassName, $calledMethodName)
	{
		if (is_object($calledObjectOrClassName)) {
			$calledClassName = get_class($calledObjectOrClassName);
		} elseif (is_string($calledObjectOrClassName)) {
			if (!class_exists($calledObjectOrClassName)) {
				throw new \granam\Exception(
					'Given class of name [' . $calledObjectOrClassName .
						'] is not available so is not trackable',
					\granam\Exception::CONTENT_VALUE | \granam\Exception::PROCESS_STATE
				);
			}

			$calledClassName = $calledObjectOrClassName;
		}


		$backtrace = debug_backtrace(FALSE);
		$stepInHistory = 1;
		$calledClassFound = FALSE;
		$calledMethodFound = FALSE;
		while (isset($backtrace[$stepInHistory])) {
			if (!$calledClassFound) {
				if (isset($backtrace[$stepInHistory]['class'])
				&& $backtrace[$stepInHistory]['class'] == $calledClassName) { // actual
				// record of history is about called class, historicaly previous record
				// should be wanted calling class
					$calledClassFound = TRUE;
				}
			}

			if (!$calledMethodFound) {
				if (isset($backtrace[$stepInHistory]['function'])
				&& $backtrace[$stepInHistory]['function'] == $calledMethodName) { // actual
				// record of history is about called method, historicaly previous record
				// should be wanted calling class
					$calledMethodFound = TRUE;
				}
			}

			$stepInHistory++;
			if ($calledClassFound && $calledMethodFound) {
				if (!isset($backtrace[$stepInHistory]['class'])) {

					return FALSE; // there was no program step before or was not preformed
					// by a class
				}

				return $backtrace[$stepInHistory]['class']; // name of class which
				// has called given method from given class
			}
		}

		if (!$calledClassFound) {
			throw new \granam\Exception(
				'Given class of name [' . $calledClassName .
					'] has not been called in current code flow',
				\granam\Exception::CONTENT_VALUE | \granam\Exception::PROCESS_STATE
			);
		}

		if (!$calledMethodFound) {
			throw new \granam\Exception(
				'Given method of name [' . $calledClassName . '::' . $calledMethodName .
					'] has not been called in current program flow',
				\granam\Exception::CONTENT_VALUE | \granam\Exception::PROCESS_STATE
			);
		}

		return FALSE; // no class has called given class method
	}
}