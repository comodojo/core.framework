<?php

/**
 * An app to permit distracted users to recover their password
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class pwdrecover extends application {
	
	public function init() {
		$this->add_application_method('sendEmail', 'recover_by_email', Array('email'), 'Send a mail to address specified containing how to recover your password.',false);
	}

	public function recover_by_email($attributes) {
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
