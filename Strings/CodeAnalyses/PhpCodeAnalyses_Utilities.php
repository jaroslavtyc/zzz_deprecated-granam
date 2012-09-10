<?php
namespace granam;

class PhpCodeAnalyses_Utilities extends CodeAnalyses_Utilities {

	public static function getMultilineCommentStartTags()
	{
		return array('/*');
	}

	public static function getMultilineCommentEndTags()
	{
		return array('*/');
	}

	public static function getSinglelineCommentStartTags()
	{
		return array('//', '#');
	}

	public static function getScriptOpenningTags()
	{
		return array('<?php', '<?'); // this sequence is important cause of
		// getClosestTagAndPosition() method structure, its tag returning value and
		// short tag allow detection
	}

	public static function getScriptClosingTags()
	{
		return array('?>');
	}

	public static function isShortOppeningTagAllowed()
	{
		return ini_get('short_open_tag') == '1';
	}
}