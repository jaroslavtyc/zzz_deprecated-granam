<?php
namespace granam;

class CodeAnalyses_Utilities extends \granam\StaticMethodsOnly {

	const TAG_MATCHING_EVERYWHERE = ''; // every character is surrounded by empty
	// strings, these are matched - useable for code without openning tags; code
	// or block starts on very begining of any string
	// for representing "enverending" code use nothing

	public static function getPositionAftertTag($tag, $position)
	{
		return strlen($tag) + $position;
	}

	public static function isASinglelineCommentStartTag($tag)
	{
		return self::isInList($tag, self::getSinglelineCommentStartTags());
	}

	public static function isAMultilineCommentStartTag($tag)
	{
		return self::isInList($tag, self::getMultilineCommentStartTags());
	}

	public static function isAScriptClosingTag($tag)
	{
		return self::isInList($tag, self::getScriptClosingTags());
	}

	public static function isInList($string, array $list)
	{
		foreach ($list as $itemOfList) {
			if (strtolower($string) === strtolower($itemOfList)) {

				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @param array $tags to search on
	 * @param string $row
	 * @param int $position from where to start search
	 * @return string|bool tag with confirmed found or false
	 */
	public static function getClosestTag(array $tags, $row, $position = 0)
	{
		return self::getClosestTagAndPosition($tags, $row, $position)->getTag();
	}

	/**
	 * @param array $tags to search on
	 * @param string $row
	 * @param int $position from where to start search
	 * @return int|bool starting position of tag with confirmed found or false
	 */
	public static function getClosestTagStartPosition(array $tags, $row, $position = 0)
	{
		return self::getClosestTagAndPosition($tags, $row, $position)->getStartPosition();
	}

	/**
	 * @param array $tags to search on
	 * @param string $row
	 * @param int $position from where to start search
	 * @return int|bool ending position of tag with confirmed found or false
	 */
	public static function getClosestTagEndPosition(array $tags, $row, $position = 0)
	{
		return self::getClosestTagAndPosition($tags, $row, $position)
			->getAfterEndPosition() - 1;
	}

	/**
	 * @param array $tags to search on
	 * @param string $row
	 * @param int $position from where to start searching
	 * @return TagAndPosition
	 */
	public static function getClosestTagAndPosition(
		array $tags,
		$row,
		$fromPosition = 0
	){
		$closestTag = FALSE;
		$closestPosition = FALSE;
		foreach ($tags as $tag) {
			if ($tag === '') { // each string is built from empty string, character,
			// empty string and so on
				return new TagAndPosition($tag, 0);
			}

			$possibleClosestTagPosition = stripos($row, $tag, $fromPosition);
			if (is_numeric($possibleClosestTagPosition)) {
				if (FALSE === $closestPosition
				 || $closestPosition > $possibleClosestTagPosition) {
					$closestPosition = $possibleClosestTagPosition;
					$closestTag = $tag;
					if ($closestPosition === $fromPosition) { // this tag
					// is the first element in code from actual position, noone else
					// can be before it
						break;
					}
				}
			}
		}

		return new TagAndPosition($closestTag, $closestPosition);
	}
}