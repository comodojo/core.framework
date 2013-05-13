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
	"maintenance" => Array(
		"builder"	=>	"form",
		"icon"		=>	"maintenance.png",
		"label"		=>	"0140",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SITE_SUSPENDED","SITE_SUSPENDED_MESSAGE")
	)
);

$options = Array(
	"SITE_SUSPENDED"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0141",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_SUSPENDED_MESSAGE"		=>	Array(
		"type"		=>	"SmallEditor",
		"label"		=>	"0142",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>