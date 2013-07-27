<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0056"]
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0057"],
				"name"			=>	"SMTP_SERVER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_SERVER'],
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0058"],
				"name"			=>	"SMTP_PORT",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_PORT'],
				"required"		=>	true
			),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0063"],
				"name"			=>	"SMTP_SERVICE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_SERVICE'],
				"options"		=>	array(
										array(
											"id"	=>	0,
											"label"	=>	"SMTP"
										),
										array(
											"id"	=>	1,
											"label"	=>	"mail"
										),
										array(
											"id"	=>	2,
											"label"	=>	"sendmail"
										)
									)
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0059"],
				"name"			=>	"SMTP_AUTHENTICATED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_AUTHENTICATED']
			),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0067"],
				"name"			=>	"SMTP_SECURITY",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_SECURITY'],
				"options"		=>	array(
										array(
											"id"	=>	0,
											"label"	=>	"No"
										),
										array(
											"id"	=>	1,
											"label"	=>	"ssl"
										),
										array(
											"id"	=>	2,
											"label"	=>	"tls"
										)
									)
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0060"],
				"name"			=>	"SMTP_USER",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_USER'],
			),
			array(
				"type"			=>	"PasswordTextBox",
				"label"			=>	$this->i18n["0061"],
				"name"			=>	"SMTP_PASSWORD",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_PASSWORD'],
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0062"],
				"name"			=>	"SMTP_ADDRESS",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SMTP_ADDRESS'],
			)
		);
	}			

}

?>