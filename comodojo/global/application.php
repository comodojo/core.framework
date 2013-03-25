<?php

/**
 * application.php
 * 
 * Standard extendable method for comodojo apps.
 * 
 * Every new application that would be called via kernel should extend this class.
 *
 * The constant COMODOJO_APPLICATION_BASIC is defined by kernel at runtime. 
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
*/

class application {
	
	private $application_declared_methods = Array();
	
	/**
	 * Registered methods will be something like:
	 * $[METHOD] = Array(0->RELATIVE_CLASS_METHOD,1->REQUESTED_PARAMETERS,2->DESCRIPTION,3->CACHE)
	 */
	private $application_registered_methods = Array();
	
	public final function add_application_method($request_method,$class_method,$required_parameters,$description=null,$cache=false) {
		array_push($this->application_declared_methods,Array('RM'=>$request_method,'CM'=>$class_method,'RP'=>$required_parameters,'DE'=>$description,'CH'=>$cache));
	}
	
	public final function get_registered_method($request_method) {
		if (!isset($this->application_registered_methods[$request_method])) return false;
		else return $this->application_registered_methods[$request_method];
	}
	
	public final function get_registered_methods() {
		$methods = Array();
		foreach ($this->application_registered_methods as $key => $value) {
			if ($key == 'methods') continue;
			$methods[$key] = Array(
				"description"=>$value[2],
				"parameters" =>!count($value[1]) ? '-' : $value[1]
			);
		}
		return $methods;
		//return $this->application_registered_methods;
	}
	
	public final function __construct() {
		$this->init();
		foreach ($this->application_declared_methods as $declared_method) {
			if (!method_exists($this, $declared_method['CM'])) {
				comodojo_debug('Error adding application method '.$declared_method['RM'].': relative method '.$declared_method['CM'].' does not exists.','ERROR','applications');
				continue;
			}
			else {
				$this->application_registered_methods[$declared_method['RM']] = Array($declared_method['CM'],$declared_method['RP'],$declared_method['DE'],$declared_method['CH']);
				comodojo_debug('Added application method '.$declared_method['RM'].' with relative method '.$declared_method['CM'],'INFO','applications');
			}
		}
		$this->application_registered_methods['methods'] = Array('get_registered_methods',Array(),'',false);
	}
	
	public function init() {}
}

?>