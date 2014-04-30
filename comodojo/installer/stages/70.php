<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0044"]
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0045"],
				"name"			=>	"GMAPS_PRELOAD",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GMAPS_PRELOAD'],
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0047"],
				"name"			=>	"GMAPS_SENSOR",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GMAPS_SENSOR'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0048"],
				"name"			=>	"GMAPS_APIKEY",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GMAPS_APIKEY'],
			)
		);
	}			

}

?>