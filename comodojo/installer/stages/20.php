<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0069"]
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0070"],
				"name"			=>	"CONFIGURATION_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CONFIGURATION_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0071"],
				"name"			=>	"APPLICATION_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['APPLICATION_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0135"],
				"name"			=>	"HOME_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['HOME_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0072"],
				"name"			=>	"USERS_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0073"],
				"name"			=>	"TEMP_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['TEMP_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0074"],
				"name"			=>	"FILESTORE_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['FILESTORE_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0075"],
				"name"			=>	"CACHE_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CACHE_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0076"],
				"name"			=>	"THUMBNAILS_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['THUMBNAILS_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0077"],
				"name"			=>	"SERVICE_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SERVICE_FOLDER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0078"],
				"name"			=>	"CRON_FOLDER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_FOLDER'],
				"required"		=>	true
			)
		);
	}			

}

?>