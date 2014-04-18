<?php

/**
 * Test user authentication on multiple realms
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class test_auth extends application {
	
	public function init() {
		$this->add_application_method('login', 'Login', Array('userName','userPass'), 'Test user credentials on multiple realm',false);
	}
	
	public function Login($params) {

		comodojo_load_resource('authentication');

		try{
			$a = new authentication();
			$to_return = $a->testlogin($params['userName'], $params['userPass'], isset($params['realm']) ? $params['realm'] : null);
		}
		catch (Exception $e){
			throw $e;
		}

		return $to_return;

	}
	
}

?>