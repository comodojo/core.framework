<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class chpasswd extends application {
	
	public function init() {
		$this->add_application_method('change_password', 'changePassword', Array('userPass','newUserPass'), 'chpasswd.change_password(userPass,newUserPass): change password for logged-in user.\n\nIt require:\n\n -userPass\tstring\tThe current user password\n\n -newUserPass\tstring\tThe new user password',false);
	}
	
	public function changePassword($params) {
		comodojo_load_resource('users_management');
		if (!defined('COMODOJO_USER_NAME') OR @is_null(COMODOJO_USER_NAME)) {
			throw new Exception("No user logged-in", 10001);
		}
		try{
			$um = new users_management();
			$um->change_user_password(COMODOJO_USER_NAME, $params['userPass'], $params['newUserPass']);
		}
		catch (Exception $e){
			throw $e;
		}
	}
	
}

?>
