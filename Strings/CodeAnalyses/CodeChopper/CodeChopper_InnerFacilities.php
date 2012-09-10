<?php
namespace granam;

class CodeChopper_InnerFacilities extends \granam\Object {

	protected $skipSinglelineComment = FALSE;
	protected $skipMultilineComment = FALSE;
	private $codeRows = array();
	private $toRowsSplittedCode;
	private $row;
	private $choppingPerformed = FALSE;
	private $codeChunks = array();
	private $positionOnRow = 0;
	private $insideCode = FALSE;
	private $codeStartPosition = FALSE;
	private $codeEndPosition = FALSE;

	protected function ensureChopping()
	{
		if (!$this->choppingPerformed) {
			$this->chopOut();
		}
	}

	// ---- LOCAL HELPERS ----

	private function chopOut()
	{
		$this->setToRowsSplittedCode();
		if (!empty($this->toRowsSplittedCode)) {
			$codeChunk = '';
			$this->setRow();
			while (FALSE !== $this->row) {
				do {
					$this->setCodeStartPosition();
					$this->setCodeEndPosition();
					$this->setCodeLength();
					if ($this->rowCodeLength > 0) {
						$codeRow = substr($row, $this->codeStartPosition, $rowCodeLength);
						if (trim($codeRow) !== '') { // row composed from white characters
						// is irelevant
							if (FALSE === $lastKeywordFound) { // still searching
							// for last keyword
								foreach ($lastKeywords as $lastKeyword) {
									$lastKeywordFound = (bool)preg_match(
										'~' .
											'(?:^|\s)' . // white character or beginning of string
											$lastKeyword . // searched keyword
											'(?:' . // group not cached in matches, does not apply
											// to next parenthesised group
												'\s+' . // prepending white characters
												'([a-z_\\\\]+)' . // possible value of keyword, will
												// be captured separately into matches
											')?' . // optional
											'(?:\s|$)' . // white character or end of string
										'~i', // case insensitive
										$codeRow,
										$possibleLastKeywordValueMatches
									);
									if ($lastKeywordFound) {
										$lastKeywordValueFound =
											!empty($possibleLastKeywordValueMatches[1]);
										break;
									}
								}
							} else { // last keyword has been already found, keyword value
							// not yet
								$lastKeywordValueFound = (bool)preg_match(
									'~' .
										'[\s^]' . // white character or beginning of string
										'[a-z_\\\\]+' . // possible value of keyword
										'[\s$]' . // white character or end of string
									'~i', // case insensitive
									$codeRow
								);
							}

							$fetchedCode .= $codeRow;
						}
					}
				} while (
					FALSE !== $this->codeStartPosition // code start has been included in
					// current row part
					&& FALSE !== $this->codeEndPosition // code end has been included
					// too in current row part (so there is possible another start)
				);
				$this->setRow();
			}
		}
	}

	private function setCodeLength()
	{
		if (!is_numeric($this->codeStartPosition)) { // code is not on this row
			$this->rowCodeLength = 0;
		} elseif (is_numeric($this->codeEndPosition)) {
			$this->rowCodeLength = $this->codeEndPosition - $this->codeStartPosition;
		} else { // code does not end on this row
			$this->rowCodeLength = strlen($this->row) - $this->codeStartPosition;
		}
	}

	private function setToRowsSplittedCode()
	{
		$this->toRowsSplittedCode = preg_split('~\R~', $this->code);
	}

		/**
	 * @return string|bool
	 */
	private function setRow()
	{
		$this->positionOnRow = 0;
		$this->setInSinglelineComment(FALSE);
		$this->row = next($this->toRowsSplittedCode);
		if (FALSE === $this->row) {

			return FALSE;
		}

		$this->rowNumber++;
	}

	/**
	 * @return int|bool
	 */
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
			$this->codeStartPosition = $this->positionOnRow;
		}
	}

	private function setCodeEndPosition()
	{
		if (!is_numeric($this->codeStartPosition)) { // code is on this row
			$this->codeEndPosition = FALSE;
		} elseif ($this->isPostitionOutOfRow()) {
			$this->codeEndPosition = FALSE;
		} elseif (!$this->getInsideCode()) {
			throw new \granam\Exception(
				'Pointer is not on code, code end position could not be determined',
				\granam\Exception::PROCESS_STATE
			);
		} elseif ($this->inSinglelineComment) {
			throw new \granam\Exception(
				'Pointer is inside of singleline comment.' .
					'End of code should be determined only from code itself.',
				\granam\Exception::PROCESS_STATE
			);
		} elseif ($this->inMultilineComment) {
			throw new \granam\Exception(
				'Pointer is inside of multiline comment.' .
					'End of code should be determined only from code itself.',
				\granam\Exception::PROCESS_STATE
			);
		} else {
			$endOfCodeAndPosition =
				\granam\ScriptFileAnalyzerUtilities::getClosestTagAndPosition(
					array_merge(
						\granam\ScriptFileAnalyzerUtilities::getSinglelineCommentStartTags(),
						\granam\ScriptFileAnalyzerUtilities::getMultilineCommentStartTags(),
						\granam\ScriptFileAnalyzerUtilities::getScriptClosingTags()
					),
					$this->row,
					$this->positionOnRow
				);
			if (FALSE === $endOfCodeAndPosition) { // code does not end on this row
				$this->codeEndPosition = FALSE;
			} else {
				$this->setPositionAfterTagWithPosition(
					$endOfCodeAndPosition['tag'],
					$endOfCodeAndPosition['position']
				);
				if (\granam\ScriptFileAnalyzerUtilities::isASinglelineCommentStartTag(
						$endOfCodeAndPosition['tag']
					)
				) {
					$this->setInSinglelineComment(TRUE);
				} elseif (\granam\ScriptFileAnalyzerUtilities::isAMultilineCommentStartTag(
						$endOfCodeAndPosition['tag']
					)
				) {
					$this->inMultilineComment = TRUE;
				} elseif (\granam\ScriptFileAnalyzerUtilities::isAScriptClosingTag(
						$endOfCodeAndPosition['tag']
					)
				) {
					$this->setInsideCode(FALSE);
				}

				$this->codeEndPosition = $endOfCodeAndPosition['position'];
			}
		}
	}

	/**
	 * @return int|bool
	 */
	protected function getCodeEndPosition()
	{
		return $this->codeEndPosition;
	}

	private function addResultOfAnalyze($keyword, $value)
	{
		switch (strtolower($keyword)) {
			case 'namespace':
				$this->namespace = trim($value, '\\'); // namespace without wrapping
				// backslashes
				break;
			case 'interface':
				$this->observanceType = \granam\Observances_Utilities::OBSERVANCE_INTERFACE;
				$this->observanceBasename = trim($value, '\\');
				break;
			case 'class':
				$this->observanceType = \granam\Observances_Utilities::OBSERVANCE_CLASS;
				$this->observanceBasename = trim($value, '\\');
				break;
			default:
				throw new \granam\Exception(
					'Unknown keyword [' . $keyword . ']',
					\granam\Exception::CONTENT_VALUE
				);
		}
	}

	private function getScriptFilePointer()
	{
		if (!is_resource($this->scriptFilePointer)) {
			$this->setScriptFilePointer();
		}

		return $this->scriptFilePointer;
	}

	private function setScriptFilePointer()
	{
		$this->scriptFilePointer = fopen($this->scriptFilename, 'r');
		if (FALSE === $this->scriptFilePointer) {
			throw new \granam\Exception(
				'Script file of name [] could not be opened for reading',
				\granam\Exception::ACCESS_READING
			);
		}
	}

	/**
	 * @throws \granam\Exception_AccessReading if reading file fails
	 * @param string $scriptFilename
	 */
	private function setScriptFilename($scriptFilename)
	{
		if (!is_file($scriptFilename)) {
			throw new \granam\Exception(
				'Script [' . $scriptFilename . '] has not been found',
				\granam\Exception::ACCESS_READING
			);
		}

		if (!is_readable($scriptFilename)) {
			throw new \granam\Exception(
				'Script [' . $scriptFilename . '] can not be readed, access rejected',
				\granam\Exception::ACCESS_READING
			);
		}

		$this->scriptFilename = $scriptFilename;
	}

	protected function getScriptFilename()
	{
		return $this->scriptFilename;
	}

	private function getInsideCode()
	{
		return $this->insideCode;
	}

	private function setInsideCode($status)
	{
		$this->insideCode = (bool)$status;
		if (!$this->getInsideCode()) {
			$this->setInSinglelineComment(FALSE);
		}
	}

	private function setInSinglelineComment($status)
	{
		$this->inSinglelineComment = (bool)$status;
	}

	private function setPositionAfterTagWithPosition($tag, $positionOfTag)
	{
		$this->positionOnRow = $positionOfTag + strlen($tag);
	}

	private function getSinglelineCommentStartPosition()
	{
		return \granam\ScriptFileAnalyzerUtilities::getClosestTagPosition(
			\granam\ScriptFileAnalyzerUtilities::getSinglelineCommentStartTags(),
			$this->row,
			$this->positionOnRow
		);
	}

	private function getMultilineCommentStartPosition()
	{
		return \granam\ScriptFileAnalyzerUtilities::getClosestTagPosition(
			\granam\ScriptFileAnalyzerUtilities::getMultilineCommentStartTags(),
			$this->row,
			$this->positionOnRow
		);
	}

	private function getMultilineCommentEndTagAndPosition()
	{
		return \granam\ScriptFileAnalyzerUtilities::getClosestTagAndPosition(
			\granam\ScriptFileAnalyzerUtilities::getMultilineCommentEndTags(),
			$this->row,
			$this->positionOnRow
		);
	}

	private function getScriptOpenningTagAndPosition()
	{
		$scriptOpenningTagAndPosition =
			\granam\ScriptFileAnalyzerUtilities::getClosestTagAndPosition(
			\granam\ScriptFileAnalyzerUtilities::getScriptOpenningTags(),
			$this->row,
			$this->positionOnRow
		);
		if ($scriptOpenningTagAndPosition['tag'] == '<?' &&
		 !\granam\ScriptFileAnalyzerUtilities::isShortOppeningTagAllowed()) {
			throw new \granam\Exception(
				'Shorten opening tag is not allowed. Found at script file [' .
					$this->scriptFilename . '] on row [' . $this->rowNumber . ']',
				\granam\Exception::SERVICE | \granam\Exception::CONTENT_VALUE
			);
		}

		return $scriptOpenningTagAndPosition;
	}

	private function getScriptClosingTagAndPosition()
	{
		return \granam\ScriptFileAnalyzerUtilities::getClosestTagAndPosition(
			\granam\ScriptFileAnalyzerUtilities::getScriptClosingTags(),
			$this->row,
			$this->positionOnRow
		);
	}

	private function setPositionOutOfRow()
	{
		$this->positionOnRow = strlen($this->row);
		if ($this->positionOnRow === 0) { // if row is empty string, we have to
		// move position over
			$this->positionOnRow += 1;
		}
	}

	private function isPostitionOutOfRow()
	{
		return (strlen($this->row) -1) < $this->positionOnRow;
	}
}