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
	"services" => Array(
		"builder"	=>	"form",
		"icon"		=>	"services.png",
		"label"		=>	"ser_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SERVICES_ENABLED"),
		"note"		=>	Array("name"=>"debug_note","type"=>"info","content"=>"0182")
	)
);

$options = Array(
	"SERVICES_ENABLED"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"ser_1",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>