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

class pwdrecover extends application {
	
	public function init() {
		$this->add_application_method('recover_by_email', 'recoverByEmail', Array('email'), 'Send a mail to address specified containing how to recover your password.',false);
	}

	public function recoverByEmail($attributes) {
		comodojo_load_resource('users_management');
		try{
			$um = new users_management();
			$um->user_recovery_request($attributes['email']);
		}
		catch (Exception $e){
			throw $e;
		}
		return true;
	}
	
}

?>
