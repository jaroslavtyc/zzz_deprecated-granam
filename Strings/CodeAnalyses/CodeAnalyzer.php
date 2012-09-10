<?php
namespace granam;

abstract class CodeAnalyzer extends \granam\Object {

	const POINTER_IN_CODE = 255; // b11111111

	private $internalCodeAnalysis;
	private $stateOfPointer = 0;
	private $lastCodeToDetermineStartPosition = FALSE;
	private $lastFoundCodeOpenningTagAndPosition = FALSE;
	private $lastCodeToDetermineEndPosition = FALSE;
	private $lastFoundCodeClosingPosition = FALSE;

	private $codeParts = array();
	private $codeBorderTagPairs = array();
	private $codeOpenningTags = array();
	private $codeClosingTags = array();

	private $singlelineCommentBorderTagPairs = array();
	private $multilineCommentBorderTagPairs = array();

	public function __construct(
		array $oppeningTags,
		array $closingTags,
		\granam\CodeAnalysis $internalCodeAnalysis = NULL
	) {
		parent::__construct();
		$this->addCodeBorderTagsGroupPair($oppeningTags, $closingTags);
		$this->setInternalCodeAnalysis($internalCodeAnalysis);
	}

	public function addInternalCodeAnalyzer(
		\granam\CodeAnalysis $internalCodeAnalyzer
	) {
		if (is_null($internalCodeAnalyzer)) {
			throw new Exception(
				'Given internal code analyzer is null',
				Exception::CONTENT_TYPE | Exception::SERVICE_REGISTERING
			);
		}

		if ($this->isAnalyzerUsed()) {
			throw new Exception(
				'Analyzer is already in use, adding internal code analyzer is prohibited',
				Exception::PROCESS_STATE | Exception::SERVICE_REGISTERING
			);
		}

		$this->internalCodeAnalysis->registerCodeAnalyzer($internalCodeAnalyzer);
	}

	public function addCodePart($codePart)
	{
		if (!Strings_Utilities::isStringOrToStringConvertable($codePart)) {
			throw new Exception(
				'Given code is not string nor to string equivalent covertable, but [' .
					gettype($codePart) . ']',
				Exception::CONTENT_TYPE
			);
		}

		$unusedStringCodePart = (string)$codePart;
		if (!$this->isInCode()) {
			$codeStartPosition = $this->determineCodeStartPosition($unusedStringCodePart);
			if (is_int($codeStartPosition)) {
				throw new Exception(
					'Given code has not been recognized',
					Exception::CONTENT_VALUE
				);
			}

			if ($codeStartPosition > 0) {
				throw new Exception(
					'Given code leads by unknown code before ' . $codeStartPosition .
						'. character position',
					Exception::CONTENT_VALUE
				);
			}

			$openningTagString = substr(
				$unusedStringCodePart,
				0,
				strlen($this->lastFoundCodeOpenningTagAndPosition->getTag())
			);
			$unusedStringCodePart = substr(
				$unusedStringCodePart,
				strlen($openningTagString)
			);
			$this->enterStateOfPointer(self::POINTER_IN_CODE);
		}

		$codeEndPosition = $this->determineCodeEndPosition($unusedStringCodePart);
		if (is_int($codeEndPosition)) {
			$closingTagString = substr(
				$unusedStringCodePart,
				$this->lastFoundCodeClosingTagAndPosition->getStartPosition(),
				strlen($this->lastFoundCodeOpenningTagAndPosition->getTag())
			);
			$this->setUnknownCode(substr(
					$unusedStringCodePart,
					$this->lastFoundCodeClosingTagAndPosition->getAfterEndPosition()
				)
			);
			$unusedStringCodePart = substr(
				$unusedStringCodePart,
				0,
				$this->lastFoundCodeClosingTagAndPosition->getStartPosition() // start
				// position is equivalent to length of prepending code
			);
			$this->leaveStateOfPointer(self::POINTER_IN_CODE);
		}

		if (isset($openningTagString)) {
			next($this->codeParts); // keeping cursor on last added code part as follows
			$this->codeParts[] = new OpenningTagCodeElement($openningTagString);
		}

		$this->internalCodeAnalysis->addCode($unusedStringCodePart);
		if (is_int($codeEndPosition)) {
			next($this->codeParts); // keeping cursor on last added code part as follows
			$this->codeParts[] = new ClosingTagCodeElement($closingTagString);
		}

	}

	public function __toString()
	{
		return implode($this->getAnalyzedCode());
	}

	public function getCode()
	{
		return (string)$this;
	}

	public function getCodeParts()
	{
		return $this->codeParts;
	}

	public function getLengthOfLastAcceptedCode()
	{
		$lastGivenCode = $this->getLastGivenCode();
		if (!is_string($lastGivenCode)) {
			throw new Exception(
				'There is no last given code',
				Exception::PROCESS_STATE
			);
		}

		return strlen($lastGivenCode);
	}

	public function isAnalyzerUsed()
	{
		return count($this->getCodeParts()) > 0;
	}

	/**
	 * @param string $codeToSearchIn
	 * @return int|bool found code start position or false if none
	 */
	public function determineCodeStartPosition($codeToSearchIn)
	{
		if (!Strings_Utilities::isStringOrToStringConvertable($codeToSearchIn)) {
			throw new Exception(
				'Given code to search in is not string nor to string equivalent ' .
					' covertable, but [' . gettype($codeToSearchIn) . ']',
				Exception::CONTENT_TYPE
			);
		}

		$stringCodeToSearchIn = (string)$codeToSearchIn;
		if ($stringCodeToSearchIn === $this->lastCodeToDetermineStartPosition) {
			// cache of last search result
			return $this->lastFoundCodeOpenningTagAndPosition->getStartPosition();
		}

		if (count($this->getCodeOpenningTags()) == 0) {
			throw new Exception(
				'Openning tags are not set, determining code start position is illegal',
				Exception::CONTENT_VALUE
			);
		}

		$this->lastCodeToDetermineStartPosition = $stringCodeToSearchIn;
		$this->lastFoundCodeOpenningTagAndPosition =
			CodeAnalyses_Utilities::getClosestTagAndPosition(
				$this->getCodeOpenningTags(),
				$codeToSearchIn
			);

		return $this->lastFoundCodeOpenningTagAndPosition->getStartPosition();
	}

	public function determineCodeEndPosition($codeToSearchIn)
	{
		if (!Strings_Utilities::isStringOrToStringConvertable($codeToSearchIn)) {
			throw new Exception(
				'Given code to search in is not string nor to string equivalent ' .
					' covertable, but [' . gettype($codeToSearchIn) . ']',
				Exception::CONTENT_TYPE
			);
		}

		$stringCodeToSearchIn = (string)$codeToSearchIn;
		if ($this->isInComment()) {
			if ($this->isInSinglelineComment()) {

			} else { // multiline comment for sure

			}
		}

		if ($stringCodeToSearchIn === $this->lastCodeToDetermineEndPosition) {
			// cache of last search result
			return $this->lastCodeToDetermineEndPosition;
		}

		if (count($this->getCodeClosingTags()) === 0) {
			throw new Exception(
				'Closing tags are not set, determining code end position is illegal',
				Exception::CONTENT_VALUE
			);
		}

		$this->lastCodeToDetermineEndPosition = $stringCodeToSearchIn;
		$this->lastFoundCodeClosingPosition =
			CodeAnalyses_Utilities::getClosestTagAndPosition(
				$this->getCodeClosingTags(),
				$codeToSearchIn
			);

		return $this->lastFoundCodeClosingPosition->getAfterEndPosition() - 1;
	}

	public function getMultilineCommentBorderTagPairs()
	{
		return $this->multilineCommentBorderTagPairs;
	}

	public function getSinglelineCommentBorderTagPairs()
	{
		return $this->singlelineCommentBorderTagPairs;
	}

	public function getCodeOpenningTags()
	{
		return $this->codeOpenningTags;
	}

	public function getCodeClosingTags()
	{
		return $this->codeClosingTags;
	}

	public function isInCode()
	{
		return $this->stateOfPointer & self::POINTER_IN_CODE;
	}

	// ---- LINEAGE FACILITIES ----

	protected function addMultilineCommentBorderTagPair(
		$openningMultilineCommentTag,
		$closingMultilineCommentTag
	){
		if ($this->isInCode()) {
			throw new Exception(
				'Multiline comment tags could not be initialized after code adding',
				Exception::PROCESS_STATE
			);
		}

		$this->multilineCommentBorderTagPairs[] =
			new CodeBorderTagsGroupPair(
				$openningMultilineCommentTag,
				$closingMultilineCommentTag
			);
	}

	protected function addSinglelineCommentBorderTagPair(
		$openningSinglelineCommentTag,
		$closingSinglelineCommentTag // every singleline comment ends on end of line
	){
		if ($this->isInCode()) {
			throw new Exception(
				'Singleline comment tags could not be initialized after code adding',
				Exception::PROCESS_STATE
			);
		}

		$this->singlelineCommentBorderTagPairs[] =
			new CodeBorderTagsGroupPair(
				$openningSinglelineCommentTag,
				$closingSinglelineCommentTag
			);
	}

	protected function getLastGivenCode()
	{
		return current($this->codeParts);
	}

	// ---- LOCAL HELPERS ----

	private function addCodeBorderTagsGroupPair(
		array $codeOpenningTags,
		array $codeClosingTags
	){
		if ($this->isInCode()) {
			throw new Exception(
				'Can not extend code borders after start of code analysis',
				Exception::PROCESS_STATE
			);
		}

		$openningAndClosingTagPair =
			CodeBorderTagsGroupPair::getInstance($codeOpenningTags, $codeClosingTags);
		$this->addCodeOpenningTags($openningAndClosingTagPair->getOpenningTags());
		$this->addCodeClosingTags($openningAndClosingTagPair->getClosingTags());
		$this->codeBorderTagPairs[] = $openningAndClosingTagPair;
	}

	private function addCodeOpenningTags(array $openningTags)
	{
		foreach ($openningTags as $openningTag) {
			$this->codeOpenningTags[] = (string)$openningTag;
		}
	}

	private function addCodeClosingTags(array $closingTags)
	{
		foreach ($closingTags as $closingTag) {
			$this->codeClosingTags[] = (string)$closingTag;
		}
	}

	private function leaveStateOfPointer($state)
	{
		$this->stateOfPointer = $this->stateOfPointer ^ $state;
	}

	private function enterStateOfPointer($state)
	{
		$this->stateOfPointer = $this->stateOfPointer | $state;
	}

	private function setInternalCodeAnalysis($internalCodeAnalysis){
		if (is_null($internalCodeAnalysis)) {
			$this->internalCodeAnalysis = new CodeAnalysis;
		} else {
			$this->internalCodeAnalysis = $internalCodeAnalysis;
		}
	}
}
