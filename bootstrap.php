<?php

/** 
 * bootstrap.php
 * 
 * Frontend (JSON) for the comodojo role_mapper.
 * 
 * Please see role_mapper file to see how it works.
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

require 'comodojo/global/comodojo_basic.php';

class bootstrap extends comodojo_basic {
	
	public $script_name = 'bootstrap.php';
	
	public $use_session_transport = true;
	
	public function logic($attributes) {
		
		$to_return = "comodojo.debug('Starting comodojo bootstrap process');\n";
		
		try {
			comodojo_load_resource('role_mapper');
			$mapper = new role_mapper();
		}
		catch (Exception $e) {
			comodojo_debug('Error n°'.$e->getCode().' bootstrapping comodojo: '.$e->getMessage(),'ERROR','bootstrap');
			throw $e;
		}
		
		$this->header_params['statusCode'] = 200;
		$this->header_params['contentType'] = 'text/javascript';
		$this->header_params['charset'] = COMODOJO_DEFAULT_ENCODING;
		
		$properties = $mapper->get_applications_properties();
		
		$autostart = $mapper->get_autostart();
		
		$to_return .= "comodojo.bus._registeredApplications = " . array2json($properties) . "; \n";
		$to_return .= "comodojo.bus._runningApplications = []; \n";
		
		$to_return .= "comodojo.debug('Comodojo bootstrap process completed');\n";
		
		foreach($autostart as $key => $val) $to_return .= "comodojo.app.start('" . $val . "');\n";
		
		return $to_return;
		
	}
	
	public function error($error_name, $error_detail) {
		
		$index = "comodojo.debug('".$error_name.": " . $error_detail . "');\n"; 
		$index .= "comodojo.error.critical('".$error_name.": " . $error_detail . "');\n";
		
		set_header(Array(
			'statusCode'	=>	200,
			'contentType'	=> 'text/javascript',
			'charset'		=>	'UTF-8'
		), strlen($index));
		
		return $index;
		
	}
	
}

$bootstrap = new bootstrap();

?>