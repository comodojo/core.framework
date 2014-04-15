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
	"ldap" => Array(
		"builder"	=>	"ldap",
		"icon"		=>	"ldap.png",
		"label"		=>	"lda_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("AUTHENTICATION_LDAPS")
	)
);

$options = Array(
	"AUTHENTICATION_LDAPS"	=>	Array(
		"type"		=>	"Ldap",
		"label"		=>	"",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>