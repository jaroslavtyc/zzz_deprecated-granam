<?php
namespace granam;

/**
 * Allows dynamically add and analyse code
 */
class CodeAnalysis extends \granam\Object {

	protected $registeredCodeAnalyzers = array();
	protected $givenCodeRows = array();
	protected $currentRowIndex = FALSE;
	protected $lastUsedRowIndex = FALSE;

	private $positionOnActualRow = FALSE;
	private $actualRow = FALSE;
	private $rowIndex = 0;
	private $actualCodeAnalyzer = FALSE;
	private $greedyCodeAnalyzer;
	private $neverendingCodeAnalyzer;

	public function __construct()
	{
		parent::__construct();
	}

	public function registerCodeAnalyzer(CodeAnalyzer $codeAnalyzer)
	{
		if ($this->hasAnalysisStarted()) {
			throw new \granam\Exception(
				'Analysis has been already runned, additional code analyzers can not be added',
				\granam\Exception::PROCESS_STATE | \granam\Exception::SERVICE_REGISTERING
			);
		}

		if (!$this->checkNewCodeAnalyzer($codeAnalyzer)) {
			return;
		}

		$this->registeredCodeAnalyzers[spl_object_hash($codeAnalyzer)] =
			clone $codeAnalyzer; // cloning to avoid external after-changes
		if ($this->isCodeAnalyzerOpenningAnywhere(
			  $this->registeredCodeAnalyzers[spl_object_hash($codeAnalyzer)]
			)
		) {
			$this->setGreedyCodeAnalyzer(
				$this->registeredCodeAnalyzers[spl_object_hash($codeAnalyzer)]
			);
		}

		if ($this->isCodeAnalyzerNeverending(
			  $this->registeredCodeAnalyzers[spl_object_hash($codeAnalyzer)]
			)
		) {
			$this->setNeverendingCodeAnalyzer(
				$this->registeredCodeAnalyzers[spl_object_hash($codeAnalyzer)]
			);
		}
	}

	/**
	 * @return array of CodeAnalyzer objects filled by proper code
	 */
	public function getAnalysisResult()
	{
		$this->ensureAnalysis();

		return $this->getResultCodeAnalyzers();
	}

	public function addCode($code)
	{
		if (!Strings_Utilities::isStringOrToStringConvertable($code)) {
			throw new \granam\Exception(
				'Given value has to be string or to string euivalent convertable,' .
					'but is type of [' . gettype($code) .']',
				\granam\Exception::CONTENT_TYPE
			);
		}

		$newCodeRows = array();
		if (preg_match_all( // splitting code to rows
			'~' .
				'(?:[^\r\n]+)' . // required one or more any characters except
				// new line and carriage return
				'(?:\n|\r\n|\r)?' . // optional any combinations of characters
				// representing new line
			'|' . // or
				'(?:[^\r\n]*)' . // optinal any characters except new line and
				// carriage return
				'(?:[\r\n]{1,2})' . // required any character combinations representing
				// new line
			'~',
			$code,
			$newCodeRows)
		) {
			foreach ($newCodeRows[0] as $codeRow) {
				$this->addCodeRow($codeRow);
			}
		}
	}

	public function isCodeAnalyzerRegistered(CodeAnalyzer $codeAnalyzer)
	{
		return isset($this->registeredCodeAnalyzers[spl_object_hash($codeAnalyzer)]);
	}

	public function getCountOfRegisteredAnalyzers()
	{
		return count($this->registeredCodeAnalyzers);
	}

	public function hasAnalysisStarted()
	{
		return FALSE !== $this->lastUsedRowIndex;
	}

	public function isWholeCodeAnalysed()
	{
		return
			$this->hasAnalysisStarted()
			&& $this->lastUsedRowIndex == (sizeof($this->givenCodeRows) - 1);
	}

	// ---- LINEAGE FACILITIES ----

	protected function getResultCodeAnalyzers()
	{
		$resultCodeAnalyzers = array();
		foreach ($this->registeredCodeAnalyzers as $registeredCodeAnalyzer) {
			if ($registeredCodeAnalyzer->isAnalyzerUsed()) {
				$resultCodeAnalyzers[] = clone $registeredCodeAnalyzer;
			}
		}

		return $resultCodeAnalyzers;
	}

	protected function isPositionOnEndOfActualRow()
	{
		return $this->positionOnActualRow >= (strlen($this->actualRow) -1);
	}

	protected function getGreedyCodeAnalyzer()
	{
		return $this->greedyCodeAnalyzer;
	}

	protected function getNeverendingCodeAnalyzer()
	{
		return $this->neverendingCodeAnalyzer;
	}

	// ---- LOCAL HELPERS ----

	private function checkNewCodeAnalyzer($newCodeAnalyzer)
	{
		if ($this->isCodeAnalyzerRegistered($newCodeAnalyzer)) {
			trigger_error(
				'Code analyzer [' . get_class($newCodeAnalyzer) .
					' is already registered' .
					($newCodeAnalyzer === $this->getGreedyCodeAnalyzer()
					  ? ' as greedy code analyzer'
					  : ''),
				E_USER_NOTICE
			);

			return FALSE;
		}

		if (count($newCodeAnalyzer->getCodeOpenningTags()) < 1) {
			throw new \granam\Exception(
				'Given code analyzer [' .
					get_class($newCodeAnalyzer) .
					'] has no start tags, which are vital for analysis',
				\granam\Exception::CONTENT_VALUE | \granam\Exception::SERVICE_REGISTERING
			);
		}

		foreach ($this->registeredCodeAnalyzers as $registeredCodeAnalyzer) {
			foreach ($registeredCodeAnalyzer->getCodeOpenningTags() as $usedOpenningTag) {
				if (in_array($usedOpenningTag, $newCodeAnalyzer->getCodeOpenningTags(), TRUE)) {
					throw new Exception(
						'Openning tag [' . $usedOpenningTag . '] is already used by [' .
							get_class($registeredCodeAnalyzer) . ']',
						granam\Exception::CONTENT_VALUE | granam\Exception::SERVICE_REGISTERING
					);
				}
			}
		}

		return TRUE;
	}

	private function setActualRow($actualRow)
	{
		$this->positionOnActualRow = 0;
		$this->actualRow = $actualRow;
	}

	private function setCodeStartPosition()
	{
		if ($this->isPostitionOutOfRow()) {
			$this->codeStartPosition = FALSE;
		} elseif (!$this->getInsideCode()) {
			$scriptOpenningTagAndPosition = $this->getScriptOpenningTagAndPosition();
			if (FALSE === $scriptOpenningTagAndPosition) {
				$this->setPositionOutOfRow();
				$this->setCodeStartPosition(); // calling this method again in new state
			} else {
				$this->setPositionAfterTagWithPosition(
					$scriptOpenningTagAndPosition['tag'],
					$scriptOpenningTagAndPosition['position']
				);
				$this->setInsideCode(TRUE); // we are in code
				$this->codeStartPosition = $scriptOpenningTagAndPosition['position'];
				// the code start on this row
			}
			// @TODO move singeline and multiline comment check into PHP analyzer
		} elseif ($this->inSinglelineComment) {
			$scriptClosingTagAndPosition = $this->getScriptClosingTagAndPosition();
			if (FALSE === $scriptClosingTagAndPosition) { // single line comment is not
			// interrupted by closing tag
				$this->setPositionOutOfRow();
				$this->setCodeStartPosition(); // calling this method again in new state
			} else {
				$this->setInsideCode(FALSE); // we are out of code
				$this->setPositionAfterTagWithPosition(
					$scriptClosingTagAndPosition['tag'],
					$scriptClosingTagAndPosition['position']
				);
				$this->setCodeStartPosition(); // calling this method again in new state
			}
		} elseif ($this->inMultilineComment) {
			$multilineCommentEndTagAndPosition =
				$this->getMultilineCommentEndTagAndPosition();
			if (FALSE === $multilineCommentEndTagAndPosition) { // multiline comment
			//does not ends in this row
				$this->setPositionOutOfRow();
				$this->setCodeStartPosition(); // calling this method again in new state
			} else {
				$this->inMultilineComment = FALSE;
				$this->setPositionAfterTagWithPosition(
					$multilineCommentEndTagAndPosition['tag'],
					$multilineCommentEndTagAndPosition['position']
				);
				$this->setCodeStartPosition(); // calling this method again in new state
			}
		} else {
			$this->codeStartPosition = $this->positionOnActualRow;
		}
	}

	private function chooseAndSetActualCodeAnalyzer()
	{
		if (!is_object($this->actualCodeAnalyzer)) {
			$closestCodeStartPosition = FALSE;
			$codeAnalyzerWithClosestStartPosition = FALSE;
			foreach ($this->registeredCodeAnalyzers as $registeredCodeAnalyzer) {
				$foundCodeStartPositionsition =
					$registeredCodeAnalyzer->determineCodeStartPosition(
						substr($this->actualRow,$this->positionOnActualRow)
					);
				if (is_int($foundCodeStartPositionsition)
				 && (!is_int($closestCodeStartPosition)
					  || ($foundCodeStartPositionsition < $closestCodeStartPosition)
					 )// no previous code start has been found or current found
					 // code begins closer than previous
				) {
					if ($registeredCodeAnalyzer === $this->getGreedyCodeAnalyzer()) {
						$this->getGreedyCodeAnalyzer()->addCodePart(
							substr(
								$this->actualRow,
								$this->positionOnActualRow,
								$closestCodeStartPosition // it is de facto lenght
								// of closer unspecified code
							)
						);
						$this->positionOnActualRow +=
							$this->getGreedyCodeAnalyzer()->getLengthOfLastGivenCode();
					}
					$closestCodeStartPosition = $foundCodeStartPositionsition;
					$codeAnalyzerWithClosestStartPosition = $registeredCodeAnalyzer;
					if ($closestCodeStartPosition === 0) {
						break; // no other code start could be closer
					}
				}
			}

			if (is_a($closestCodeStartPosition, 'granam\CodeAnalysis')) {
				$this->actualCodeAnalyzer = $codeAnalyzerWithClosestStartPosition;
			}
		}

		if (!is_object($this->actualCodeAnalyzer)) {
			throw new Exception(
				'No analyzer has recognized actual part of code',
				Exception::CONTENT_VALUE
			);
		}
	}

	private function ensureAnalysis()
	{
		if (!$this->isWholeCodeAnalysed()) {
			if (count($this->givenCodeRows) < 1) {
				throw new \granam\Exception(
					'No code to analyse is known, analysis could not be performed',
					\granam\Exception::PROCESS_STATE | \granam\Exception::SERVICE_LAUNCHING
				);
			}

			if (!$this->hasAnalysisStarted()) {
				$this->moveGreedyAnalyzerToEnd(); // ... if any
			}

			$this->analyse();
		}
	}

	private function analyse()
	{
		while ($this->rowIndex < count($this->givenCodeRows)) { // from last row
		// to newest one
			$this->setActualRow($this->givenCodeRows[$this->rowIndex]);
			while (!$this->isPositionOnEndOfActualRow()) {
				$this->chooseAndSetActualCodeAnalyzer();
				$this->actualCodeAnalyzer->addCodePart( // internaly adds only known code,
					// if code ends in given string, trailing unknown code is ignored
					$this->actualRow,
					$this->positionOnActualRow
				);
				$this->positionOnActualRow +=
					$this->actualCodeAnalyzer->getLengthOfLastAcceptedCode();
				if (!$this->actualCodeAnalyzer->isInCode()
				 || $this->actualCodeAnalyzer === $this->getNeverendingCodeAnalyzer()
				// generic code analyzer is greedy - every code consider as "his" -
				//  so has to be cut off externally
				) {
					$this->actualCodeAnalyzer = FALSE;
				}
			}

			$this->rowIndex++;
		}
	}

	private function addCodeRow($givenCodeRow)
	{
		$this->givenCodeRows[] = $givenCodeRow;
	}

	/**
	 * Important for chooseAndSetActualCodeAnalyzer() propper functionality
	 */
	private function moveGreedyAnalyzerToEnd()
	{
		foreach ($this->registeredCodeAnalyzers as $hash => $registeredCodeAnalyzer) {
			if ($this->isCodeAnalyzerGreedy($registeredCodeAnalyzer)) {
				unset($this->registeredCodeAnalyzers[$hash]);
				$this->registerCodeAnalyzer($registeredCodeAnalyzer); // re-registering
				// analyzer with "greedy", everywhere matching openning tag to move him
				// to very end of registered analyzers list
				break; // there can be only one analyzer with all-matching openning tag
			}
		}
	}

	private function isCodeAnalyzerGreedy($codeAnalyzer)
	{
		return in_array(
			CodeAnalyses_Utilities::TAG_MATCHING_EVERYWHERE,
			$codeAnalyzer->getCodeOpenningTags(),
			TRUE
		);
	}

	private function setGreedyCodeAnalyzer($codeAnalyzer)
	{
		$this->greedyCodeAnalyzer = $codeAnalyzer;
	}

	private function isCodeAnalyzerNeverending($codeAnalyzer)
	{
		return count($codeAnalyzer->getCodeClosingTags()) === 0;
	}

	private function setNeverendingCodeAnalyzer($codeAnalyzer)
	{
		$this->neverendingCodeAnalyzer = $codeAnalyzer;
	}
}