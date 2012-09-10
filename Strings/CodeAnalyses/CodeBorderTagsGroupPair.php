<?php
namespace granam;

class CodeBorderTagsGroupPair extends \granam\Object implements \granam\Singleton {

	/**
	 * @see http://en.wikipedia.org/wiki/Multiton_pattern
	 */
	private static $tagsDependentInstances = array();
	private $openningTags = array();
	private $closingTags = array();

	protected function __construct(array $openningTags, array $closingTags)
	{
		parent::__construct();
		$this->initializeOpenningTags($openningTags);
		$this->initializeClosingTags($closingTags);
	}

	// @todo move this control into analysis
	public static function getInstance(
		array $openningTags = NULL,
		array $closingTags = NULL
	) {
		$validatedOpenningTags = self::getValidatedTags($openningTags); // transforms
		// potential NULL value into (empty) array as well
		if (count($validatedOpenningTags) == 0) {
			throw new Exception(
				'List of openning tags can not be empty.',
				granam\Exception::CONTENT_VALUE | granam\Exception::SERVICE_REGISTERING
			);
		}

		foreach (self::$tagsDependentInstances as $tagsDependentInstance) {
			$openningTagsAreSame = // the first condition is same number of tags
				count($tagsDependentInstance->getOpenningTags()) == count($validatedOpenningTags);
			if ($openningTagsAreSame) {
				foreach($tagsDependentInstance->getOpenningTags() as $usedOpenningTag) {
					if (!in_array($usedOpenningTag, $validatedOpenningTags, TRUE)) {
						$openningTagsAreSame = FALSE;
						break;
					}
				}

				if ($openningTagsAreSame) {
					$validatedClosingTags = self::getValidatedTags($closingTags);
					$closingTagsAreSame = // the first condition is same number of tags
						count($tagsDependentInstance->getClosingTags()) == count($validatedClosingTags);
					if ($closingTagsAreSame) {
						foreach ($tagsDependentInstance->getClosingTags() as $usedClosingTag) {
							if (!in_array($usedClosingTag, $validatedClosingTags, TRUE)) {
								$closingTagsAreSame = FALSE;
								break;
							}
						}
					}

					if ($closingTagsAreSame) {
						return $tagsDependentInstance;
					}
				}
			}
		}

		$actualClassName = get_called_class();
		$newTagsDependentInstance = new $actualClassName($openningTags, $closingTags);
		self::$tagsDependentInstances[] = $newTagsDependentInstance;

		return $newTagsDependentInstance;
	}

	public function getOpenningTags()
	{
		return $this->openningTags;
	}

	public function getClosingTags()
	{
		return $this->closingTags;
	}

	// ---- LOCAL HELPERS ----

	private function initializeOpenningTags(array $openningTags)
	{
		$validatedOpenningTags = self::getValidatedTags($openningTags);
		if (in_array(
				 CodeAnalyses_Utilities::TAG_MATCHING_EVERYWHERE,
				 $validatedOpenningTags,
				 TRUE
			)
		 && count($validatedOpenningTags) > 1) {
			throw new Exception(
				'Openning tag with value matching everywhere [' .
				var_export(CodeAnalyses_Utilities::TAG_MATCHING_EVERYWHERE, TRUE) .
					'] does not have sense with any other openning tags',
				Exception::CONTENT_VALUE | Exception::SERVICE_REGISTERING
			);
		}

		$this->openningTags = $validatedOpenningTags;
	}

	private function initializeClosingTags(array $closingTags)
	{
		$this->closingTags = self::getValidatedTags($closingTags);
	}

	private static function getValidatedTags(array $tags = NULL)
	{
		$validatedTags = array();
		foreach ((array)$tags as $tag) { // NULL turned to array is empty array
			if (!Strings_Utilities::isStringOrToStringConvertable($tag)) {
				throw new Exception(
					'Given tag can be only string or to string eqivalent convertable.' .
						'Given value is [' . gettype($tag) . ']',
					Exception::CONTENT_VALUE
				);
			} else {
				$validatedTags[] = (string)$tag;
			}
		}

		return array_unique($validatedTags);
	}
}