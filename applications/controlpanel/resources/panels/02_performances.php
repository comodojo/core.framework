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

//require(COMODOJO_SITE_PATH."comodojo/others/available_cdn"); 

$panels = Array(
	"performances" => Array(
		"builder"	=>	"form",
		"icon"		=>	"performances.png",
		"label"		=>	"per_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("CACHE_ENABLED","CACHE_TTL"/*,"JS_XD_LOADING","JS_XD_LOCATION","JS_XD_TIMEOUT"*/)
	)
);

$options = Array(
	"CACHE_ENABLED"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"per_1",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"CACHE_TTL"		=>	Array(
		"type"		=>	"NumberSpinner",
		"label"		=>	"per_2",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)//,
	//"JS_XD_LOADING"	=>	Array(
	//	"type"		=>	"OnOffSelect",
	//	"label"		=>	"0123",
	//	"required"	=>	true,
	//	"onclick"	=>	false,
	//	"options"	=>	false
	//),
	//"JS_XD_LOCATION"=>	Array(
	//	"type"		=>	"Select",
	//	"label"		=>	"0124",
	//	"required"	=>	false,
	//	"onclick"	=>	false,
	//	"options"	=>	$available_cdn
	//),
	//"JS_XD_TIMEOUT"	=>	Array(
	//	"type"		=>	"NumberSpinner",
	//	"label"		=>	"0125",
	//	"required"	=>	true,
	//	"onclick"	=>	false,
	//	"options"	=>	false
	//)
);

?>