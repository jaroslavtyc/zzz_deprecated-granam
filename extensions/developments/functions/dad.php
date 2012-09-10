<?php
namespace granam;
/**
 * Dump & die; dump variables by var_dump and exit
 */
function dad() {
	call_user_func_array('var_dump', func_get_args());
	exit;
}