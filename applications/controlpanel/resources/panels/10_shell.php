<?php

/** 
 * controlpanel panel definition
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$panels = Array(
	"shell" => Array(
		"builder"	=>	"form",
		"icon"		=>	"shell.png",
		"label"		=>	"she_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SHELL_ENABLED"),
		"note"		=>	Array("name"=>"debug_note","type"=>"info","content"=>"she_2")
	)
);

$options = Array(
	"SHELL_ENABLED"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"she_1",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>