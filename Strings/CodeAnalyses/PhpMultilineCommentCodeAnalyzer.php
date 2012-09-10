<?php
namespace granam;

class PhpMultilineCommentCodeAnalyzer extends CodeAnalyzer {

	final public function __construct()
	{
		parent::__construct('/*', '*/');
	}
}