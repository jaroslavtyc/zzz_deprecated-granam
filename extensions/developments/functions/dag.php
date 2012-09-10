<?php
namespace granam;
/**
 * Dump & go; var_dump variables
 *
 */
function dag() {
	call_user_func_array('var_dump', func_get_args());
}