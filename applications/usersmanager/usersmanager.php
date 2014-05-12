<?php

/**
 * Add, remove, edit users
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class usersmanager extends application {
	
	public function init() {
		$this->add_application_method('getUsersRolesRealms', 'get_users_roles_realms', Array(), 'DESCRIPTION',false);
		$this->add_application_method('enableUser', 'enable_user', Array("userName"), 'DESCRIPTION',false);
		$this->add_application_method('disableUser', 'disable_user', Array("userName"), 'DESCRIPTION',false);
		$this->add_application_method('deleteUser', 'delete_user', Array("userName"), 'DESCRIPTION',false);
		$this->add_application_method('getUser', 'get_user', Array("userName"), 'DESCRIPTION',false);
		$this->add_application_method('updateUser', 'update_user', Array("userName"), 'DESCRIPTION',false);
		$this->add_application_method('saveUser', 'save_user', Array("userName"), 'DESCRIPTION',false);
	}

	public function get_users_roles_realms($params) {

		comodojo_load_resource('roles_management');
		comodojo_load_resource('users_management');
		
		try {

			$roles = new roles_management();
			$users = new users_management();

			$result = Array(
				"users"	=>	$users->get_users(false, false, false),
				"roles"	=>	$roles->get_roles(),
				"realms"=>	$this->get_auth_servers()
			);

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function enable_user($params) {

		comodojo_load_resource('users_management');
		
		try {

			$users = new users_management();
			$result = $users->enable_user($params['userName']);

		} catch (Exception $e) {
			throw $e;
		}

		return Array(
			"userName"	=>	$params['userName'],
			"enabled"	=>	$result
		);

	}

	public function disable_user($params) {

		comodojo_load_resource('users_management');
		
		try {

			$users = new users_management();
			$result = $users->disable_user($params['userName']);

		} catch (Exception $e) {
			throw $e;
		}

		return Array(
			"userName"	=>	$params['userName'],
			"enabled"	=>	!$result
		);

	}

	public function delete_user($params) {

		comodojo_load_resource('users_management');
		
		try {

			$users = new users_management();
			$result = $users->delete_user($params['userName']);

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function get_user($params) {

		comodojo_load_resource('users_management');
		
		try {

			$users = new users_management();
			$result = $users->get_user_extensive($params['userName'], false);

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function update_user($params) {

	}

	public function save_user($params) {

	}

	private final function get_auth_servers() {

		$rpcs = json2array(COMODOJO_AUTHENTICATION_RPCS);

		$ldaps = json2array(COMODOJO_AUTHENTICATION_LDAPS);

		$servers = Array();

		$servers["local"] = Array(
			"server"	=>	"local",
			"type"		=>	"local"
		);

		foreach ($rpcs as $rpc) {
			$servers[$rpc["name"]] = Array(
				"server"	=> $rpc["server"],
				"port"		=> $rpc["port"],
				"transport"	=> $rpc["transport"],
				"sharedkey"	=> $rpc["sharedkey"],
				"enabled"	=> $rpc["enabled"],
				"type"		=> "rpc"
			);
		}

		foreach ($ldaps as $ldap) {
			$servers[$ldap["name"]] = Array(
				"server"	=> $ldap["server"],
				"port"		=> $ldap["port"],
				"dn"		=> $ldap["dn"],
				"version"	=> $ldap["version"],
				"ssl"		=> $ldap["ssl"],
				"tls"		=> $ldap["tls"],
				"enabled"	=> $ldap["enabled"],
				"type"		=> "ldap"
			);
		}

		return $servers;

	}
	
}

?>
