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
		$this->add_application_method('editUser', 'update_user', Array("userName"), 'DESCRIPTION',false);
		$this->add_application_method('addUser', 'add_user', Array("userName","userPass","email"), 'DESCRIPTION',false);
		$this->add_application_method('search', 'Search', Array("realm","pattern"), 'DESCRIPTION',false);
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

		comodojo_load_resource('users_management');
		
		$attributes = Array();

		if (isset($params["userRole"])) $attributes["userRole"] = $params["userRole"];
		if (isset($params["authentication"])) $attributes["authentication"] = $params["authentication"];
		if (isset($params["completeName"])) $attributes["completeName"] = $params["completeName"];
		if (isset($params["birthday"])) $attributes["birthday"] = $params["birthday"];
		if (isset($params["gender"])) $attributes["gender"] = $params["gender"];
		if (isset($params["url"])) $attributes["url"] = $params["url"];
		if (isset($params["gravatar"])) $attributes["gravatar"] = $params["gravatar"];

		try {

			$users = new users_management();
			$result = $users->edit_user($params['userName'], $attributes);

		} catch (Exception $e) {
			throw $e;
		}

		return true;

	}

	public function add_user($params) {

		comodojo_load_resource('users_management');
		
		$attributes = Array("enabled" => true);

		if (isset($params["userRole"])) $attributes["userRole"] = $params["userRole"];
		if (isset($params["authentication"])) $attributes["authentication"] = $params["authentication"];
		if (isset($params["completeName"])) $attributes["completeName"] = $params["completeName"];
		if (isset($params["birthday"])) $attributes["birthday"] = $params["birthday"];
		if (isset($params["gender"])) $attributes["gender"] = $params["gender"];
		if (isset($params["url"])) $attributes["url"] = $params["url"];
		if (isset($params["gravatar"])) $attributes["gravatar"] = $params["gravatar"];

		try {

			$users = new users_management();
			$result = $users->add_user($params['userName'], $params['userPass'], $params['email'], $attributes, false);

		} catch (Exception $e) {
			throw $e;
		}

		return Array(
			"userName"		=>	$params['userName'],
			"completeName"	=>	isset($params["completeName"]) ? $params["completeName"] : $params['userName'],
			"userRole"		=>	isset($params["userRole"]) ? $params["userRole"] : COMODOJO_REGISTRATION_DEFAULT_ROLE,
			"enabled"		=>	true
		);

	}

	public function Search($params) {

		comodojo_load_resource('users_management');
		
		try {

			$users = new users_management();
			$result = $users->search($params['pattern'], $params['realm']);

		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	private final function get_auth_servers(/*$extensive=false*/) {

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
				//"port"		=> $rpc["port"],
				//"transport"	=> $rpc["transport"],
				//"sharedkey"	=> $rpc["sharedkey"],
				//"enabled"	=> $rpc["enabled"],
				"type"		=> "rpc"
			);
			//if ($extensive) {
			//	$servers[$rpc["port"]][] = $rpc["port"];
			//	$servers[$rpc["transport"]][] = $rpc["transport"];
			//	$servers[$rpc["sharedkey"]][] = $rpc["sharedkey"];
			//	$servers[$rpc["enabled"]][] = $rpc["enabled"];
			//}
		}

		foreach ($ldaps as $ldap) {
			$servers[$ldap["name"]] = Array(
				"server"	=> $ldap["server"],
				//"port"		=> $ldap["port"],
				//"dn"		=> $ldap["dn"],
				//"version"	=> $ldap["version"],
				//"ssl"		=> $ldap["ssl"],
				//"tls"		=> $ldap["tls"],
				//"enabled"	=> $ldap["enabled"],
				"type"		=> "ldap"
			);
			//if ($extensive) {
			//	$servers[$ldap["port"]][] = $ldap["port"];
			//	$servers[$ldap["transport"]][] = $ldap["transport"];
			//	$servers[$ldap["sharedkey"]][] = $ldap["sharedkey"];
			//	$servers[$ldap["enabled"]][] = $ldap["enabled"];
			//}
		}
		return $servers;

	}
	
}

?>
