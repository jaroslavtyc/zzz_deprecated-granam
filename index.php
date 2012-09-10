<?php
// ________________________
//	Exemplary index.
//	Explore it,
//	think about it,
//	make your own.
// ------------------------

//This is single row comment - php will ignore it

/* This is multiline comment, starting by slash and asterisk, ending by reversed
 order of same characters - asterisk and slash
 */

/** This is comment mentioned for external documentation, built later by
 * a comment extractor and documentation builder.
 * Starts by slash and two asterisks, ends as standard multiline comment,
 * asterisk and slash.
 *
 * This type of "external documentation" comment is technically standard multiline
 * comment, PHP is approaching to it as that and additional asterisk on start
 * is taken as part of comment.
 * So additional asterisk on start has special meaning only for documentation
 * builders.
 *
 * Single asterisk at beginning of every row is good practice how to highlight
 * comments, it is not necessary.
 */

error_reporting(-1);
/* Every error, every warning, every notice, including strict
 * useable for development - try to keep Your code absolutely whitout any
 * imperfection, it will pay back to you.
 */

require __DIR__ . '/initializations/initialization.php';
/* Autoloader is couple of rules how to find file with script, containting
 * PHP class or interface.
 * Granam autoloader is looking for script named exactly as class or interface
 * are named with .php at end, will search every directory in granam directory
 * and in document root directory (if differ from granam).
 * For example GranamSmarty will be in this example searched in granam
 * directory and his subdirectories (and it will find it on path
 * Viewers/Smarty.php)
 */

$smarty = \granam\Smarty::getInstance(); /* getting instance of Smarty -
 * single instance everywhere.
 * (http://en.wikipedia.org/wiki/Singleton_pattern)
 */

$smarty->debugging = FALSE; /* no debugg display of Smarty templates
 * (http://www.smarty.net/docsv2/en/chapter.debugging.console.tpl)
 */

$smarty->caching = FALSE; /* no chaching of content, so changes will be displayed
 * immediately
 */

$smarty->assign(
	'css',
	/* under this name, "$css", will be available following value
	 * (of type array in this case) in Smarty template ("index.tpl" in this example,
	 * see parameter of smarty display() function below)
	 */
	array('main.css','system/main.css')
	/* values, array here, available in Smarty under previously defined name (css)*/
);

$smarty->assign(
	'headerJs', // under "$headerJs" will be available following value in Smarty
	// template
	array('javascript/libraries/jquery.js') // value available under above
	// mentioned variable name
);

$smarty->display('index.tpl'); /* means: compile file with name index.tpl, if
 * not cached or if caching is forbiden, and show result, respective run compiled
 * PHP script, built from indx.tpl (you can see compiled PHP script in
 * templatesCompiled directory).
 * Location of index.tpl is defined in constructor \granam\Smarty, see used
 * addTemplateDir() method there.
 * In same method are defined as well root directory for Smarty templates,
 * DOCUMENT_ROOT . '/templates', and name of directory for compiled templates,
 * DOCUMENT_ROOT . '/templates_compiled'.
 * Notice: templates are searched in DOCUMENT_ROOT . '/templates' directory
 * at first, 'granam/templates' directory at second, so if you define your own
 * index.tpl in your project, it will be preffered;
 *	this behaviour can be changed in GranamSmarty __constuctor, methods
 * addPluginsDir() and their order.
 */

/*
 * closure of php script, "question-mark" followed by right angle bracket,
 * is not recommended:
 *
 * 1) it is not necessary, so why use it
 * 2) you can forget some character, like white space, after this closure,
 *		so unwanted HTTP headers should be sent; so again, why use it
 *		(http://php.net/manual/en/function.header.php)
 */