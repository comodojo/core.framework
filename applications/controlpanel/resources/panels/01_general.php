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
		array_push($locales,Array("label"=>$locale,"id"=>$locale));
	}
	return $locales;
} 

$panels = Array(
	"general" => Array(
		"builder"	=>	"form",
		"icon"		=>	"general.png",
		"label"		=>	"gen_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SITE_TITLE","SITE_DESCRIPTION","SITE_AUTHOR","SITE_DATE","GRAVATAR_RATING","SITE_LOCALE","DEFAULT_ENCODING")
	)
);

$options = Array(
	"SITE_TITLE"		=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"gen_1",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_DESCRIPTION"	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"gen_2",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_AUTHOR"		=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"gen_3",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SITE_DATE"			=>	Array(
		"type"		=>	"DateTextBox",
		"label"		=>	"gen_4",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"GRAVATAR_RATING"=>	Array(
		"type"		=>	"Select",
		"label"		=>	"gen_5",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	Array(
			array(
				"id"	=>	"g",
				"label"	=>	"G"
			),
			array(
				"id"	=>	"pg",
				"label"	=>	"PG"
			),
			array(
				"id"	=>	"r",
				"label"	=>	"R"
			),
			array(
				"id"	=>	"x",
				"label"	=>	"X"
			)
		)
	),
	"SITE_LOCALE"		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"gen_7",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_locale_options()
	),
	"DEFAULT_ENCODING"		=>	Array(
		"type"		=>	"ValidationTextBox",
		"label"		=>	"gen_6",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>