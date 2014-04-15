<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0037"]
			),
			//array(
			//	"type"			=>	"Select",
			//	"label"			=>	$this->i18n["0038"],
			//	"name"			=>	"AUTHENTICATION_MODE",
			//	"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['AUTHENTICATION_MODE'],
			//	"options"		=>	array(
			//							array(
			//								"id"		=>	'local',
			//								"label"		=>	$this->i18n["0092"]	
			//							),
			//							array(
			//								"id"		=>	'ldapfiltered',
			//								"label"		=>	$this->i18n["0093"]
			//							),
			//							array(
			//								"id"		=>	'ldapunfiltered',
			//								"label"		=>	$this->i18n["0094"]
			//							),
			//							array(
			//								"id"		=>	'rpc',
			//								"label"		=>	$this->i18n["0095"]
			//							)
			//						)
			//),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0088"],
				"name"			=>	"SESSION_AUTHENTICATED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SESSION_AUTHENTICATED']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0040"],
				"name"			=>	"AUTHENTICATION_CACHE_ENABLED",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['AUTHENTICATION_CACHE_ENABLED']
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0052"],
				"name"			=>	"AUTHENTICATION_CACHE_TTL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['AUTHENTICATION_CACHE_TTL'],
				"required"		=>	true
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0039"],
				"name"			=>	"REGISTRATION_MODE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['REGISTRATION_MODE']
			),
			array(
				"type"			=>	"OnOffSelect",
				"label"			=>	$this->i18n["0064"],
				"name"			=>	"REGISTRATION_AUTHORIZATION",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['REGISTRATION_AUTHORIZATION']
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0065"],
				"name"			=>	"REGISTRATION_DEFAULT_ROLE",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['REGISTRATION_DEFAULT_ROLE'],
				"required"		=>	true
			),
			array(
				"type"			=>	"ValidationTextBox",
				"label"			=>	$this->i18n["0138"],
				"name"			=>	"REGISTRATION_TTL",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['REGISTRATION_TTL'],
				"required"		=>	true
			),
			//array(
			//	"type"			=>	"TextBox",
			//	"label"			=>	$this->i18n["0041"],
			//	"name"			=>	"EXTERNAL_RPC_SERVER",
			//	"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['EXTERNAL_RPC_SERVER'],
			//),
			//array(
			//	"type"			=>	"TextBox",
			//	"label"			=>	$this->i18n["0136"],
			//	"name"			=>	"EXTERNAL_RPC_PORT",
			//	"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['EXTERNAL_RPC_PORT'],
			//),
			//array(
			//	"type"			=>	"ValidationTextBox",
			//	"label"			=>	$this->i18n["0098"],
			//	"name"			=>	"EXTERNAL_RPC_TRANSPORT",
			//	"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['EXTERNAL_RPC_TRANSPORT'],
			//	"required"		=>	true
			//),
			//array(
			//	"type"			=>	"OnOffSelect",
			//	"label"			=>	$this->i18n["0042"],
			//	"name"			=>	"EXTERNAL_RPC_MODE",
			//	"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['EXTERNAL_RPC_MODE']
			//),
			//array(
			//	"type"			=>	"TextBox",
			//	"label"			=>	$this->i18n["0043"],
			//	"name"			=>	"EXTERNAL_RPC_KEY",
			//	"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['EXTERNAL_RPC_KEY'],
			//),
			array(
				"type"			=>	"Select",
				"label"			=>	$this->i18n["0137"],
				"name"			=>	"GRAVATAR_RATING",
				"value"			=>	$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GRAVATAR_RATING'],
				"options"		=>	array(
										array(
											"id"		=>	"g",
											"label"		=>	"g"
										),
										array(
											"id"		=>	"pg",
											"label"		=>	"pg"
										)
										,
										array(
											"id"		=>	"r",
											"label"		=>	"r"
										)
										,
										array(
											"id"		=>	"x",
											"label"		=>	"x"
										)
									)
			)
		);
	}			

}

?>