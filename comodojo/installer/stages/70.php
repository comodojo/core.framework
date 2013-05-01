<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0044"]
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0045"],
				"name"			=>	"LDAP_SERVER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_SERVER'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0046"],
				"name"			=>	"LDAP_PORT",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_PORT'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0047"],
				"name"			=>	"LDAP_DC",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_DC'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0048"],
				"name"			=>	"LDAP_OTHER_DN",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_OTHER_DN'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0049"],
				"name"			=>	"LDAP_LISTER_USERNAME",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_LISTER_USERNAME'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0050"],
				"name"			=>	"LDAP_LISTER_PASSWORD",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_LISTER_PASSWORD'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0066"],
				"name"			=>	"LDAP_DEFAULT_FILTER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_DEFAULT_FILTER'],
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0051"],
				"name"			=>	"LDAP_COMPATIBILE_MODE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['LDAP_COMPATIBILE_MODE']
			)
		);
	}			

}

?>