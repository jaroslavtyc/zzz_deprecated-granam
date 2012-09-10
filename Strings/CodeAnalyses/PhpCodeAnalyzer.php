<?php
namespace granam;

class PhpCodeAnalyzer extends CommentsPrioritizedCodeAnalyzer {

	final public function __construct()
	{
		parent::__construct(array('<?php','<?'), '?>'); // IMPORTANT!! dodelat pdoporu vice tagu stejneho vyznamu
		$this->addInternalCodeAnalyzer(new PhpMultilineCommentCodeAnalyzer);
		$this->addInternalCodeAnalyzer(new PhpSinglelineCommentCodeAnalyzer);
	}

	final public function determineCodeStartPosition($codeToSearchIn)
	{
		if ((string)$codeToSearchIn === $this->lastCodeToDetermineStartPosition) {
			return parent::determineCodeStartPosition($codeToSearchIn);
		}

		$positionInCodeToSearchIn = 0;
		while(TRUE) { // only return can end the cyclus
			$codeChunkToSearchIn = substr($codeToSearchIn, $positionInCodeToSearchIn);
			$closestTagAndPosition =
				\granam\CodeAnalyses_Utilities::getClosestTagAndPosition(
					$this->getStartTags(),
					$codeChunkToSearchIn
				);
			if (empty($closestTagAndPosition)) { // no tag found
				return parent::determineCodeStartPosition($codeChunkToSearchIn);
			}

			if (preg_match(
					'~' .
						'(?:' . preg_quote($closestTagAndPosition['tag']) . ')' . // possible
						// openning tag
						'[\s|$]' . // appending white character or ending of row
					'~i', // case insensitive
					$codeChunkToSearchIn
				)
			) { // validity of PHP code starting position has been confirmed
				return parent::determineCodeStartPosition($codeChunkToSearchIn);
			}

			$positionInCodeToSearchIn +=
				$closestTagAndPosition['position'] + 1 + strlen($closestTagAndPosition['tag']);
		}
	}

	final public function determineCodeEndPosition($codeToSearchIn)
	{
		$commentaryTagGroups = array(
			$this->getSinglelineCommentOpenningTags(),
			$this->getMultilineCommentBorderTags()
		);

		foreach ($commentaryTagGroups as $commentaryTagGroup) {
			foreach ($commentaryTagGroup as $commentaryTag) {
				$comentaryTagPosition =
					\granam\CodeAnalyses_Utilities::getClosestTagPosition(
						$commentaryTag->getOpenningTag(),
						$codeToSearchIn
					);
			}
		}
		// if code is not in commentary
	}
}