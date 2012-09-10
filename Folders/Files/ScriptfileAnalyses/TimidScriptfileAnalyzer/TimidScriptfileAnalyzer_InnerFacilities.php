<?php
namespace granam;

abstract class ScriptFileAnalyzer_InnerFacilities extends \granam\Object {

	private $scriptFilePointer;
	private $scriptFilename;
	private $row;
	private $rowNumber = 0;
	private $position = 0;
	private $insideCode = FALSE;
	private $inSinglelineComment = FALSE;
	private $inMultilineComment = FALSE;
	private $analysisPerformed = FALSE;

	protected function ensureAnalysis()
	{
		if (!$this->analysisPerformed) {
			$this->analyze();
		}
	}

	protected function analyze()
	{
		$lastKeywords = array('interface', 'class');
		$fetchedCode = '';
		$lastKeywordFound = FALSE;
		$lastKeywordValueFound = FALSE;
		while (FALSE !== ($row = $this->getNextRow($this->getScriptFilePointer()))
		 // not on end of file yet
		 && FALSE === $lastKeywordValueFound) { // data collecting is still needed
			//$row = trim($row); // surrounding white spaces are irelevant for this analyse
			if (trim($row) !== '') { // we do not need empty row for this kind of analyse
				$counter = 0;
				do {
					$rowCodeEndPosition = FALSE;
					$counter++;
					$rowCodeStartPosition = $this->getRowCodeStartPosition();
					if (is_numeric($rowCodeStartPosition)) { // code is on this row
						$rowCodeEndPosition = $this->getRowCodeEndPosition();
						$rowCodeLength = 0;
						if (is_numeric($rowCodeEndPosition)) {
							$rowCodeLength = $rowCodeEndPosition - $rowCodeStartPosition;
						} else { // code does not end on this row
							$rowCodeLength = strlen($row) - $rowCodeStartPosition;
						}
						if ($rowCodeLength > 0) {
							$codeRow = substr($row, $rowCodeStartPosition, $rowCodeLength);
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
					}
				} while (
					FALSE !== $rowCodeStartPosition // code start has been included in
					// current row part
					&& FALSE !== $rowCodeEndPosition // code end has been included
					// too in current row part (so there is possible another start)
					&& FALSE === $lastKeywordValueFound // and last value of keyword
					// has not been found yet
				);
			}
		}

		$foundInformations = array();
		if (preg_match_all(
			'~' .
				'(namespace|interface|class)' . // any of listed keyword
				'(?:\s+)' . // followed by one or more white characters
				'([\\\\a-z_]+)' . // followed by keyword value, composited from listed
				// characters only
			'~i', // case insensitive
			$fetchedCode,
			$foundInformations
			)
		) { // some keywords have been found
			if (isset($foundInformations[1]) && is_array($foundInformations[1])) {
				$numberOfFoundInformations = count($foundInformations[1]);
				for ($i = 0; $i < $numberOfFoundInformations; $i++) {
					if (isset($foundInformations[1][$i]) && isset($foundInformations[2][$i])) {
						$this->addResultOfAnalyze(
							$foundInformations[1][$i],
							$foundInformations[2][$i]
						);
					}
				}
			}
		}

		$this->analysisPerformed = TRUE;
	}

	/**
	 * @return string|bool
	 */
	private function getNextRow($scriptFilePointer)
	{
		$this->position = 0;
		$this->setInSinglelineComment(FALSE);
		$this->row = fgets($scriptFilePointer);
		if (FALSE === $this->row) {

			return FALSE;
		}

		$this->rowNumber++;

		return $this->row;
	}

	/**
	 * @return int|bool
	 */
	private function getRowCodeStartPosition()
	{
		if ($this->isPostitionOutOfRow()) {

			return FALSE;
		}

		if (!$this->insideCode) {
			$scriptOpenningTagAndPosition = $this->getScriptOpenningTagAndPosition();
			if (FALSE === $scriptOpenningTagAndPosition) {
				$this->setPositionOutOfRow();

				return FALSE; // the code does not start on this row
			}

			$this->setPositionAfterTagWithPosition(
				$scriptOpenningTagAndPosition['tag'],
				$scriptOpenningTagAndPosition['position']
			);
			$this->insideCode = TRUE;

			return $scriptOpenningTagAndPosition['position']; // the code start on this row
		}

		if ($this->inSinglelineComment) {
			$scriptClosingTagAndPosition = $this->getScriptClosingTagAndPosition();
			if (FALSE === $scriptClosingTagAndPosition) { // single line comment is not
			// interrupted by closing tag
				$this->setPositionOutOfRow();

				return FALSE;
			}

			$this->setInsideCode(FALSE); // we are out of code
			$this->setPositionAfterTagWithPosition(
				$scriptClosingTagAndPosition['tag'],
				$scriptClosingTagAndPosition['position']
			);

			return $this->getRowCodeStartPosition(); // calling this method again
			// in new state
		}

		if ($this->inMultilineComment) {
			$multilineCommentEndTagAndPosition =
				$this->getMultilineCommentEndTagAndPosition();
			if (FALSE === $multilineCommentEndTagAndPosition) { // multiline comment
			//does not ends in this row
				$this->setPositionOutOfRow();

				return FALSE;
			}

			$this->inMultilineComment = FALSE;
			$this->setPositionAfterTagWithPosition(
				$multilineCommentEndTagAndPosition['tag'],
				$multilineCommentEndTagAndPosition['position']
			);

			return $this->getRowCodeStartPosition(); // calling this method again
			// in new state
		}

		return $this->position;
	}

	/**
	 * @return int|bool
	 */
	protected function getRowCodeEndPosition()
	{
		if ($this->isPostitionOutOfRow()) {

			return FALSE;
		}

		if (!$this->insideCode) {
			throw new \granam\Exception(
				'Pointer is not on code, code end position could not be determined',
				\granam\Exception::PROCESS_STATE
			);
		}

		if ($this->inSinglelineComment) {
			throw new \granam\Exception(
				'Pointer is inside of singleline comment.' .
					'End of code should be determined only from code itself.',
				\granam\Exception::PROCESS_STATE
			);
		}

		if ($this->inMultilineComment) {
			throw new \granam\Exception(
				'Pointer is inside of multiline comment.' .
					'End of code should be determined only from code itself.',
				\granam\Exception::PROCESS_STATE
			);
		}

		$endOfCodeAndPosition =
			\granam\ScriptFileAnalyzerUtilities::getClosestTagAndPosition(
				array_merge(
					 \granam\ScriptFileAnalyzerUtilities::getSinglelineCommentStartTags(),
					 \granam\ScriptFileAnalyzerUtilities::getMultilineCommentStartTags(),
					 \granam\ScriptFileAnalyzerUtilities::getScriptClosingTags()
				),
				$this->row,
				$this->position
			);
		if (FALSE === $endOfCodeAndPosition) { // code does not end on this row

			return FALSE;
		}

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

		return $endOfCodeAndPosition['position'];
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

	private function setInsideCode($status)
	{
		$this->insideCode = (bool)$status;
		if (!$this->insideCode) {
			$this->setInSinglelineComment(FALSE);
		}
	}

	private function setInSinglelineComment($status)
	{
		$this->inSinglelineComment = (bool)$status;
	}

	private function setPositionAfterTagWithPosition($tag, $positionOfTag)
	{
		$this->position = $positionOfTag + strlen($tag);
	}

	private function getSinglelineCommentStartPosition()
	{
		return \granam\ScriptFileAnalyzerUtilities::getClosestTagPosition(
			\granam\ScriptFileAnalyzerUtilities::getSinglelineCommentStartTags(),
			$this->row,
			$this->position
		);
	}

	private function getMultilineCommentStartPosition()
	{
		return \granam\ScriptFileAnalyzerUtilities::getClosestTagPosition(
			\granam\ScriptFileAnalyzerUtilities::getMultilineCommentStartTags(),
			$this->row,
			$this->position
		);
	}

	private function getMultilineCommentEndTagAndPosition()
	{
		return \granam\ScriptFileAnalyzerUtilities::getClosestTagAndPosition(
			\granam\ScriptFileAnalyzerUtilities::getMultilineCommentEndTags(),
			$this->row,
			$this->position
		);
	}

	private function getScriptOpenningTagAndPosition()
	{
		$scriptOpenningTagAndPosition =
			\granam\ScriptFileAnalyzerUtilities::getClosestTagAndPosition(
			\granam\ScriptFileAnalyzerUtilities::getScriptOpenningTags(),
			$this->row,
			$this->position
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
			$this->position
		);
	}

	private function setPositionOutOfRow()
	{
		$this->position = strlen($this->row);
		if ($this->position === 0) { // if row is empty string, we have to
		// move position over
			$this->position += 1;
		}
	}

	private function isPostitionOutOfRow()
	{
		return (strlen($this->row) -1) < $this->position;
	}
}