<?php
namespace granam;

class PhpSinglelineCommentCodeAnalyzer extends CodeAnalyzer {

	final public function __construct()
	{
		parent::__construct(
			array('//', '#'),
			array(
				\granam\granam\Strings_Utilities::LINE_ENDING_WINDOWS, // because
				// windows line end contains both of following line ends so windows
				// line end has to be the first one to avoid inaccurate results
				\granam\Strings_Utilities::LINE_ENDING_UNIX,
				\granam\granam\Strings_Utilities::LINE_ENDING_OLD_MAC
			)
		);
	}
}