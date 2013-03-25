<?php

/**
 * Reserved "comodojo" application and mehtod.
 * 
 * It serves requests in the reserver comodojo application space, such as comodojo.login or comodojo.version
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class system_reserved extends application {
	
	public function init() {
		$this->add_application_method('getCapabilities', 'getCapabilities', Array(), 'No description available, sorry.',false);
	}
	
	public function getCapabilities($params) {
		return array(
			'faults_interop' => array (
				'specUrl' => "http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php",
				'specVersion' => 20010516
			)
		);
	}
	
}

?>