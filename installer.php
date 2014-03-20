<?php

/**
 * installer.php
 * 
 * Installer frontend
 * 
 * @package		Comodojo Installer
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

@session_unset();
@session_start();

function isInstalled() {
		
	return (is_readable(COMODOJO_SITE_PATH . "comodojo/configuration/static_configuration.php")) ? true : false;
	
}

function makeIndex() {
	
	require(COMODOJO_SITE_PATH . "comodojo/installer/customization.php");

	$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/installer/installer.html");

	//$js_via_cdn = !is_readable(COMODOJO_SITE_PATH . "comodojo/javascript/dojo/dojo.js");

	//if (!$js_via_cdn) {
		$loader = '
			<link rel="stylesheet" type="text/css" href="comodojo/javascript/dojo/resources/dojo.css" />
			<link rel="stylesheet" type="text/css" href="comodojo/javascript/dijit/themes/claro/claro.css" />
			<script type="text/javascript" src="comodojo/javascript/dojo/dojo.js" ></script>
		';
	//}
	//else {
	//	require(COMODOJO_SITE_PATH . "comodojo/others/available_cdn");
	//	$loader = '
	//		<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dojo/resources/dojo.css" />
	//		<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/dojo/1.7.2/dijit/themes/claro/claro.css" />
	//		<script type="text/javascript" src="'.$available_cdn[0]["id"].'"></script>
	//	';
	//}

	$index = str_replace("*_DOJOLOADER_*",$loader,$index);
	$index = str_replace("*_BANNER_*",$comodojoCustomization['banner'],$index);
	$index = str_replace("*_SERVERLOCALE_*",$_SESSION[SITE_UNIQUE_IDENTIFIER]['PHP_LOCALE'],$index);
	$index = str_replace("*_COMODOJOVERSION_*",comodojo_version('VERSION'),$index);
	$index = str_replace("*_COMODOJO_SITE_URL_*",COMODOJO_SITE_URL,$index);
	
	return $index;

}

function getUrl() {
	
	$http = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') . '://';
	$uri = $_SERVER['REQUEST_URI'];
	$uri = preg_replace("/\/installer.php(.*?)$/i","",$uri);
	$currentUrl = $http . $_SERVER['HTTP_HOST'] . $uri;
	$currentUrl = str_replace('%20',' ',$currentUrl);
	
	return $currentUrl;
	
}

function getPath() {
	
	$currentPath = getcwd();
	
	return $currentPath;
	
}

function throwErrorEvent() {
		
	include COMODOJO_SITE_PATH.'comodojo/global/qotd.php';
		
	$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/templates/web_error.html");
	
	$index = str_replace("*_ERRORNAME_*",'THERE WAS AN ERROR!',$index);
	$index = str_replace("*_ERRORDETAILS_*",'<strong>It seems that CoMoDojo is already installed; Remember to remove configuration files to proceede with a new installation.</strong>',$index);
	$index = str_replace("*_ERRORQUOTE_*","<em>".get_quote()."</em>",$index);
	
	@session_destroy();
	
	return $index;
		
}

define('SITE_UNIQUE_IDENTIFIER','COMODOJO_INSTALLER');
define('COMODOJO_SITE_PATH', getPath()."/");
define('COMODOJO_SITE_URL', getUrl()."/");

$_SESSION[SITE_UNIQUE_IDENTIFIER]['SITE_PATH'] = COMODOJO_SITE_PATH;
$_SESSION[SITE_UNIQUE_IDENTIFIER]['SITE_URL'] = COMODOJO_SITE_URL;
	
if (isInstalled()) {
	die(throwErrorEvent());
}

require(COMODOJO_SITE_PATH . "comodojo/global/common_functions.php");	

$_SESSION[SITE_UNIQUE_IDENTIFIER]['PHP_LOCALE'] = getLocale();
	
die(makeIndex());

?>