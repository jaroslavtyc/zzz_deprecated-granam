<?php
namespace granam;

abstract class	Object {

	private $alreadyKnownPublicGetters = array();
	private $alreadyKnownProtectedGetters = array();
	private $alreadyKnownPublicSetters = array();
	private $alreadyKnownProtectedSetters = array();
	private $constructorCallInformations;
	private $destructorCallInformations;

	/**
	 * "Call me from child to prohibit repeated call"
	 */
	protected function __construct()
	{
		if (!isset($this->constructorCallInformations)) {
			$this->constructorCallInformations = debug_backtrace(FALSE);
		} else {
			throw new Exception(
				'Constructor of class [' . get_class($this) .
					'] has been already called' .
					(isset($this->constructorCallInformations['file'])
						? ' in file [' . $this->constructorCallInformations['file'] . ']'
						: '') .
					(isset($this->constructorCallInformations['line'])
						? ' on line [' . $this->constructorCallInformations['line'] . ']'
						: ''),
					Exception::ACCESS_EXECUTION
			) ;
		}
	}

	/**
	 * Destructor has to be public.
	 * Calling destructor is allowed only once, respective directly calling of
	 * __destruct is prohibited
	 */
	public function __destruct()
	{
		if (!isset($this->destructorCallInformations)) {
			$this->destructorCallInformations = debug_backtrace(FALSE);
		} else {
			throw new Exception(
				'Destructor of class [' . get_class($this) .
					'] has been already called' .
					(isset($this->destructorCallInformations['file'])
						? ' in file [' . $this->destructorCallInformations['file'] . ']'
						: '') .
					(isset($this->destructorCallInformations['line'])
						? ' on line [' . $this->destructorCallInformations['line'] . ']'
						: ''),
				Exception::ACCESS_EXECUTION | Exception::PROCESS_STATE
			);
		}
	}

	final public function __call($methodName, $arguments) // dynamically built
	// methods are not allowed
	{
		throw new Exception(
			'Method of name [' . $methodName . '] does not exists in class [' .
				get_class($this) . ']',
			Exception::ACCESS_EXECUTION
		);
	}

	/**
	 * If is required property on this object, which is out of actual visible scope,
	 *	magic __get is called.
	 *	If property has been required from public scope and public getter exists,
	 *	his value is returned.
	 *	If requirement origin from protected scope (from child or parent, so property
	 * has to be private to invoke __get) and protected or weaker getter exists,
	 * his value is returned.
	 *
	 * @param string $propertyName
	 * @throws Exception
	 * @return mixed
	 */
	final public function __get($propertyName)
	{
		$getterMethodName = 'get' . ucfirst($propertyName);
		if ($this->isPublicGetterAlreadyKnown($getterMethodName)) {

			return $this->$getterMethodName();
		}

		if (!method_exists($this, $getterMethodName)) {
			throw new Exception(
				'Property of name [' . $propertyName .
				'] is not visible or does not exists',
				Exception::ACCESS_READING | Exception::CONTENT
			);
		}

		if (Objects_Utilities::existsPublicMethod($this, $getterMethodName)) {
			$this->addAlreadyKnownPublicGetter($getterMethodName);

			return $this->$getterMethodName();
		}

		$backtrace = debug_backtrace(FALSE);
		if (!isset($backtrace[1]) // information about caller does not exists
		|| !isset($backtrace[1]['class']) // or caller is not a class / object
		|| (!is_a($this, $backtrace[1]['class']) // or caller is not actual object
			&& !is_subclass_of($this, $backtrace[1]['class'])) // neither parent
		) { // getter is tried to be accessed from public scope
			throw new Exception( // method is not public, but caller is not
			// from this lineage
				'Required property of name [' . $propertyName .
					'] is not visible from public scope',
				Exception::ACCESS_READING
			);
		}

		if ($this->isProtectedGetterAlreadyKnown($getterMethodName)) {

			return $this->$getterMethodName();
		}

		if (!$this->hasMethod($getterMethodName)) { // required getter does not
		// exists in actual scope - check twice if no getter is set in Object class
		// otherwise will be detected as available getter for descendant as well
			throw new Exception(
				'Required property of name [' . $propertyName .
					'] is not visible from class [' . $backtrace[1]['class'] . ']',
				Exception::ACCESS_READING
			);
		}

		$this->addAlreadyKnownProtectedGetter($getterMethodName);

		return $this->$getterMethodName(); // getter should be accessed from protected
		// scope
	}

	final public function __set($propertyName, $valueToSet)
	{
		$setterMethodName = 'set' . ucfirst($propertyName);
		if ($this->isPublicSetterAlreadyKnown($setterMethodName)) {

			return $this->$setterMethodName($valueToSet);
		}

		if (!method_exists($this, $setterMethodName)) {
			throw new Exception(
				'Property of name [' . $propertyName .
				'] is not writable or does not exists',
				Exception::ACCESS_WRITING | Exception::CONTENT
			);
		}

		if (\granam\Objects_Utilities::existsPublicMethod(
				$this, $setterMethodName
			)
		) {
			$this->addAlreadyKnownPublicSetter($setterMethodName);

			return $this->$setterMethodName($valueToSet);
		}

		$backtrace = debug_backtrace(FALSE);
		if (!isset($backtrace[1]) // information about caller does not exists
		|| !isset($backtrace[1]['class']) // or caller is not a class / object
		|| (!is_a($this, $backtrace[1]['class']) // or caller is not actual object
			&& !is_subclass_of($this, $backtrace[1]['class'])) // neither parent
		) { // setter is tried to be accessed from public scope
			throw new Exception( // method is not public, but caller is not
			// from this lineage
				'Required property of name [' . $propertyName .
					'] is not writable from public scope',
				Exception::ACCESS_WRITING
			);
		}

		if ($this->isProtectedSetterAlreadyKnown($setterMethodName)) {

			return $this->$setterMethodName($valueToSet);
		}

		if (!$this->hasMethod($setterMethodName)) { // required setter does not
		// exists in actual scope - check twice if no setter is set in Object class
		// otherwise will be detected as available setter for descendant as well
			throw new Exception(
				'Required property of name [' . $propertyName .
					'] is not writable from child class [' . $backtrace[1]['class'] . ']',
				Exception::ACCESS_WRITING
			);
		}

		$this->addAlreadyKnownProtectedSetter($setterMethodName);

		return $this->$setterMethodName($valueToSet); // setter should be accessed
		// from protected scope
	}

	final public function __isset($propertyName)
	{
		$getterMethodName = 'get' . ucfirst($propertyName);
		if ($this->isPublicGetterAlreadyKnown($getterMethodName)) {
			return !is_null($this->$getterMethodName());
		}

		if (!method_exists($this, $getterMethodName)) {
			return FALSE;
		}

		if (Objects_Utilities::existsPublicMethod($this, $getterMethodName)) {
			$this->addAlreadyKnownPublicGetter($getterMethodName);

			return !is_null($this->$getterMethodName());
		}

		$backtrace = debug_backtrace(FALSE);
		if (!isset($backtrace[1]) // information about caller does not exists
		|| !isset($backtrace[1]['class']) // or caller is not a class / object
		|| (!is_a($this, $backtrace[1]['class']) // or caller is not actual object
			&& !is_subclass_of($this, $backtrace[1]['class'])) // neither parent
		) { // getter is tried to be accessed from public scope
			throw new Exception( // method is not public, but caller is not
			// from this lineage
				'Required property of name [' . $propertyName .
					'] is not visible from public scope',
				Exception::ACCESS_READING
			);
		}

		if ($this->isProtectedGetterAlreadyKnown($getterMethodName)) {

			return !is_null($this->$getterMethodName());
		}

		if (!$this->hasMethod($getterMethodName)) { // required getter does not
		// exists in actual scope - check twice if no getter is set in Object class
		// otherwise will be detected as available getter for descendant as well
			throw new Exception(
				'Required property of name [' . $propertyName .
					'] is not visible from class [' . $backtrace[1]['class'] . ']',
				Exception::ACCESS_READING
			);
		}

		$this->addAlreadyKnownProtectedGetter($getterMethodName);

		return is_null($this->$getterMethodName()); // getter should be accessed from
		// protected scope
	}

	final public function __unset($propertyName)
	{
		$setterMethodName = 'set' . ucfirst($propertyName);
		if ($this->isPublicSetterAlreadyKnown($setterMethodName)) {

			return $this->$setterMethodName(NULL);
		}

		if (!method_exists($this, $setterMethodName)) {
			throw new Exception(
				'Property of name [' . $propertyName .
				'] is not writable or does not exists',
				Exception::ACCESS_WRITING | Exception::CONTENT
			);
		}

		if (\granam\Objects_Utilities::existsPublicMethod(
				$this, $setterMethodName
			)
		) {
			$this->addAlreadyKnownPublicSetter($setterMethodName);

			return $this->$setterMethodName(NULL);
		}

		$backtrace = debug_backtrace(FALSE);
		if (!isset($backtrace[1]) // information about caller does not exists
		|| !isset($backtrace[1]['class']) // or caller is not a class / object
		|| (!is_a($this, $backtrace[1]['class']) // or caller is not actual object
			&& !is_subclass_of($this, $backtrace[1]['class'])) // neither parent
		) { // setter is tried to be accessed from public scope
			throw new Exception( // method is not public, but caller is not
			// from this lineage
				'Required property of name [' . $propertyName .
					'] is not writable from public scope',
				Exception::ACCESS_WRITING
			);
		}

		if ($this->isProtectedSetterAlreadyKnown($setterMethodName)) {

			return $this->$setterMethodName(NULL);
		}

		if (!$this->hasMethod($setterMethodName)) { // required setter does not
		// exists in actual scope - check twice if no setter is set in Object class
		// otherwise will be detected as available setter for descendant as well
			throw new Exception(
				'Required property of name [' . $propertyName .
					'] is not writable from child class [' . $backtrace[1]['class'] . ']',
				Exception::ACCESS_WRITING
			);
		}

		$this->addAlreadyKnownProtectedSetter($setterMethodName);

		return $this->$setterMethodName(NULL); // setter should be accessed from
		// protected scope
	}

	// ---- LINEAGE FACILITIES ----

	final protected function isPublicGetterAlreadyKnown($methodName)
	{
		return in_array($methodName, $this->alreadyKnownPublicGetters, TRUE);
	}

	final protected function isProtectedGetterAlreadyKnown($methodName)
	{
		return in_array($methodName, $this->alreadyKnownProtectedGetters, TRUE);
	}

	final protected function isPublicSetterAlreadyKnown($methodName)
	{
		return in_array($methodName, $this->alreadyKnownPublicSetters, TRUE);
	}

	final protected function isProtectedSetterAlreadyKnown($methodName)
	{
		return in_array($methodName, $this->alreadyKnownProtectedSetters, TRUE);
	}

	/**
	 * Search method in Object class scope.
	 * It will detected local private methods as well, co be careful what you are
	 * looking for.
	 *
	 * @param string $methodName
	 * @return bool
	 */
	final protected function hasMethod($methodName)
	{
		return in_array($methodName, get_class_methods($this), TRUE);
	}

	// ---- LOCAL HELPERS ----

	private function addAlreadyKnownPublicGetter($methodName)
	{
		$this->alreadyKnownPublicGetters[] = $methodName;
	}

	private function addAlreadyKnownProtectedGetter($methodName)
	{
		$this->alreadyKnownProtectedGetters[] = $methodName;
	}

	private function addAlreadyKnownPublicSetter($methodName)
	{
		$this->alreadyKnownPublicSetters[] = $methodName;
	}

	private function addAlreadyKnownProtectedSetter($methodName)
	{
		$this->alreadyKnownProtectedSetters[] = $methodName;
	}
}