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
	"rpc" => Array(
		"builder"	=>	"rpc",
		"icon"		=>	"external_rpc.png",
		"label"		=>	"ext_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("AUTHENTICATION_RPCS")
	)
);

$options = Array(
	"AUTHENTICATION_RPCS"	=>	Array(
		"type"		=>	"Rpc",
		"label"		=>	"",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>