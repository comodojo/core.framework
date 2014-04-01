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

comodojo_load_resource('services_management');

class servicesmanager extends application {
	
	public function init() {
		$this->add_application_method('get_services', 'getServices', Array(), 'No description yes, sorry', false);
		$this->add_application_method('get_service', 'getService', Array("name"), 'No description yes, sorry', false);
		$this->add_application_method('enable_service', 'enableService', Array("name"), 'No description yes, sorry', false);
		$this->add_application_method('disable_service', 'disableService', Array("name"), 'No description yes, sorry', false);
		$this->add_application_method('delete_service', 'deleteService', Array("name"), 'No description yes, sorry', false);
		$this->add_application_method('new_service', 'newService', Array("name","type","supported_http_methods"), 'No description yes, sorry', false);
		$this->add_application_method('edit_service', 'editService', Array("name"), 'No description yes, sorry', false);
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

	public function getService($params) {
		$service = new services_management();
		try {
			$s = $service->get_service($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function enableService($params) {
		$service = new services_management();
		try {
			$s = $service->enable_service($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function disableService($params) {
		$service = new services_management();
		try {
			$s = $service->disable_service($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function deleteService($params) {
		$service = new services_management();
		try {
			$s = $service->delete_service($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $params["name"];
	}

	public function newService($params) {
		$service = new services_management();
		try {
			$s = $service->new_service($params);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function editService($params) {
		$service = new services_management();
		try {
			$s = $service->edit_service($params);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

}

?>