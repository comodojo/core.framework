<?php

/**
 * chpasswd.js
 *
 * Update or reset passwords in Comodojo mode
 *
 * @package		Comodojo Applications
 * @author		comodojo.org
 * @copyright	2010 comodojo.org (info@comodojo.org)
 */

@session_start();

include "../../configuration/siteIdentifier.php";
require("../../configuration/staticConfiguration.php");
require("../../global/commonFunctions.php");


if (!isset($_SESSION[SITE_UNIQUE_IDENTIFIER])) {
	die("fatal error");
}

if (!isset($_GET['userName']) OR !isset($_GET['userPass'])) {
	die('<p style="color:red;">No username/password defined...</p>');
}

require($_SESSION[SITE_UNIQUE_IDENTIFIER]["sitePath"] . "/comodojo/global/authentication.php");
	
$au = new authentication();

$au->userName = $_GET['userName'];
$au->userPass = $_GET['userPass'];
$au->isDebug = true;

$re = $au->testLogin();

if ($re['success']) {
	echo "<p>returned user data:</p>";
	print_r($re['result']);
}

die();

?>