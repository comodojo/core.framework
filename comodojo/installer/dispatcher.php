<?php

/**
 * installerDispatcher.php
 * 
 * Installer stages dispatcher
 * 
 * @package		Comodojo Installer
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

@session_start();

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

define('SITE_UNIQUE_IDENTIFIER','COMODOJO_INSTALLER');
define('COMODOJO_PUBLIC_IDENTIFIER','COMODOJO_INSTALLER_PUBLIC');

if (!isset($_SESSION[SITE_UNIQUE_IDENTIFIER]['SITE_PATH']) OR 
	!isset($_SESSION[SITE_UNIQUE_IDENTIFIER]['SITE_URL']) OR
	!isset($_SESSION[SITE_UNIQUE_IDENTIFIER]['PHP_LOCALE'])
) die("{'success':false, 'reason':'Installation cannot procede due to corrupted or invalid session values.<br>Please consider to download a fresh copy of comodojo from <a href=\"http://www.comodojo.org\">comodojo.org</a>'}");

define('COMODOJO_SITE_PATH', $_SESSION[SITE_UNIQUE_IDENTIFIER]['SITE_PATH']);
define('COMODOJO_SITE_URL', $_SESSION[SITE_UNIQUE_IDENTIFIER]['SITE_URL']);

/***********************************************************************/
/****** CHANGE TO TRUE IF YOU WANT TO DEBUG COMODOJO INSTALLATION ******/
define('COMODOJO_GLOBAL_DEBUG_ENABLED',true);
define('COMODOJO_GLOBAL_DEBUG_LEVEL','INFO');
define('COMODOJO_GLOBAL_DEBUG_FILE',null);
/****** CHANGE TO TRUE IF YOU WANT TO DEBUG COMODOJO INSTALLATION ******/
/***********************************************************************/

require(COMODOJO_SITE_PATH . "comodojo/global/common_functions.php");
require(COMODOJO_SITE_PATH . "comodojo/global/database.php");
require(COMODOJO_SITE_PATH . "comodojo/global/filesystem.php");
require(COMODOJO_SITE_PATH . "comodojo/global/mail.php");
require(COMODOJO_SITE_PATH . "comodojo/installer/customization.php");
require(COMODOJO_SITE_PATH . "comodojo/installer/stage_base.php");

if (isset($_GET['stage'])) {
	$current = array_search($_GET['stage'], $comodojoCustomization['stages']);
	if ($current === false OR $_GET['stage'] === "false"){
		$toReturn = array(
			"success"	=>	false,
			"result"	=>	"stage not valid"
		);
	}
	else {
		$values = $_POST;
		$prev = $current == 0 ? false : $comodojoCustomization['stages'][$current-1];
		$next = $current == (sizeof($comodojoCustomization['stages'])-1) ? false : $comodojoCustomization['stages'][$current+1];
		require(COMODOJO_SITE_PATH . "comodojo/installer/stages/".$comodojoCustomization['stages'][$current].".php");
		$st = new stage($prev,$next,$comodojoCustomization['stages'][$current],$values);
		$toReturn = $st->dispatch();
	}
}
else {
	$toReturn = array(
		"success"	=>	false,
		"result"	=>	"no stage specified"
	);
}

die(array2json($toReturn));

?>