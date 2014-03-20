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

function get_registration_default_role() {
	comodojo_load_resource("roles_management");
	$r = new roles_management();
	$roles = $r->get_roles();
	$roles_options = Array();
	foreach ($roles as $role) {
		array_push($roles_options,Array("label"=>$role["description"],"id"=>$role["id"]));
	}
	return $roles_options;
}

$panels = Array(
	"registration" => Array(
		"builder"	=>	"form",
		"icon"		=>	"registration.png",
		"label"		=>	"0240",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("REGISTRATION_MODE","REGISTRATION_AUTHORIZATION","REGISTRATION_DEFAULT_ROLE","REGISTRATION_TTL")
	)
);
  
$options = Array(
	"REGISTRATION_MODE"			=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0241",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"REGISTRATION_AUTHORIZATION"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0242",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"REGISTRATION_DEFAULT_ROLE" 		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0243",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_registration_default_role()
	),
	"REGISTRATION_TTL" 		=>	Array(
		"type"		=>	"NumberSpinner",
		"label"		=>	"0244",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>