<?php

/**
 * An app to permit distracted users to recover registration's email
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class regrecover extends application {
	
	public function init() {
		$this->add_application_method('sendNewEmail', 'send_new_email', Array('email'), 'No description available, sorry.',false);
	}
	
	public function send_new_email($attributes) {

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
