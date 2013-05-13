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

function get_bootstrap() {
	comodojo_load_resource("roles_management");
	$r = new roles_management();
	$roles = $r->get_roles();
	//array_push($roles, Array('id'=>'persistent','description'=>'persistent'));
	$applications = Array();
	$handler = opendir(COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER);
	$themes_available = Array();
	while (false !== ($item = readdir($handler))) {
		if (is_readable(COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER.$item."/".$item.".properties")) {
			array_push($applications,$item);
		}
	}
	closedir($handler);
	return Array("roles"=>$roles,"applications"=>$applications);
}
 
$panels = Array(
	"bootstrap" => Array(
		"builder"	=>	"bootstrap",
		"icon"		=>	"bootstrap.png",
		"label"		=>	"0270",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("BOOTSTRAP")
	)
);

$options = Array(
	"BOOTSTRAP"	=>	Array(
		"type"		=>	"Bootstrap",
		"label"		=>	"",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	get_bootstrap()
	)
);

?>