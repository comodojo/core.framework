<?php

/**
 * Change password
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class chpasswd extends application {
	
	public function init() {
		$this->add_application_method('changePassword', 'change_password', Array('userPass','newUserPass'), 'Change password. Usage: chpasswd.changePassword({ userPass: [OLDPASS], newUserPass: [NEWPASS] }).',false);
	}
	
	public function change_password($params) {
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
