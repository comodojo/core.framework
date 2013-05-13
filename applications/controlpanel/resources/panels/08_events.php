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
	"events" => Array(
		"builder"	=>	"form",
		"icon"		=>	"events.png",
		"label"		=>	"0170",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("EVENTS_ENABLED"),
		"note"		=>	Array("name"=>"debug_note","type"=>"info","content"=>"0172")
	)
);

$options = Array(
	"EVENTS_ENABLED"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0171",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>