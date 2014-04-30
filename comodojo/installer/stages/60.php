<?php

class stage extends stage_base {

	public function output() {

		return array(
			array(
				"type"			=>	"info",
				"content"		=>	$this->i18n["0037"]
			),
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