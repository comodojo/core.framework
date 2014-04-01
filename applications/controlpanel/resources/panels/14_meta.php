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
	"meta_tags" => Array(
		"builder"	=>	"meta",
		"icon"		=>	"meta.png",
		"label"		=>	"met_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SITE_TAGS")
	)
);

$options = Array(
	"SITE_TAGS"	=>	Array(
		"type"		=>	"Meta",
		"label"		=>	"",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>