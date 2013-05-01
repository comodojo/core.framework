<?php

class stage extends stage_base {

	public function output() {

		global $comodojoCustomization;

		//try the database connection, or throw an exception
		try {
			$db = new database(
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_HOST'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_DATA_MODEL'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_NAME'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PORT'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PREFIX'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_USER'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PASSWORD']
				);
			unset($db);
		} catch (Exception $e) {
			throw $e;
		}

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0013"]
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0014"],
				"name"			=>	"SITE_TITLE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_TITLE'],
				"required"		=>	true
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0015"],
				"name"			=>	"SITE_DESCRIPTION",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_DESCRIPTION'],
				"required"		=>	false
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0016"],
				"name"			=>	"SITE_AUTHOR",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_AUTHOR'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0068"],
				"name"			=>	"ADMIN_USER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'],
				"required"		=>	true
			),
			array(
				"type"			=>	"EmailTextBox",
				"label"			=>	$this->i18n["0124"],
				"name"			=>	"ADMIN_MAIL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_MAIL'],
				"required"		=>	true
			),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0017"],
				"name"			=>	"SITE_LOCALE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_LOCALE'],
				"options"		=>	$comodojoCustomization['supportedLocales']
			)
		);
	}			

}

?>