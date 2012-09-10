<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * granam variable dump function plugin
 *
 * Type:			function
 * Name:			dump
 * Purpose:		dump variable by PHP native var_dump function and format result
 *	 using HTML pre format
 *
 * @param array $params input values
 * @param Smarty_Internal_Template $template
 * @return void
 */
function smarty_function_dump($params, $template){
	echo '<pre style="color: red; background-color: yellow; text-align: left">';
	foreach($params as $index=>$param){
		echo("$index=");
		var_dump($param);
	}
	echo '</pre>';
}