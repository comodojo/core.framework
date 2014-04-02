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

class qotd extends application {
	
	public function init() {
		$this->add_application_method('getMessage', 'get_message', Array(), 'qotd.get_message() returns a randomic quote string; no extra parameter is necessary',false);
	}
	
	public function get_message() {
		
		comodojo_load_resource('qotd');
		return get_quote();
		
	}	
}

?>