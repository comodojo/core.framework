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
		$this->add_application_method('realms', 'Realms', Array(), 'Get available authentication realms',false);
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

	public function Realms($params) {

		$rpcs = json2array(COMODOJO_AUTHENTICATION_RPCS);

		$ldaps = json2array(COMODOJO_AUTHENTICATION_LDAPS);

		$servers = Array("local");

		foreach ($rpcs as $rpc) {
			if ($rpc["enabled"] == false) continue;
			array_push($servers, $rpc["name"]);
		}

		foreach ($ldaps as $ldap) {
			if ($ldap["enabled"] == false) continue;
			array_push($servers, $ldap["name"]);
		}

		return $servers;

	}
	
}

?>