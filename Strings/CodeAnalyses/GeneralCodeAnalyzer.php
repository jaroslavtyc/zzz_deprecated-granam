<?php
namespace granam;

final class GeneralCodeAnalyzer extends \granam\CodeAnalyzer {

	public function __construct()
	{
		parent::__construct(
			array(CodeAnalyses_Utilities::TAG_MATCHING_EVERYWHERE),
			array() // no end at all
		);
	}
}