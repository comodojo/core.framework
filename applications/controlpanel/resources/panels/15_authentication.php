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

function get_authentication_mode() {
	return Array(
		Array("label"=>"Local", "id"=>'local'),
		Array("label"=>"LDAP filtered", "id"=>'ldapfiltered'),
		Array("label"=>"LDAP unfiltered + SharedKey", "id"=>'ldapunfiltered'),
		Array("label"=>"External RPC", "id"=>'rpc')
	);
}

$panels = Array(
	"authentication" => Array(
		"builder"	=>	"form",
		"icon"		=>	"authentication.png",
		"label"		=>	"aut_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("AUTHENTICATION_MODE","AUTHENTICATION_CACHE_ENABLED","AUTHENTICATION_CACHE_TTL")
	)
);

$options = Array(
	"AUTHENTICATION_MODE"			=>	Array(
		"type"		=>	"Select",
		"label"		=>	"aut_1",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_authentication_mode()
	),
	"AUTHENTICATION_CACHE_ENABLED"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"aut_2",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"AUTHENTICATION_CACHE_TTL" 		=>	Array(
		"type"		=>	"NumberSpinner",
		"label"		=>	"aut_3",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>