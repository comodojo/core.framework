<?php

/**
 * Comodojo test environment
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class qotd extends application {
	
	public function init() {
		$this->add_application_method('get_message', 'getMessage', Array(), 'qotd.get_message() returns a randomic quote string; no extra parameter is necessary',false);
	}
	
	public function getMessage() {
		
		comodojo_load_resource('qotd');
		return get_quote();
		
	}	
}

?>