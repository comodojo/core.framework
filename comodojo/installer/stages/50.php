<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0030"]
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0080"],
				"name"			=>	"SITE_PATH",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_PATH'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0081"],
				"name"			=>	"SITE_URL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_URL'],
				"required"		=>	true
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0082"],
				"name"			=>	"SITE_EXTERNAL_URL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_EXTERNAL_URL'],
				"required"		=>	false
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0090"],
				"name"			=>	"SITE_THEME",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_THEME'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0083"],
				"name"			=>	"DEFAULT_ENCODING",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DEFAULT_ENCODING'],
				"required"		=>	true
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0032"],
				"name"			=>	"SITE_SUSPENDED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_SUSPENDED']
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0033"],
				"name"			=>	"SITE_SUSPENDED_MESSAGE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_SUSPENDED_MESSAGE'],
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0035"],
				"name"			=>	"CACHE_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CACHE_ENABLED']
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0034"],
				"name"			=>	"CACHE_TTL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CACHE_TTL'],
				"required"		=>	true
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0031"],
				"name"			=>	"STARTUP_CACHE_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['STARTUP_CACHE_ENABLED']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0036"],
				"name"			=>	"EVENTS_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['EVENTS_ENABLED']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0087"],
				"name"			=>	"SESSION_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SESSION_ENABLED']
			)
		);
	}			

}

?>