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
		"builder"	=>	"form",
		"icon"		=>	"ldap.png",
		"label"		=>	"0210",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("LDAP_SERVER","LDAP_PORT","LDAP_DC","LDAP_OTHER_DN","LDAP_DEFAULT_FILTER","LDAP_LISTER_USERNAME","LDAP_LISTER_PASSWORD","LDAP_COMPATIBILE_MODE")
	)
);

$options = Array(
	"LDAP_SERVER"	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0211",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"LDAP_PORT"		=>	Array(
		"type"		=>	"NumberTextBox",
		"label"		=>	"0212",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false,
		"min"		=>	1,
		"max"		=>	65535
	),
	"LDAP_DC" 		=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0213",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"LDAP_OTHER_DN"	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0214",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"LDAP_DEFAULT_FILTER"=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0215",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"LDAP_LISTER_USERNAME"=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0216",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"LDAP_LISTER_PASSWORD"=>	Array(
		"type"		=>	"PasswordTextBox",
		"label"		=>	"0217",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"LDAP_COMPATIBILE_MODE"=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0218",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>