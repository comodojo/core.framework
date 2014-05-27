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
		$this->add_application_method('addUsers', 'add_users', Array("content"), 'DESCRIPTION',false);
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

	public function add_users($params) {
		
		if (!is_array($params["content"])) throw new Exception("Malformed multi user query");
		
		comodojo_load_resource('users_management');

		$return = Array();

		$users = new users_management();

		foreach ($params["content"] as $param) {

			if (!isset($param["userName"]) OR !isset($param["userPass"]) OR !isset($param["email"])) {

				array_push($return,Array(
					"userName"	=>	"undefined",
					"status"	=>	"Malformed user format"
				));
				continue;

			}

			try {
				
				$attributes = Array("enabled" => true);

				if (isset($param["userRole"])) $attributes["userRole"] = $param["userRole"];
				if (isset($param["authentication"])) $attributes["authentication"] = $param["authentication"];
				if (isset($param["completeName"])) $attributes["completeName"] = $param["completeName"];
				if (isset($param["birthday"])) $attributes["birthday"] = $param["birthday"];
				if (isset($param["gender"])) $attributes["gender"] = $param["gender"];
				if (isset($param["url"])) $attributes["url"] = $param["url"];
				if (isset($param["gravatar"])) $attributes["gravatar"] = $param["gravatar"];

				$result = $users->add_user($param['userName'], $param['userPass'], $param['email'], $attributes, false);

				array_push($return,Array(
					"userName"		=>	$param['userName'],
					"status"		=>	true,
					"completeName"	=>	isset($param["completeName"]) ? $param["completeName"] : $param['userName'],
					"userRole"		=>	isset($param["userRole"]) ? $param["userRole"] : COMODOJO_REGISTRATION_DEFAULT_ROLE,
					"enabled"		=>	true
				));

			} catch (Exception $e) {
				
				array_push($return,Array(
					"userName"	=>	$param['userName'],
					"status"	=>	$e->getMessage()
				));

			}
			
		}

		return $return;

	}

	public function Search($params) {

		comodojo_load_resource('users_management');

		try {

			$users = new users_management();
			$result = $users->search($params['pattern'], $params['realm']);

		} catch (Exception $e) {
			throw $e;
		}
		
		$return = $this->abstract_results($result, $params['realm']);

		return $return;

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
				"type"		=> "rpc"
			);
		}

		foreach ($ldaps as $ldap) {
			$servers[$ldap["name"]] = Array(
				"server"	=> $ldap["server"],
				"searchfields"	=> $ldap["searchfields"],
				"base"		=> $ldap["base"],
				"type"		=> "ldap"
			);
		}
		
		return $servers;

	}

	private final function abstract_results($result, $realm) {

		$servers = $this->get_auth_servers();

		switch ($servers[$realm]["type"]) {

			case "local":
			case "rpc":
				foreach ($result as $r) {
					$r["realm"] = strtolower($realm);
				}
				$return = $result;
				break;

			case "ldap":
				$return = Array();
				$fields = Array();

				$base_pools = Array();
				$base = explode(',', $servers[$realm]["base"]);
				foreach ($base as $b) {
					$be = explode('=', $b);
					array_push($base_pools, $be[1]);
				}
				$suffix = '@'.implode('.', $base_pools);

				if (!empty($servers[$realm]["searchfields"])) {
					foreach (explode(",", $servers[$realm]["searchfields"]) as $field) {
						array_push($fields, strtolower($field));
					}
				}

				foreach ($result as $r) {
					if (empty($fields)) {
						$userName = isset($r["name"]) ? $r["name"] : (isset($r["uid"]) ? $r["uid"] : NULL);
						$completeName = isset($r["displayName"]) ? $r["displayName"] : (isset($r["cn"]) ? $r["cn"] : NULL);
						$email = isset($r["mail"]) ? $r["mail"] : $userName.$suffix;
						$description = isset($r["description"]) ? $r["description"] : NULL;
						if (is_null($userName)) continue;
						array_push($return, Array(
							"userName"		=> $userName,
							"completeName"	=> $completeName,
							"email"			=> $email,
							"description"	=> $description
						));
					}
					else {
						array_push($return, Array(
							"userName"		=> isset($r[$fields[0]]) ? $r[$fields[0]] : NULL,
							"completeName"	=> isset($r[$fields[1]]) ? $r[$fields[1]] : NULL,
							"email"			=> isset($r[$fields[2]]) ? $r[$fields[2]] : NULL,
							"description"	=> isset($r[$fields[3]]) ? $r[$fields[3]] : NULL
						));
					}
				}
				break;

		}

		return $return;

	}

}

?>