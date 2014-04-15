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

function get_ldap() {

	$ldaps = json2array(COMODOJO_AUTHENTICATION_LDAPS);

	$serialized = Array();

	$i = 1;

	foreach ($ldaps as $name => $ldap) {
		$ldap["id"] = $i;
		$ldap["name"] = $name;
		array_push($serialized, $ldap);
		$i++;
	}

	for ($i; $i < 10; $i++) { 
		array_push($serialized, Array(
			"id"	=>	$i,
			"name"	=>	'',
			"server"=>	'',
			"port"	=>	'',
			"dcs"	=>	'',
			"dns"	=>	'',
			"filter"=>	'',
			"listuser"=>'',
			"listpass"=>'',
			"cmode"	=>	'',
			"enabled"=>	false
		));
	}

	return $serialized;

}
 
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
		"options"	=>	get_ldap()
	)
);

//$panels = Array(
//	"ldap" => Array(
//		"builder"	=>	"form",
//		"icon"		=>	"ldap.png",
//		"label"		=>	"lda_0",
//		"table"		=>	"options",
//		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
//		"include"	=>	Array("LDAP_SERVER","LDAP_PORT","LDAP_DC","LDAP_OTHER_DN","LDAP_DEFAULT_FILTER","LDAP_LISTER_USERNAME","LDAP_LISTER_PASSWORD","LDAP_COMPATIBILE_MODE")
//	)
//);
//
//$options = Array(
//	"LDAP_SERVER"	=>	Array(
//		"type"		=>	"TextBox",
//		"label"		=>	"lda_1",
//		"required"	=>	false,
//		"onclick"	=>	false,
//		"options"	=>	false
//	),
//	"LDAP_PORT"		=>	Array(
//		"type"		=>	"NumberTextBox",
//		"label"		=>	"lda_2",
//		"required"	=>	true,
//		"onclick"	=>	false,
//		"options"	=>	false,
//		"min"		=>	1,
//		"max"		=>	65535
//	),
//	"LDAP_DC" 		=>	Array(
//		"type"		=>	"TextBox",
//		"label"		=>	"lda_3",
//		"required"	=>	false,
//		"onclick"	=>	false,
//		"options"	=>	false
//	),
//	"LDAP_OTHER_DN"	=>	Array(
//		"type"		=>	"TextBox",
//		"label"		=>	"lda_4",
//		"required"	=>	false,
//		"onclick"	=>	false,
//		"options"	=>	false
//	),
//	"LDAP_DEFAULT_FILTER"=>	Array(
//		"type"		=>	"TextBox",
//		"label"		=>	"lda_5",
//		"required"	=>	false,
//		"onclick"	=>	false,
//		"options"	=>	false
//	),
//	"LDAP_LISTER_USERNAME"=>	Array(
//		"type"		=>	"TextBox",
//		"label"		=>	"lda_6",
//		"required"	=>	false,
//		"onclick"	=>	false,
//		"options"	=>	false
//	),
//	"LDAP_LISTER_PASSWORD"=>	Array(
//		"type"		=>	"PasswordTextBox",
//		"label"		=>	"lda_7",
//		"required"	=>	false,
//		"onclick"	=>	false,
//		"options"	=>	false
//	),
//	"LDAP_COMPATIBILE_MODE"=>	Array(
//		"type"		=>	"OnOffSelect",
//		"label"		=>	"lda_8",
//		"required"	=>	false,
//		"onclick"	=>	false,
//		"options"	=>	false
//	)
//);

?>