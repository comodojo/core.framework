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
		
		$to_return = "comodojo.debug('Starting comodojo bootstrap process. There are:');\n";
		
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
		
		//Set registered applications
		$to_return .= "comodojo.Bus._registeredApplications = " . array2json($properties) . "; \n";
		
		// Remove running applications and autostart registers
		$to_return .= "comodojo.Bus._runningApplications = []; \n";
		$to_return .= "comodojo.Bus._autostartApplications = []; \n";
		
		$to_return .= "comodojo.debug(' - ".count($properties)." application/s to register');\n";
		$to_return .= "comodojo.debug(' - ".count($autostart)." application/s to autostart');\n";

		foreach($autostart as $key => $val) $to_return .= "comodojo.Bus.addAutostartApplication('" . $val . "');\n";
		//$to_return .= "comodojo.Bus.addAutostartApplication('qotd');\n";
		
		$to_return .= "comodojo.debug('Comodojo bootstrap process completed');\n";
		
		//$to_return .= "dojo.ready(function() {\n";
		//foreach($autostart as $key => $val) $to_return .= "comodojo.App.start('" . $val . "');\n";
		//$to_return .= "comodojo.App.start('qotd');\n";
		//$to_return .= "comodojo.debug(Comodojo bootstrap process completed);\n";
		//$to_return .= "});";

		return $to_return;
		
	}
	
	public function error($error_code, $error_content) {
		
		$index = "comodojo.debug('".$error_code.": " . $error_content . "');\n"; 
		$index .= "comodojo.Error.critical('".$error_code.": " . $error_content . "');\n";
		
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