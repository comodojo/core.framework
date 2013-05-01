<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0091"]
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0026"],
				"name"			=>	"JS_DEBUG",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['JS_DEBUG']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0027"],
				"name"			=>	"JS_DEBUG_POPUP",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['JS_DEBUG_POPUP']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0028"],
				"name"			=>	"JS_DEBUG_DEEP",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['JS_DEBUG_DEEP']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0085"],
				"name"			=>	"GLOBAL_DEBUG_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GLOBAL_DEBUG_ENABLED']
			),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0086"],
				"name"			=>	"GLOBAL_DEBUG_LEVEL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GLOBAL_DEBUG_LEVEL'],
				"options"		=>	array(
										array(
											"name"		=>	"INFO",
											"value"		=>	"INFO"
										),
										array(
											"name"		=>	"WARNING",
											"value"		=>	"WARNING"
										),
										array(
											"name"		=>	"ERROR",
											"value"		=>	"ERROR"
										)
									)
			)
		);
	}			

}

?>