<?php
namespace granam;

 /**
 * Wrapper for standalone methods.
 * (every class with "Utilities" in name is a helper with static methods only)
 *
 * @author Jaroslav Týc <mail@jaroslavtyc.com>
 */
abstract class StaticMethodsOnly extends \granam\Object {

	/**
	 * Using constructor is forbidden in static-only class
	 */
	final protected function __construct(){
		throw new Exception(
			'Class [' . get_called_class() . '] as child of class [' . __CLASS__ .
				'] could not be instantiated',
			Exception::ACCESS_EXECUTION
		);
	}
}