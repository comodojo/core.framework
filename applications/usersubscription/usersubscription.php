<?php
 
/**
 * Sign up as new user
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class usersubscription extends application {
	
	public function init() {
		$this->add_application_method('check_registration_mode', 'checkRegistrationMode', Array(), 'No description available, sorry.',false);
		$this->add_application_method('new_registration', 'newRegistration', Array('userName','userPass','email'), 'No description available, sorry.',false);
	}
	
	public function checkRegistrationMode($attributes) {
		
		return Array(
			"mode"	=>	COMODOJO_REGISTRATION_MODE,
			"auth"	=>	COMODOJO_REGISTRATION_AUTHORIZATION
		);
		
	}

	public function newRegistration($attributes) {

		comodojo_load_resource('registration');

		if (empty($attributes['completeName'])) $attributes['completeName'] = null;
		if (empty($attributes['birthday'])) $attributes['birthday'] = null;
		if (empty($attributes['gender'])) $attributes['gender'] = null;

		$r = new registration();

		try {
			$result = $r->new_request(
				$attributes['userName'],
				$attributes['userPass'],
				$attributes['email'],
				$attributes['completeName'],
				$attributes['birthday'],
				$attributes['gender']
			);
		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}
	
}

?>