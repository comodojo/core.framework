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

function get_locale_options() {
	$locales = Array();
	foreach (getSupportedLocales() as $locale) {
		array_push($locales,Array("name"=>$locale,"value"=>$locale));
	}
	return $locales;
} 

$panels = Array(
	"general" => Array(
		"builder"	=>	"form",
		"icon"		=>	"general.png",
		"label"		=>	"0100",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SITE_TITLE","SITE_DESCRIPTION","SITE_AUTHOR","SITE_DATE","GRAVATAR_RATING","SITE_LOCALE","DEFAULT_ENCODING")
	)
);

$options = Array(
	"SITE_TITLE"		=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"0101",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_DESCRIPTION"	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0102",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_AUTHOR"		=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0103",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_DATE"			=>	Array(
		"type"		=>	"DateTextBox",
		"label"		=>	"0104",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"GRAVATAR_RATING"=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0105",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	Array(
			array(
				"value"	=>	"g",
				"name"	=>	"G"
			),
			array(
				"value"	=>	"pg",
				"name"	=>	"PG"
			),
			array(
				"value"	=>	"r",
				"name"	=>	"R"
			),
			array(
				"value"	=>	"x",
				"name"	=>	"X"
			)
		)
	),
	"SITE_LOCALE"		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0107",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_locale_options()
	),
	"DEFAULT_ENCODING"		=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"0106",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>