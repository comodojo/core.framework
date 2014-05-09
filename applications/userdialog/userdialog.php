<?php

/**
 * Simple and configurable user selector (and password validation)
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class userdialog extends application {
	
	public function init() {
		$this->add_application_method('getUsers', 'get_users', Array(), 'No description yet',false);
	}
	
	public function get_users($params) {
		comodojo_load_resource('users_management');
		$um = new users_management();
		return $um->get_users(16, true, true);
	}
	
}

?>
