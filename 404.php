<?php

/**
 * 404.php
 * 
 * Default 404 - Not Found error page
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 
require 'comodojo/global/comodojo_basic.php';

class fourzerofour extends comodojo_basic {
	
	public $script_name = '404.php';
	
	public $use_session_transport = true;
	
	public $require_valid_session = false;
	
	public $do_authentication = false;
	
	public $header_params = Array(
		'statusCode'	=>	404,
		'contentType'	=> 'text/html',
		'charset'		=>	'UTF-8'
	);
	
	public function logic($attributes) {
		
		include COMODOJO_BOOT_PATH.'comodojo/global/qotd.php';
		
		$index = file_get_contents(COMODOJO_BOOT_PATH . "comodojo/templates/error.html");
				
		$index = str_replace("*_ERRORNAME_*","This page could not be found",$index);
		$index = str_replace("*_ERRORDETAILS_*",'The content you are looking for does not exist or it has been removed... go back, friend, go back.',$index);
		$index = str_replace("*_ERRORQUOTE_*","<em>".get_quote()."</em>",$index);
				
		set_header(Array(
			'statusCode'	=>	404,
			'contentType'	=> 'text/html',
			'charset'		=>	'UTF-8'
		), strlen($index));
		
		return $index;
		
	}
	
}

$fourzerofour = new fourzerofour();

?>