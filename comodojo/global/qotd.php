<?php
 
 /**
  * Minimal Quote Of The Day provider
  * 
  * It WILL NOT return any error, just false if no quotes' file found
  * 
  * @package	Comodojo ServerSide Core Packages
  * @author		comodojo.org
  * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
  * @version	__CURRENT_VERSION__
  * @license	GPL Version 3
  */

function get_quotes() {
	if (@defined('COMODOJO_SITE_PATH')) {
		$comodojo_quotes = file_get_contents(COMODOJO_SITE_PATH.'comodojo/others/quotes');
	}
	elseif (@defined('COMODOJO_BOOT_PATH')) {
		$comodojo_quotes = file_get_contents(COMODOJO_BOOT_PATH.'comodojo/others/quotes');
	}
	else {
		return false;
	}
	$comodojo_quotes_array = explode("\n",$comodojo_quotes);
	array_shift($comodojo_quotes_array);
	return $comodojo_quotes_array;
}
  
function get_quote() {
	$comodojo_quotes_array = get_quotes();
	return $comodojo_quotes_array[rand(1,sizeof($comodojo_quotes_array)-1)];
}

function get_quotes_store() {
	$comodojo_quotes_array = get_quotes();
	$toReturn = Array();
	foreach($comodojo_quotes_array as $id=>$quote) {
		array_push($toReturn, Array('id'=>$id, 'quote'=>$quote));
	}
	return $toReturn;
}

function loadHelper_qotd() { return false; }

?>