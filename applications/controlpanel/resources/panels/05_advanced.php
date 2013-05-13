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
	"advanced" => Array(
		"builder"	=>	"form",
		"icon"		=>	"advanced.png",
		"label"		=>	"0110",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SITE_PATH","SITE_URL","SITE_EXTERNAL_URL","SITE_DEFAULT_CONTAINER","JS_BASE_URL","SESSION_ENABLED","SESSION_AUTHENTICATED","JS_REQUIRES")
	)
);

$options = Array(
	"SITE_PATH"		=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"0111",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_URL"		=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"0112",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_EXTERNAL_URL"=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0113",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_DEFAULT_CONTAINER"=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"0114",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"JS_BASE_URL"	=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"0117",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"JS_REQUIRES"	=>	Array(
		"type"		=>	"Textarea",
		"label"		=>	"0118",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SESSION_ENABLED"=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0115",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SESSION_AUTHENTICATED"=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0116",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>