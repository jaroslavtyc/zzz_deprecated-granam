<?php
namespace granam;

/**
 * In some languages like PHP comments sterilize end of code so end of code
 * is valid only out of comment
 */
class CommentsPrioritizedCodeAnalyzer extends CodeAnalyzer {

	const POINTER_IN_SINGLELINE_COMMENT = 1,	// b1
		POINTER_IN_MULTILINE_COMMENT = 2;		// b10

}