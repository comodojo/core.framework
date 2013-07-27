<?php

class stage extends stage_base {

	public function output() {

		$this->back_button_disabled = true;

		return array(
			array(
				"type"		=>	"info",
				"content"	=>	$this->i18n["0002"],
				),
			
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0009"],
				"name"			=>	"DB_DATA_MODEL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_DATA_MODEL'],
				"options"		=>	array(
					array(
						"id"	=>	"MYSQLI",
						"label"	=>	"MySQLi"
						),
					array(
						"id"	=>	"MYSQL",
						"label"	=>	"MySQL"
						),
					array(
						"id"	=>	"MYSQL_PDO",
						"label"	=>	"MySQL (PDO)"
						),
					array(
						"id"	=>	"SQLITE_PDO",
						"label"	=>	"SQLite (PDO)"
						),
					array(
						"id"	=>	"POSTGRESQL",
						"label"	=>	"PostgreSQL"
						),
					array(
						"id"	=>	"ORACLE_PDO",
						"label"	=>	"SQLite (PDO)"
						),
					array(
						"id"	=>	"INFORMIX_PDO",
						"label"	=>	"Informix (PDO)"
						),
					array(
						"id"	=>	"DBLIB_PDO",
						"label"	=>	"DBLib/MSSQL (PDO)"
						),
					array(
						"id"	=>	"DB2",
						"label"	=>	"DB2"
						)
					)
				),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0003"],
				"name"			=>	"DB_HOST",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_HOST'],
				"required"		=>	true
				),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0004"],
				"name"			=>	"DB_PORT",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PORT'],
				"required"		=>	true
				),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0005"],
				"name"			=>	"DB_NAME",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_NAME'],
				"required"		=>	true
				),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0006"],
				"name"			=>	"DB_USER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_USER'],
				"required"		=>	true
				),
			array(
				"type"			=>	"PasswordTextBox",
				"label"			=>	$this->i18n["0007"],
				"name"			=>	"DB_PASSWORD",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PASSWORD'],
				"required"		=>	true
				),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0008"],
				"name"			=>	"DB_PREFIX",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PREFIX']
				)
			);
	}			

}

?>