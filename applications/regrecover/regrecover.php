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

class regrecover extends application {
	
	public function init() {
		$this->add_application_method('send_new_email', 'sendNewEmail', Array('email'), 'No description available, sorry.',false);
	}
	
	public function sendNewEmail($attributes) {

		comodojo_load_resource('registration');

		$r = new registration();

		try {
			$r->send_new_notification($attributes['email']);
		} catch (Exception $e) {
			throw $e;
		}

		return true;

	}

}

?>
