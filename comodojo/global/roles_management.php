<?php

/**
 * Basic functions to create, delete, edit comodojo roles
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class roles_management {

/********************** PRIVATE VARS *********************/
	/**
	 * Restrict roles management to administrator, default associated with COMODOJO_RESTRICT_MANAGEMENT
	 * 
	 * If disabled, it will not check user role (=1).
	 *
	 * @default true;
	 */
	private $restrict_management_to_administrators = COMODOJO_RESTRICT_MANAGEMENT;
	
	/**
	 * Protected roles; ids in this array will not be editable
	 * 
	 * @default Array(1,2,3);
	 */
	private $protected_roles = Array(1,2,3);
	
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/

	/**
	 * List local roles
	 * 
	 * @return	array	List of currently defined roles, with id, reference and description
	 */
	public function get_roles() {
	 	
		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$result = $db->table("roles")->keys(Array("id","reference","description"))->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result["result"];
		
	}
	
	/**
	 * Get role by id
	 * 
	 * @param	integer	$id	Role reference
	 *
	 * @return	Array				Role attributes in case of success, false otherwise, exception in case of error
	 */
	public function get_role($id) {
	 	
	 	if (!is_int($id)) {
			comodojo_debug('Invalid role identifier','ERROR','roles_management');
			throw new Exception("Invalid role identifier", 2702);
		}
		
		comodojo_load_resource('database');
		
		try {
			
			$db = new database();
			$result = $db->table("roles")->keys(Array("reference","description"))->where("id","=",$id)->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result["result"];
		
	}
	
	/**
	 * Add a new role to system
	 * 
	 * @param	integer	$reference			New role's reference number
	 * @param	string	$description		[optional] New role's description
	 *
	 * @return	integer	Id of newly created role
	 */
	public function add_role($reference, $description) {
		
		comodojo_load_resource('database');
		
		if (!is_int($reference) OR !is_scalar($description)) {
			comodojo_debug('Invalid reference or description','ERROR','roles_management');
			throw new Exception("Invalid reference or description", 2701);
		}		
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage roles','ERROR','roles_management');
			throw new Exception("Only administrators can manage roles", 2704);
		}		
		
		try {
			$db = new database();
			$result = $db->return_id()->table("roles")->keys(Array("reference","description"))->values(Array($reference, $description))->store();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result["transactionId"];
		
	}
	
	/**
	 * Modify exsisting role
	 * 
	 * @param	integer	$id					Identifier of role to be modified
	 * @param	integer	$reference			New role's reference number
	 * @param	string	$description		[optional] New role's description
	 *
	 * @return	integer	Id of newly created role
	 */
	public function edit_role($id, $reference, $description) {
		
		comodojo_load_resource('database');
		
		if (!is_int($id)) {
			comodojo_debug('Invalid role identifier','ERROR','roles_management');
			throw new Exception("Invalid role identifier", 2702);
		}		
		
		if (in_array($id, $this->protected_roles)) {
			comodojo_debug('Cannot modify a protected role','ERROR','roles_management');
			throw new Exception("Cannot modify a protected role", 2703);
		}
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage roles','ERROR','roles_management');
			throw new Exception("Only administrators can manage roles", 2704);
		}
		
		try {
			$db = new database();
			$result = $db->table("roles")->keys(Array("reference","description"))->values(Array($reference, $description))->where("id","=",$id)->update();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result["affectedRows"] == 1 ? true : false;
		
	}
	
	/**
	 * Delete existing role
	 * 
	 * @param	integer	$id	Identifier of role to be deleted
	 *
	 * @return	bool				true in case of success, false otherwise, exception in case of error
	 */
	public function delete_role($id) {
		
		comodojo_load_resource('database');
		
		if (!is_int($id)) {
			comodojo_debug('Invalid role identifier','ERROR','roles_management');
			throw new Exception("Invalid role identifier", 2702);
		}		
		
		if (in_array($id, $this->protected_roles)) {
			comodojo_debug('Cannot modify a protected role','ERROR','roles_management');
			throw new Exception("Cannot modify a protected role", 2703);
		}	
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage roles','ERROR','roles_management');
			throw new Exception("Only administrators can manage roles", 2704);
		}
		
		try {
			$db = new database();
			$result = $db->table("roles")->where("id","=",$id)->delete();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result["affectedRows"] == 1 ? true : false;
		
	}

	/**
	 * Get role id by reference number
	 * 
	 * @param	integer	$reference	Role reference
	 *
	 * @return	int						Role id in in case of success, false otherwise, exception in case of error
	 */
	public function id_by_reference($reference) {

		if (!is_int($reference)) {
			comodojo_debug('Invalid role reference','ERROR','roles_management');
			throw new Exception("Invalid role reference", 2705);
		}

		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$result = $db->table("roles")->keys("id")->where('reference','=',$reference)->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return is_int($result["result"][0]['id']) ? $result["result"][0]['id'] : false;
	
	}
	
/********************* PUBLIC METHODS ********************/
	
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_roles_management
 */
function loadHelper_roles_management() { return false; }

?>