<?php

/**
 * customization.php
 * 
 * Customization options for comodojo installer.
 * 
 * Changing those values will change default installation values.
 * 
 * In example, turning "enableAdvanced" into "false", will force installer
 * to not show "personalize advanced settings" to user.
 * 
 * @package		Comodojo Installer
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
	
@session_start();
	
$comodojoCustomization = array(
	
	"banner"	=>	"comodojo/images/logo.png",

	"components"	=>	array(
		"base"			=>	true,
		"events"		=>	true,
		"test"			=>	true,
		"services"		=>	true,
		"cron"			=>	true,
		"keychains"		=>	true,
	),
	
	"supportedLocales"	=>	array(
		array(
			"label"		=>	"auto",
			"id"		=>	"auto"//,
			//"default"	=>	true
		),
		array(
			"label"		=>	"English",
			"id"		=>	"en"//,
			//"default"	=>	false
		),
		array(
			"label"		=>	"Italiano",
			"id"		=>	"it"//,
			//"default"	=>	false
		)
	),
	
	"minPPHRequired"	=>	"5.3.0",

	"stages"			=>	Array(0,1,10,20,30,40,50,60,70,80,85,90,100),

	"defaultBaseValues"	=>	Array(
		
		/****** CONSTANTS ******/
		"UNIQUE_IDENTIFIER"				=>	random(),
		"PUBLIC_IDENTIFIER"				=>	random(),
		"SESSION_IDENTIFIER"			=>	random(),
		
		"CONFIGURATION_FOLDER"			=>	'comodojo/configuration/', /* DO NOT CHANGE */ 
		"APPLICATION_FOLDER"			=>	'applications/', /* DO NOT CHANGE */ 
		"HOME_FOLDER"					=>	'home/',//.random().'/',
		
		"USERS_FOLDER"					=>	'home/',//.random().'/',
		"TEMP_FOLDER"					=>	'temp/',//.random().'/',
		"FILESTORE_FOLDER"				=>	'filestore/',//.random().'/',
		"CACHE_FOLDER"					=>	'cache/',//.random().'/',
		"THUMBNAILS_FOLDER"				=>	'thumbs/',//.random().'/',
		"SERVICE_FOLDER"				=>	'services/',//.random().'/',
		"CRON_FOLDER"					=>	'cron/',//.random().'/',
		
		"DB_HOST"						=>	'localhost',
		"DB_PORT"						=>	3306,
		"DB_NAME"						=>	'comodojo',
		"DB_USER"						=>	'root',
		"DB_PASSWORD"					=>	'root',
		"DB_PREFIX"						=>	'comodojo_',
		"DB_DATA_MODEL"					=>	'MYSQLI',
		
		"STARTUP_CACHE_ENABLED"			=>	1,
		
		"GLOBAL_DEBUG_ENABLED"			=>	0,
		"GLOBAL_DEBUG_LEVEL"			=>	'INFO',
		/****** CONSTANTS ******/
		
		/****** NOT IN DB ******/
		"ADMIN_USER"				=>	'admin',
		"ADMIN_MAIL"				=>	'admin@localhost',
		"ADMIN_PASSWORD"			=>	random(8),
		/****** NOT IN DB ******/
		
		/****** IN DB ******/
		"SITE_PATH"					=>	COMODOJO_SITE_PATH,
		"SITE_URL"					=>	COMODOJO_SITE_URL,
		"SITE_EXTERNAL_URL"			=>	NULL,
		"CACHE_ENABLED"				=>	1,
		"CACHE_TTL"					=>	14400,
		"SITE_AUTHOR"				=>	'comodojo.org',
		"SITE_TITLE"				=>	'Comodojo core',
		"SITE_DESCRIPTION"			=>	'Your new comodojo core installation',
		"SITE_DATE"					=>	date('Y-m-d'),
		
		"SITE_LOCALE"				=>	'auto',
		"SITE_DEFAULT_CONTAINER"	=>	'main',
		"DEFAULT_ENCODING"			=>	'UTF-8',
		"SITE_THEME"				=>	'comodojo',
		"SITE_THEME_DOJO"			=>	'claro',
		"GRAVATAR_RATING"			=>	'g',
		
		"JS_REQUIRES"				=>	'[{"name":"dojo.aspect"},{"name":"dojo.data.ItemFileReadStore"},{"name":"dojo.data.ItemFileWriteStore"},{"name":"dijit.form.Button"},{"name":"dijit.Dialog"},{"name":"dijit.layout.ContentPane"}]',
		"JS_BASE_URL"				=>	'comodojo/javascript/dojo/',
		"JS_XD_LOADING"				=>	0,
		"JS_XD_LOCATION"			=>	'http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojo/dojo.js',
		"JS_XD_TIMEOUT"				=>	10,
		"JS_DEBUG"					=>	1,
		"JS_DEBUG_POPUP"			=>	0,
		"JS_DEBUG_DEEP"				=>	0,
		/**
		 * Comodojo Session
		 */
		"SESSION_ENABLED"			=>	0,
		"SESSION_AUTHENTICATED"		=>	0,
		/**
		 * Site status
		 */
		"SITE_SUSPENDED"			=>	0,
		"SITE_SUSPENDED_MESSAGE"	=>	'<p>Site in maintenance mode, please come back later</p>',
		/**
		 * Events
		 */
		"EVENTS_ENABLED"			=>	1,
		/**
		 * Shell
		 */
		"SHELL_ENABLED"				=>	1,
		/**
		 * REST Services
		 */
		"SERVICES_ENABLED"			=>	0,
		/**
		 * Authentication & registration
		 */
		"AUTHENTICATION_MODE"			=>	0,
		"AUTHENTICATION_CACHE_ENABLED"	=>	0,
		"AUTHENTICATION_CACHE_TTL"		=>	259200,
		"REGISTRATION_MODE"				=>	0,
		"REGISTRATION_AUTHORIZATION"	=>	0,
		"REGISTRATION_DEFAULT_ROLE"		=>	3,
		"REGISTRATION_TTL"				=>	604800,
		/**
		 * LDAP
		 */
		"LDAP_SERVER"			=>	'',
		"LDAP_PORT"				=>	636,
		"LDAP_DC"				=>	'',
		"LDAP_OTHER_DN"			=>	'',
		"LDAP_DEFAULT_FILTER"	=>	'samaccountname=',
		"LDAP_LISTER_USERNAME"	=>	'',
		"LDAP_LISTER_PASSWORD"	=>	'',
		"LDAP_COMPATIBILE_MODE"	=>	1,
		/**
		 * Mail
		 */
		"SMTP_SERVER"			=>	'localhost',
		"SMTP_PORT"				=>	25,
		"SMTP_SERVICE"			=>	1,
		"SMTP_AUTHENTICATED"	=>	0,
		"SMTP_SECURITY"			=>	0,
		"SMTP_USER"				=>	'',
		"SMTP_PASSWORD"			=>	'',
		"SMTP_ADDRESS"			=>	'',
		/**
		 * External RPC
		 */
		"EXTERNAL_RPC_SERVER"		=>	'',
		"EXTERNAL_RPC_PORT"			=>	80,
		"EXTERNAL_RPC_MODE"			=>	0,
		"EXTERNAL_RPC_TRANSPORT"	=>	'XML',
		"EXTERNAL_RPC_KEY"			=>	'',
		/**
		 * Local RPC
		 */
		"RPC_ENABLED"			=>	0,
		"RPC_MODE"				=>	0,
		"RPC_ALLOWED_TRANSPORT"	=>	'XML,JSON',
		"RPC_KEY"				=>	random(),
		/**
		 * CRON JOBS
		 */
		"CRON_ENABLED"					=>	1,
		"CRON_MULTI_THREAD_ENABLED"		=>	1,
		"CRON_NOTIFICATION_MODE"		=>	'DISABLED', //ALWAYS,FAILURES
		"CRON_NOTIFICATION_ADDRESSES"	=>	''			
		
	)	
	
);

$comodojoCustomization["defaultBaseValues"]["SITE_TAGS"] = array(
	array(
		"name"		=>	"description",
		"content"	=>	""
	),
	array(
		"name"		=>	"keywords",
		"content"	=>	""
	),
	array(
		"name"		=>	"author",
		"content"	=>	""
	),
	array(
		"name"		=>	"Expires",
		"content"	=>	""
	),
	array(
		"name"		=>	"Pragma",
		"content"	=>	""
	),
	array(
		"name"		=>	"Refresh",
		"content"	=>	""
	)
);

$comodojoCustomization["defaultBaseValues"]["BOOTSTRAP"] = array(

	0				=>	array(),

	1				=>	array("chpasswd","filepicker","folderpicker","chmod","cacheman","controlpanel"),

	2				=>	array("chpasswd","filepicker","folderpicker","chmod"),

	3				=>	array("chpasswd","filepicker","folderpicker","chmod"),

	"persistent"	=>	array("about","comodojo_menubar","set_locale","license","helloworld","qotd")

);
	
?>