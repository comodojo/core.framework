<?php

/**
 * Provide user authentication to entire framework;
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class authentication {

/*********************** PUBLIC VARS *********************/
	public $loginFromSession = false;
	public $loginFromExternal = false;
/*********************** PUBLIC VARS *********************/

/********************** PRIVATE VARS *********************/
	private $userName = false;
	private $userPass = false;
	private $userData = false;
	private $userData_remote = false;
	private $authVia = false;
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Login a user
	 * 
	 * It's the standard interface to login user in comodojo.
	 * According to personal profile, user will be authenticated via:
	 * - local database
	 * - ldap
	 * - external authenticator via RPC interface
	 * 
	 * PLEASE NOTE: authentication via external server, if autoadd=true option will also create user profile
	 *  if a valid ldap correspondece is found.
	 * 
	 * @param	string	$userName	The name of the user to login
	 * @param	string	$userPass	The password for the user to login
	 * 
	 * @return	array|bool			An array (if successful login) containing user information ("userId","userRole",
	 * 								"completeName","fromGravatar","eMail","birthDate","gender","url") or false; exception on errors
	 */
	public function login($userName, $userPass) {
		
		comodojo_load_resource('database');
		comodojo_load_resource('events');

		if (!$userName OR !$userPass) {
			comodojo_debug('Invalid username or password provided','ERROR','authentication');
			throw new Exception("Invalid username or password provided", 1901);
		}
		
		$this->userName = $userName;
		$this->userPass = $userPass;

		$events = new events();
		
		// FIRST, check if user IS defined locally
		try {
			$this->userData = $this->get_user_local_definition($userName);
			if (!$this->userData) {
				$isValid = $this->login_undefined();
			}
			else if (!$this->userData["enabled"]) {
				$isValid = false;
			}
			else {
				$isValid = $this->login_defined();
			}
		} catch (Exception $e) {
			throw $e;
		}

		$events->record($this->loginFromExternal ? 'user_external_login' : 'user_login', $userName, !$isValid ? false : true);

		if ($isValid) {
			unset($this->userData["enabled"]);
			unset($this->userData["authentication"]);
			return $this->userData;
		}
		else {
			return false;
		}

		return $isValid == true ? $this->userData : false;

	}

	public function testlogin($userName, $userPass, $realm = null) {

		comodojo_load_resource('database');

		if (!$userName OR !$userPass) {
			comodojo_debug('Invalid username or password provided','ERROR','authentication');
			throw new Exception("Invalid username or password provided", 1901);
		}
		
		$this->userName = $userName;
		$this->userPass = $userPass;

		try {

			$this->userData = $this->get_user_local_definition($userName);
			
			$re = empty($realm) ? null : trim($realm);

			if (!$this->userData AND $re == 'local') {
				$this->authVia = 'local';
				comodojo_debug('Cannot authenticate user '.$this->userName.' via local database','WARNING','authentication');
				$isValid = false;
			}
			elseif (!$this->userData) {

				$isValid = $this->login_undefined($re, true);

			}
			else if (!$this->userData["enabled"]) {

				throw new Exception("User administratively disabled", 1906);

			}
			else {
				
				$isValid = $this->login_defined($re, true);

			}

		} catch (Exception $e) {
			throw $e;
		}

		if ($isValid) {
			unset($this->userData["enabled"]);
			unset($this->userData["authentication"]);
		}

		return Array(
			"via" => $this->authVia,
			"data"=> $isValid == true ? $this->userData : false
		);

	}

	/**
	 * Logout an user
	 * 
	 * This function does nothing but record logout event.
	 * 
	 * This because logout/session/define/... directives are managed completely by
	 * the new comodojo_basic lib.
	 * 
	 * @param	string	$userName	The name of the user to logout
	 * 
	 * @return	array|bool			An array (if successful login) containing user information ("userId","userRole",
	 * 								"completeName","fromGravatar","eMail","birthDate","gender","url") or false; exception on errors
	 */
	public function logout($userName) {
		comodojo_load_resource('events');
		$events = new events();
		return $events->record('user_logout', $userName);
	}
/********************* PUBLIC METHODS ********************/

/********************* PRIVATE METHODS *******************/
	
	private final function login_defined($realm=null, $test=false) {

		$servers = $this->get_auth_servers();

		// automatically select realm
		if (is_null($realm)) {

			if (strtolower($this->userData["authentication"]) == 'local') {

				comodojo_debug('Starting local authentication for user '.$this->userName,'INFO','authentication');
				
				$this->authVia = 'local';

				try {

					$login = $this->validate_user_local();

				} catch (Exception $e) {

					throw $e;
					
				}

				return $login;
				
			}
			elseif(isset($servers[$this->userData["authentication"]])) {

				try {

					$this->authVia = $this->userData["authentication"];
					$se = $servers[$this->userData["authentication"]];
					switch(strtolower($se["type"])) {
						case 'ldap':
							comodojo_debug('Starting ldap authentication for user '.$this->userName.' via '.$this->userData["authentication"],'INFO','authentication');
							$login = $this->validate_user_ldap(
								$se["server"],
								$se["port"],
								$se["dn"],
								$se["version"],
								$se["ssl"],
								$se["tls"]
							);
						break;
						case 'rpc':
							comodojo_debug('Starting rpc authentication for user '.$this->userName.' via '.$this->userData["authentication"],'INFO','authentication');
							$login = $this->validate_user_external_rpc(
								$se["server"],
								$se["port"],
								$se["transport"],
								$se["sharedkey"]
							);
						break;
						default:
							throw new Exception("Invalid external authentication server", 1905);
						break;
					}
					if ($login AND $test === false) {
						$this->user_to_cache($this->userName, $this->userPass);
						$this->update_user();
					}

				} catch (Exception $e) {

					comodojo_debug('External auth error: '.$e->getCode().' - '.$e->getMessage(),'ERROR','authentication');
					if ( in_array($e->getCode(), Array(1403,1501,1504)) AND $test !== false AND !$cache_runs) {
						$login = $this->validate_user_cache();
					}
					else throw $e;
					
				}

				return $login;

			}
			else {

				throw new Exception("Invalid external authentication server", 1905);

			}

		}
		// try to authenticate user via realm override
		else {

			$this->authVia = trim($realm);

			if (strtolower(trim($realm)) == 'local') {

				comodojo_debug('Starting local authentication for user '.$this->userName,'INFO','authentication');

				try {
				
					$login = $this->validate_user_local();

				} catch (Exception $e) {
					
					throw $e;
					
				}

				return $login;

			}
			elseif(isset($servers[trim($realm)])) {

				try {

					$se = $servers[trim($realm)];
					switch(strtolower($se["type"])) {
						case 'ldap':
							comodojo_debug('Starting ldap authentication for user '.$this->userName.' via '.$realm,'INFO','authentication');
							$login = $this->validate_user_ldap(
								$se["server"],
								$se["port"],
								$se["dn"],
								$se["version"],
								$se["ssl"],
								$se["tls"]
							);
						break;
						case 'rpc':
							comodojo_debug('Starting rpc authentication for user '.$this->userName.' via '.$realm,'INFO','authentication');
							$login = $this->validate_user_external_rpc(
								$se["server"],
								$se["port"],
								$se["transport"],
								$se["sharedkey"]
							);
						break;
						default:
							throw new Exception("Invalid external authentication server", 1905);
						break;
					}
					if ($login AND $test === false) {
						$this->user_to_cache($this->userName, $this->userPass);
						$this->update_user();
					}

				} catch (Exception $e) {
					
					if ( in_array($e->getCode(), Array(1403,1501,1504)) AND $test !== false) $login = $this->validate_user_cache();
					else throw $e;

				}

				return $login;

			}
			else {

				throw new Exception("Invalid external authentication server", 1905);

			}

		}

	}

	private final function login_undefined($realm=null, $test=false) {

		$servers = $this->get_auth_servers($test ? false : true);

		if (is_null($realm)) {

			$cache_runs = false;

			foreach ($servers as $s => $se) {

				try {

					switch(strtolower($se["type"])) {
						case 'ldap':
							comodojo_debug('Starting AUTO ldap authentication for user '.$this->userName.' via '.$s,'INFO','authentication');
							$login = $this->validate_user_ldap(
								$se["server"],
								$se["port"],
								$se["dn"],
								$se["version"],
								$se["ssl"],
								$se["tls"]
							);
						break;
						case 'rpc':
							comodojo_debug('Starting AUTO rpc authentication for user '.$this->userName.' via '.$s,'INFO','authentication');
							$login = $this->validate_user_external_rpc(
								$se["server"],
								$se["port"],
								$se["transport"],
								$se["sharedkey"]
							);
						break;
						default:
							throw new Exception("Invalid external authentication server", 1905);
						break;

					}

					if ($login) {
						$this->add_user($s, $test);
						$this->authVia = $s;
						return true;
					}

				} catch (Exception $e) {

					// no excemption here, only log (just in case)
					//throw $e;
					comodojo_debug('External auth error: '.$e->getCode().' - '.$e->getMessage(),'ERROR','authentication');
					if ( in_array($e->getCode(), Array(1403,1501,1504)) AND $test === false AND !$cache_runs) {
						$login = $this->validate_user_cache();
						$cache_runs = true;
					}

				}

			}

			return false;

		}
		else {

			if (isset($servers[trim($realm)])) {

				$this->authVia = trim($realm);

				try {
					
					$se = $servers[trim($realm)];
					switch(strtolower($se["type"])) {
						case 'ldap':
							comodojo_debug('Starting ldap authentication for user '.$this->userName.' via '.$realm,'INFO','authentication');
							$login = $this->validate_user_ldap(
								$se["server"],
								$se["port"],
								$se["dn"],
								$se["version"],
								$se["ssl"],
								$se["tls"]
							);
						break;
						case 'rpc':
							comodojo_debug('Starting rpc authentication for user '.$this->userName.' via '.$realm,'INFO','authentication');
							$login = $this->validate_user_external_rpc(
								$se["server"],
								$se["port"],
								$se["transport"],
								$se["sharedkey"]
							);
						break;
						default:
							throw new Exception("Invalid external authentication server", 1905);
						break;
					}

					if ($login) {
						$this->add_user(trim($realm), $test);
					}

					return $login;

				} catch (Exception $e) {

					comodojo_debug('External auth error: '.$e->getCode().' - '.$e->getMessage(),'ERROR','authentication');
					throw $e;
					
				}
				
			}
			else {
				throw new Exception("Invalid external authentication server", 1905);
			}

		}

	}

	/**
	 * Get user definition
	 */
	private final function get_user_local_definition($userName) {

		try {

			$db = new database();
			$result = $db->table('users')
				->keys(Array("userId","userRole","enabled","authentication","completeName","gravatar","email","birthday","gender","url"))
				->where("userName","=",$userName)
				->get();

			if ($result['resultLength'] == 1) {
				$to_return = $result['result'][0];
			} 
			else {
				//comodojo_debug('Unknown user','ERROR','authentication');
				//throw new Exception("Invalid username", 1903);
				$to_return = false;
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		comodojo_debug('User '.$this->userName.($to_return ? ' ' : ' NOT').' found in local database','INFO','authentication');
		return $to_return;

	}

	/**
	 * Parse authentication servers
	 */
	private final function get_auth_servers($autoAddfilter=false) {

		$rpcs = json2array(COMODOJO_AUTHENTICATION_RPCS);

		$ldaps = json2array(COMODOJO_AUTHENTICATION_LDAPS);

		$servers = Array();

		foreach ($rpcs as $rpc) {
			if ($autoAddfilter AND $rpc["autoadd"] == false) continue;
			if ($rpc["enabled"] == false) continue;
			$servers[$rpc["name"]] = Array(
				"server"	=> $rpc["server"],
				"port"		=> $rpc["port"],
				"transport"	=> $rpc["transport"],
				"sharedkey"	=> $rpc["sharedkey"],
				"autoadd"	=> $rpc["autoadd"],
				"type"		=> "rpc"
			);
		}

		foreach ($ldaps as $ldap) {
			if ($autoAddfilter AND $ldap["autoadd"] == false) continue;
			if ($ldap["enabled"] == false) continue;
			$servers[$ldap["name"]] = Array(
				"server"	=> $ldap["server"],
				"port"		=> $ldap["port"],
				"dn"		=> $ldap["dn"],
				"version"	=> $ldap["version"],
				"ssl"		=> $ldap["ssl"],
				"tls"		=> $ldap["tls"],
				"autoadd"	=> $ldap["autoadd"],
				"type"		=> "ldap"
			);
		}

		return $servers;

	}

	/**
	 * Validate user via local database
	 */
	private	final function validate_user_local() {
		
		try {
			$db = new database();
			$result = $db->table('users')
			->keys("userId")
			->where("userName","=",$this->userName)
			->and_where("userPass","=",!$this->loginFromSession ? md5($this->userPass) : $this->userPass)
			->and_where("enabled","=",1)
			->and_where("authentication","=",'local')
			->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		if ($result["resultLength"] == 1) {
			comodojo_debug('User '.$this->userName.' authenticated via local database','INFO','authentication');
			return true;
		}
		else {
			comodojo_debug('Cannot authenticate user '.$this->userName.' via local database','WARNING','authentication');
			return false;
		}
		
	}
	
	/**
	 * Validate user via external LDAP server
	 */
	private	final function validate_user_ldap($server, $port, $dn, $version, $ssl, $tls) {
		
		if ($this->loginFromSession) throw new Exception("External auth not supported when authenticating from session", 1904);

		comodojo_load_resource('ldap');
		
		try {
			$ldap = new ldap($server, $port);
			$lauth = $ldap->dn($dn)->version($version)->ssl($ssl)->tls($tls)->auth($this->userName, $this->userPass);
		}
		catch (Exception $e){
			comodojo_debug('There is a problem with ldap: '.$e->getMessage(),'WARNING','authentication');
			throw $e;
		}
		if ($lauth) {
			comodojo_debug('User '.$this->userName.' authenticated via ldap','INFO','authentication');
			return true;
		}
		else {
			comodojo_debug('Cannot authenticate user '.$this->userName.' via ldap','WARNING','authentication');
			return false;
		}

	}

	/**
	 * Validate user using external RPC authenticator
	 */
	private	final function validate_user_external_rpc($server, $port=80, $transport='XML', $sharedkey=null) {
		
		if ($this->loginFromSession) throw new Exception("External auth not supported when authenticating from session", 1904);

		comodojo_load_resource('rpc_client');
		
		$id = strtoupper($transport) == 'JSON' ? true : false;

		try {
			$rpc = new rpc_client($server, $transport, $sharedkey, $port, 'POST');
			$result = $rpc->send('comodojo.login', Array($this->userName, $this->userPass) , $id);
		} catch (Exception $e) {
			//Exceptions are suppressed. If rpc doesn't work, this will return false,
			// but execution will continue.
			comodojo_debug('There was an error authenticating user from rpc '.$server.': '.$e->getMessage(),'ERROR','authentication');
			return false;
		}

		$this->userData_remote = $result;

		return true;
		
	}

	/**
	 * Validate user from cache if:
	 * - users cache enabled;
	 * - user already defined in local user database;
	 * - cache is not expired
	 */
	private	final function validate_user_cache() {

		if (!COMODOJO_AUTHENTICATION_CACHE_ENABLED) return false;
		
		comodojo_load_resource('database');

		try {
			$db = new database();
			$result = $db->table('users_cache')
			->keys("id")
			->where("userName","=",$userName)
			->and_where("userPass","=",!$this->loginFromSession ? md5($userPass) : $userPass)
			->and_where("ttl",">",strtotime('now'))
			->get();
		}
		catch (Exception $e){
			//Exceptions here are suppressed. If cache doesn't work, this will return false,
			// but execution will continue.
			//throw $e;
			comodojo_debug('There was an error reading user from cache: '.$e->getMessage(),'ERROR','authentication');
			return false;
		}
		
		if ($result["resultLength"] == 1) {
			comodojo_debug('User '.$userName.' authenticated via local cache','INFO','authentication');
			return true;
		}
		else {
			comodojo_debug('No cache for user '.$userName.' or cache expired','WARNING','authentication');
			return false;
		}

	}
	
	/**
	 * Cache user one authenticated if
	 * - users cache enabled;
	 */
	private final function user_to_cache($userName, $userPass) {
				
		if (!COMODOJO_AUTHENTICATION_CACHE_ENABLED) return;
		
		comodojo_debug('Caching user '.$userName,'INFO','authentication');

		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$db->table('users_cache')
			->keys(Array("userName","userPass","userRole","ttl"))
			->values(Array($userName,$userPass,0,strtotime('now')+COMODOJO_AUTHENTICATION_CACHE_TTL))
			->store();
		}
		catch (Exception $e){
			//Exceptions here are suppressed. If cache doesn't work, this will return false,
			// but execution will continue.
			//throw $e;
			comodojo_debug('There was an error storing user to cache: '.$e->getMessage(),'ERROR','authentication');
		}
		
	}

	private final function add_user($source, $test=false) {

		if ($test === false) {
			comodojo_debug('Adding user '.$this->userName.' found in '.$source,'INFO','authentication');
			comodojo_load_resource('users_management');
		}

		if (!$this->userData_remote) {
			$email = $this->userName.'@localhost';
			$params = Array(
				"authentication"=> $source,
				"enabled"		=> true,
			);
			$data = Array(
				"authentication"=> $source,
				"enabled"		=> true,
				"userRole"		=> COMODOJO_REGISTRATION_DEFAULT_ROLE,
				"completeName"	=> null,
				"gravatar"		=> false,
				"email"			=> $this->userName.'@localhost',
				"birthday"		=> null,
				"gender"		=> null,
				"url"			=> null
			);
		}
		else {
			$email = $this->userData_remote["email"];
			$params = Array(
				"authentication"=> $source,
				"enabled"		=> true,
				"completeName"	=> $this->userData_remote["completeName"],
				"gravatar"		=> $this->userData_remote["gravatar"],
				"birthday"		=> $this->userData_remote["birthday"],
				"gender"		=> $this->userData_remote["gender"],
				"url"			=> $this->userData_remote["url"]
			);
			$data = Array(
				"authentication"=> $source,
				"enabled"		=> true,
				"userRole"		=> COMODOJO_REGISTRATION_DEFAULT_ROLE,
				"completeName"	=> $this->userData_remote["completeName"],
				"gravatar"		=> $this->userData_remote["gravatar"],
				"email"			=> $this->userData_remote["email"],
				"birthday"		=> $this->userData_remote["birthday"],
				"gender"		=> $this->userData_remote["gender"],
				"url"			=> $this->userData_remote["url"]
			);
		}

		if ($test === false) {
			try {
				$um = new users_management();
				$um->add_user_from_external = true;
				$result = $um->add_user($this->userName, random(32), $email, $params);
				$data["userId"] = $result;
			} catch (Exception $e) {
				throw $e;
			}
		}
		else {
			$data["userId"] = -1;
		}

		$this->userData = $data;
		
	}

	private final function update_user() {

		if (!$this->userData_remote) return;

		if (
			$this->userData_remote["completeName"] != $this->userData["completeName"] OR
			$this->userData_remote["gravatar"] != $this->userData["gravatar"] OR
			$this->userData_remote["email"] != $this->userData["email"] OR
			$this->userData_remote["birthday"] != $this->userData["birthday"] OR
			$this->userData_remote["gender"] != $this->userData["gender"] OR
			$this->userData_remote["url"] != $this->userData["url"]
		) {

			comodojo_debug('Updating user '.$this->userName,'INFO','authentication');

			comodojo_load_resource('users_management');

			$params = Array(
				"completeName"	=> $this->userData_remote["completeName"],
				"gravatar"		=> $this->userData_remote["gravatar"],
				"email"			=> $this->userData_remote["email"],
				"birthday"		=> $this->userData_remote["birthday"],
				"gender"		=> $this->userData_remote["gender"],
				"url"			=> $this->userData_remote["url"]
			);

			try {
				$um = new users_management();
				$result = $um->edit_user($this->userName, $params);
			} catch (Exception $e) {
				throw $e;
			}

			$this->userData["completeName"] = $this->userData_remote["completeName"];
			$this->userData["gravatar"] = $this->userData_remote["gravatar"];
			$this->userData["email"] = $this->userData_remote["email"];
			$this->userData["birthday"] = $this->userData_remote["birthday"];
			$this->userData["gender"] = $this->userData_remote["gender"];
			$this->userData["url"] = $this->userData_remote["url"];

		}

	}
//********************* PRIVATE METHODS *******************/

}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_authentication
 */
function loadHelper_authentication() { return false; }

?>