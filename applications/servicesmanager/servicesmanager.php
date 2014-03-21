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
comodojo_load_resource('services_management');

class servicesmanager extends application {
	
	public function init() {
		$this->add_application_method('get_services', 'getServices', Array(), 'No description yes, sorry', false);
	}

	public function getServices($params) {
		$service = new services_management();
		try {
			$s = $service->get_services();
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

}

?>