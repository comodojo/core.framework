<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class userdialog extends application {
	
	public function init() {
		$this->add_application_method('get_users', 'getUsers', Array(), 'No description yet',false);
	}
	
	public function getUsers($params) {
		comodojo_load_resource('users_management');
		$um = new users_management();
		return $um->get_users(16);
	}
	
}

?>
