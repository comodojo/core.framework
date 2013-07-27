<?php

class stage extends stage_base {

	public function output() {

		global $comodojoCustomization;
		require(COMODOJO_SITE_PATH . "comodojo/others/available_cdn");
		
		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0022"]
			),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0023"],
				"name"			=>	"SITE_THEME_DOJO",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_THEME_DOJO'],
				"options"		=>	array(
										array(
											"label"		=>	"tundra",
											"id"		=>	"tundra"//,
											//"default"	=>	true
										),
										array(
											"label"		=>	"soria",
											"id"		=>	"soria"//,
											//"default"	=>	false
										),
										array(
											"label"		=>	"nihilo",
											"id"		=>	"nihilo"//,
											//"default"	=>	false
										),
										array(
											"label"		=>	"claro",
											"id"		=>	"claro"//,
											//"default"	=>	false
										)
									)
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0024"],
				"name"			=>	"JS_XD_LOADING",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['JS_XD_LOADING']
			),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0079"],
				"name"			=>	"JS_XD_LOCATION",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['JS_XD_LOCATION'],
				"options"		=>	$available_cdn
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0025"],
				"name"			=>	"JS_XD_TIMEOUT",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['JS_XD_TIMEOUT'],
				"required"		=>	true
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0029"],
				"name"			=>	"JS_BASE_URL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['JS_BASE_URL'],
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0084"],
				"name"			=>	"SITE_DEFAULT_CONTAINER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_DEFAULT_CONTAINER'],
				"required"		=>	true
			)
		);
	}			

}

?>