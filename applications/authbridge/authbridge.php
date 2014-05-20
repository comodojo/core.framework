<?php

/**
 * Bridge for the comodojo remote auth users' listing
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class authbridge extends application {
	
	public function init() {
		$this->add_application_method('search', 'Search', Array('user'), 'Search for user (intended to be used remotely)',false);
	}
	
	public function Search ($attributes) {
		
	}
	
}

?>
