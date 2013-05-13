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

function get_available_site_themes() {
	$handler = opendir(COMODOJO_SITE_PATH."comodojo/themes");
	$themes_available = Array();
	while (false !== ($item = readdir($handler))) {
		if (is_readable(COMODOJO_SITE_PATH."comodojo/themes/".$item."/theme.info")) {
			require(COMODOJO_SITE_PATH."comodojo/themes/".$item."/theme.info");
			if (is_array($theme)) {
				array_push($themes_available, Array(
					"name"=>$theme["name"],
					"value"=>$theme["name"],
					"createdBy"=>$theme["createdBy"],
					"version"=>$theme["version"],
					"framework"=>$theme["framework"],
					"comment"=>$theme["comment"]
				));
				$theme = "";
			}
		}
	}
	closedir($handler);
	return $themes_available;
}

function get_available_dojo_themes() {
	return Array(
		Array("name"=>"tundra","value"=>"tundra"),
		Array("name"=>"soria","value"=>"soria"),
		Array("name"=>"nihilo","value"=>"nihilo"),
		Array("name"=>"claro","value"=>"claro")
	);
}

$panels = Array(
	"themes" => Array(
		"builder"	=>	"theme",
		"icon"		=>	"themes.png",
		"label"		=>	"0150",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SITE_THEME","SITE_THEME_DOJO")
	)
);

$options = Array(
	"SITE_THEME"	=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0151",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_available_site_themes()
	),
	"SITE_THEME_DOJO"		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0152",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_available_dojo_themes()
	)
);

?>