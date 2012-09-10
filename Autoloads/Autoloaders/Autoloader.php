<?php
namespace granam;

interface Autoloader {

	/**
	 * Make available required observance (class or interface)
	 *
	 * @param string $observanceName
	 */
	public function that($observanceName);
}