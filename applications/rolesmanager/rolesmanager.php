<?php

/**
 * Add, remove, edit roles
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class rolesmanager extends application {
	
	public function init() {
		$this->add_application_method('getRoles', 'get_roles', Array(), 'Get defined roles, no extra parameter required.',false);
		$this->add_application_method('addRole', 'add_role', Array('reference','description'), 'Add new role.',false);
		$this->add_application_method('deleteRole', 'delete_role', Array('id'), 'Delete existing role, identified by id.',false);
		$this->add_application_method('editRole', 'edit_role', Array('id','reference','description'), 'Update existing role, identified by id.',false);
	}

	public function get_roles($params) {

		comodojo_load_resource('roles_management');
		
		try {

			$r = new roles_management();
			$result = $r->get_roles();

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function add_role($params) {

		comodojo_load_resource('roles_management');
		
		try {

			$r = new roles_management();
			$result = $r->add_role($params['reference'], $params['description']);

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function delete_role($params) {

		comodojo_load_resource('roles_management');
		
		try {

			$r = new roles_management();
			$result = $r->delete_role($params['id']);

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function edit_role($params) {

		comodojo_load_resource('roles_management');
		
		try {

			$r = new roles_management();
			$result = $r->edit_role($params['id'],$params['reference'],$params['description']);

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	
}

?>
