<?php
namespace granam;

class Strings_Utilities extends \granam\StaticMethodsOnly {

	const LINE_ENDING_UNIX = "\n",
		LINE_ENDING_WINDOWS = "\r\n",
		LINE_ENDING_OLD_MAC = "\r";

	public static function isStringOrToStringConvertable($item)
	{
		return
			(is_scalar($item) && !is_bool($item))
			|| self::isToStringConvertableObject($item);
	}

	public static function isStringOrToStringConvertableObject($item)
	{
		return
			is_string($item)
			|| self::isToStringConvertableObject($item);
	}

	public static function isToStringConvertableObject($item)
	{
		return is_object($item)
			&& (
				is_a($item, 'ToStringConvertable') // ensured public method
				|| method_exists($item, '__toString') // unfortunately should be
				// defined with less accessibility than public
			);
	}
}