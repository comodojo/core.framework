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

class keychainmanager extends application {
	
	public function init() {
		$this->add_application_method('get_keychains', 'getKeychains', Array(), 'No description yes, sorry', false);
	}

	public function getKeychains($params) {

		comodojo_load_resource('keychain');

		try {
			$k = new keychain();
			$result = $k->get_keychains();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result;

	}
	
}

?>
