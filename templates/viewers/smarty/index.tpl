{*
	This is multiline comment in Smarty template.
	Starts by Smarty opening character, usually opening brace, followed by asterisk
	and ends by asterisk, followed by Smarty closing character, usually closing brace.
	Single line comment is not available.
*}
{extends 'base.tpl'}
{*
	Extending of template in Smarty template means inheriting parent content,
	except explicitly mentioned blocks with different content inside, between
	opening and closing block tags.
	Every block has to be named for identification.
*}
{block "allowed-content"}
<pre>This is inside allowed-content block. Requirements have been checked in parent template, base.tpl.</pre>
	{block "message"}
<span>IT sometimes works!</span>
<pre>Inside block, inherited from parent base.tpl and rewriten by this child index.tpl, can be any number of newly defined blocks, like this "message" one.</pre>
	{/block}
{/block}