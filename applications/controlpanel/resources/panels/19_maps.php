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
	"registration" => Array(
		"builder"	=>	"form",
		"icon"		=>	"maps.png",
		"label"		=>	"map_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("GMAPS_PRELOAD","GMAPS_SENSOR","GMAPS_APIKEY")
	)
);
  
$options = Array(
	"GMAPS_PRELOAD"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"map_1",
		"required"	=>	true,
		"onclick"	=>	false
	),
	"GMAPS_SENSOR"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"map_2",
		"required"	=>	true,
		"onclick"	=>	false
	),
	"GMAPS_APIKEY" 	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"map_3",
		"required"	=>	false,
		"onclick"	=>	false
	)
);

?>