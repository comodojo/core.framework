<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0100"]
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0053"],
				"name"			=>	"RPC_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['RPC_ENABLED'],
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0099"],
				"name"			=>	"RPC_ALLOWED_TRANSPORT",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['RPC_ALLOWED_TRANSPORT'],
				"required"		=>	true
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0054"],
				"name"			=>	"RPC_MODE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['RPC_MODE']
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0055"],
				"name"			=>	"RPC_KEY",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['RPC_KEY'],
			),
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0131"]
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0132"],
				"name"			=>	"SERVICES_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SERVICES_ENABLED']
			),
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0133"]
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0134"],
				"name"			=>	"SHELL_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SHELL_ENABLED']
			),
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0130"]
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0126"],
				"name"			=>	"CRON_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_ENABLED']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0127"],
				"name"			=>	"CRON_MULTI_THREAD_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_MULTI_THREAD_ENABLED']
			),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0128"],
				"name"			=>	"CRON_NOTIFICATION_MODE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_NOTIFICATION_MODE'],
				"options"		=>	array(
										array(
											"value"	=>	'DISABLED',
											"name"	=>	"DISABLED"
										),
										array(
											"value"	=>	'FAILURE',
											"name"	=>	"DISABLED"
										),
										array(
											"value"	=>	'ALL',
											"name"	=>	"ALL"
										)
									)
			),
			array(
				"type"			=>	"TextBox",
				"label"			=>	$this->i18n["0129"],
				"name"			=>	"CRON_NOTIFICATION_ADDRESSES",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_NOTIFICATION_ADDRESSES'],
			)
		);
	}			

}

?>