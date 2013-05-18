<?php

/**
 * taskmanager
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class taskmanager extends application {
	
	public function init() {
		$this->add_application_method('get_load', 'getLoad', Array(), 'No description available, sorry.',false);
	}
	
	public function getLoad() {
		
		if (function_exists('sys_getloadavg')) return sys_getloadavg();
		else return Array('N/A','N/A','N/A');
		
	}
	
}

?>