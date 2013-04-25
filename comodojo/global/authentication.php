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
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Login a user
	 * 
	 * It's the standard interface to login user in comodojo.
	 * According to COMODOJO_AUTHENTICATION_MODE, user will be authenticated via:
	 * - local database
	 * - ldap (filtered)
	 * - ldap (unfiltered)
	 * - external authenticator via RPC interface
	 * 
	 * PLEASE NOTE: authentication via ldap unfiltered will also create user profile
	 *  if a valid ldap correspondece is found.
	 * 
	 * PLEASE NOTE: external user's cache will work ONLY when authenticating via RPC authenticator
	 *
	 * @param	string	$userName	The name of the user to login
	 * @param	string	$userPass	The password for the user to login
	 * 
	 * @return	array|bool			An array (if successful login) containing user information ("userId","userRole",
	 * 								"completeName","fromGravatar","eMail","birthDate","gender","url") or false; exception on errors
	 */
	public function login($userName, $userPass) {
		
		if (!$userName OR !$userPass) {
			comodojo_debug('Invalid username or password provided','ERROR','authentication');
			throw new Exception("Invalid username or password provided", 1901);
		}
		
		$this->userName = $userName;
		$this->userPass = $userPass;
		
		try {
			switch (COMODOJO_AUTHENTICATION_MODE) {
				case 0:
					comodojo_debug('Starting local authentication for user '.$userName,'INFO','authentication');
					$isValid = $this->validate_user_local();
				break;
				case 1:
					comodojo_debug('Starting ldap filtered authentication for user '.$userName,'INFO','authentication');
					$isValid = $this->validate_user_ldap_filtered();
				break;
				case 2:
					comodojo_debug('Starting ldap unfiltered authentication for user '.$userName,'INFO','authentication');
					$isValid = $this->validate_user_ldap_unfiltered();
				break;
				case 3:
					comodojo_debug('Starting RPC authenticator authentication for user '.$userName,'INFO','authentication');
					$isValid = $this->validate_user_external_rpc();
				break;
				default:
					comodojo_debug('Unsupported authentication model','ERROR','authentication');
					throw new Exception("Unsupported authentication model", 1902);
				break;
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		comodojo_load_resource('events');
		$events = new events();
		
		$events->record($this->loginFromExternal ? 'user_external_login' : 'user_login', $userName, !$isValid ? false : true);
		
		return $isValid;

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
	/**
	 * Validate user via local database
	 */
	private	function validate_user_local() {
		
		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$result = $db->table('users')
			->keys(Array("userId","userRole","completeName","gravatar","email","birthday","gender","url"))
			->where("userName","=",$this->userName)
			->and_where("userPass","=",!$this->loginFromSession ? md5($this->userPass) : $this->userPass)
			->and_where("enabled","=",1)
			->and_where("ldap","=",0)
			->and_where("rpc","=",0)
			->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		if ($result["resultLength"] == 1) {
			comodojo_debug('User '.$this->userName.' authenticated via local database','INFO','authentication');
			return $result["result"][0];
		}
		else {
			comodojo_debug('Cannot authenticate user '.$this->userName.' via local database','WARNING','authentication');
			return false;
		}
		
	}
	
	/**
	 * Validate user via external LDAP server IF AND ONLY IF user is also defined in local database 
	 */
	private	function validate_user_ldap_filtered() {
		
		comodojo_load_resource('database');
		comodojo_load_resource('ldap');
		
		try {
			$db = new database();
			$result = $db->table('users')
			->keys(Array("userId","userRole","completeName","gravatar","email","birthday","gender","url","ldap")
			->where("userName","=",$this->userName)
			->and_where("enabled","=",1))
			->and_where("rpc","=",0)
			->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		if ($result["resultLength"] == 1 AND $result["result"]['ldap'] == 0) {
			comodojo_debug('User '.$this->userName.' found in local database and defined local, now checking password locally','INFO','authentication');
			try {
				$db->cleanup();
				$result = $db->table('users')
				->keys(Array("userId","userRole","completeName","gravatar","email","birthday","gender","url"))
				->where("userName","=",$this->userName)
				->and_where("userPass","=",!$this->loginFromSession ? md5($this->userPass) : $this->userPass)
				->and_where("enabled","=",1)
				->and_where("ldap","=",0)
				->and_where("rpc","=",0)
				->get();
			}
			catch (Exception $e){
				throw $e;
			}
			
			if ($result["resultLength"] == 1) {
				comodojo_debug('User '.$this->userName.' authenticated via local database','INFO','authentication');
				return $result["result"][0];
			}
			else {
				comodojo_debug('Cannot authenticate user '.$this->userName.' via local database','WARNING','authentication');
				return false;
			}
		}
		else if ($result["resultLength"] == 1 AND $result["result"]['ldap'] == 1) {
			comodojo_debug('User '.$this->userName.' found in local database, now checking password on ldap','INFO','authentication');
			try {
				$ldap = new ldap();
				$lauth = $ldap->ldapAuth($this->userName, $this->userPass);
			}
			catch (Exception $e){
				//IF LDAP is unavailable check local cache (no error throw)
				comodojo_debug('There is a problem with ldap: '.$e->getMessage(),'WARNING','authentication');
				return $this->user_from_cache($this->userName, $this->userPass) ? $result["result"][0] : false;
				//throw $e;
			}
			if ($lauth) {
				comodojo_debug('User '.$this->userName.' authenticated via ldap filtered','INFO','authentication');
				$this->user_to_cache($this->userName, $this->userPass);
				return $result["result"][0];
			}
			else {
				comodojo_debug('Cannot authenticate user '.$this->userName.' via ldap filtered, no match in ldap','WARNING','authentication');
				return false;
			}
		}
		else {
			comodojo_debug('Cannot authenticate user '.$this->userName.' via ldap filtered, no match in local database','WARNING','authentication');
			return false;
		}

	}

	/**
	 * Validate user via external LDAP server and create user profile (if not yet defined locally)
	 */
	private	function validate_user_ldap_unfiltered() {
		
		comodojo_load_resource('database');
		comodojo_load_resource('ldap');
		
		try {
			$db = new database();
			$result = $db->table('users')
			->keys(Array("userId","userRole","completeName","gravatar","email","birthday","gender","url","ldap")
			->where("userName","=",$this->userName)
			->and_where("enabled","=",1))
			->and_where("rpc","=",0)
			->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		if ($result["resultLength"] == 1 AND $result["result"]['ldap'] == 0) {
			comodojo_debug('User '.$this->userName.' found in local database and defined local, now checking password locally','INFO','authentication');
			try {
				$db->cleanup();
				$result = $db->table('users')
				->keys(Array("userId","userRole","completeName","gravatar","email","birthday","gender","url"))
				->where("userName","=",$this->userName)
				->and_where("userPass","=",!$this->loginFromSession ? md5($this->userPass) : $this->userPass)
				->and_where("enabled","=",1)
				->and_where("ldap","=",0)
				->and_where("rpc","=",0)
				->get();
			}
			catch (Exception $e){
				throw $e;
			}
			
			if ($result["resultLength"] == 1) {
				comodojo_debug('User '.$this->userName.' authenticated via local database','INFO','authentication');
				return $result["result"][0];
			}
			else {
				comodojo_debug('Cannot authenticate user '.$this->userName.' via local database','WARNING','authentication');
				return false;
			}
		}
		else if ($result["resultLength"] == 1 AND $result["result"]['ldap'] == 1) {
			comodojo_debug('User '.$this->userName.' found in local database, now checking password on ldap','INFO','authentication');
			try {
				$ldap = new ldap();
				$lauth = $ldap->ldapAuth($this->userName, $this->userPass);
			}
			catch (Exception $e){
				//IF LDAP is unavailable check local cache (no error throw)
				comodojo_debug('There is a problem with ldap: '.$e->getMessage(),'WARNING','authentication');
				return $this->user_from_cache($this->userName, $this->userPass) ? $result["result"][0] : false;
				//throw $e;
			}
			if ($lauth) {
				comodojo_debug('User '.$this->userName.' authenticated via ldap filtered','INFO','authentication');
				$this->user_to_cache($this->userName, $this->userPass);
				return $result["result"][0];
			}
			else {
				comodojo_debug('Cannot authenticate user '.$this->userName.' via ldap filtered, no match in ldap','WARNING','authentication');
				return false;
			}
		}
		else {
			
			//Should cache user at first ldap login?
			
			comodojo_load_resource('user_manager');
			
			comodojo_debug('User '.$this->userName.' not found in local database, now checking on ldap','INFO','authentication');
			
			try {
				$ldap = new ldap();
				$lauth = $ldap->ldapAuth($this->userName, $this->userPass);
			}
			catch (Exception $e){
				throw $e;
			}
			
			if ($lauth) {
				comodojo_debug('User '.$this->userName.' authenticated via ldap filtered, now creating profile','INFO','authentication');
				try {
					$um = new user_manager();
					$result = $um->create_user_profile($this->userName, NULL, COMODOJO_REGISTRATION_DEFAULT_ROLE, 1, Array("ldap"=>1));
				}
				catch (Exception $e){
					throw $e;
				}
				
				comodojo_debug('User '.$this->userName.' authenticated via ldap filtered, profile correctly created','INFO','authentication');
				
				return Array(
					"userId"		=>	$result,
					"userRole"		=>	COMODOJO_REGISTRATION_DEFAULT_ROLE,
					"completeName"	=>	null,
					"gravatar"		=>	false,
					"email"			=>	null,
					"birthday"		=>	null,
					"gender"		=>	null,
					"url"			=>	null
				);
				
			}
			else {
				comodojo_debug('Cannot authenticate user '.$this->userName.' via ldap unfiltered, no match in ldap','WARNING','authentication');
				return false;
			}

		}
		
	}

	/**
	 * Validate user using external RPC authenticator
	 */
	private	function validate_user_external_rpc() {
		
		comodojo_load_resource('database');
		comodojo_load_resource('rpc_client');
		
		try {
			$db = new database();
			$result = $db->table('users')
			->keys(Array("userId","userRole","completeName","gravatar","email","birthday","gender","url","fromRpc")
			->where("userName","=",$this->userName)
			->and_where("enabled","=",1))
			->and_where("ldap","=",0)
			->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		if ($result["resultLength"] == 1 AND $result["result"]['rpc'] == 0) {
			comodojo_debug('User '.$this->userName.' found in local database and defined local, now checking password locally','INFO','authentication');
			try {
				$db->cleanup();
				$result = $db->table('users')
				->keys(Array("userId","userRole","completeName","gravatar","email","birthday","gender","url"))
				->where("userName","=",$this->userName)
				->and_where("userPass","=",!$this->loginFromSession ? md5($this->userPass) : $this->userPass)
				->and_where("enabled","=",1)
				->and_where("ldap","=",0)
				->and_where("rpc","=",0)
				->get();
			}
			catch (Exception $e){
				throw $e;
			}
			
			if ($result["resultLength"] == 1) {
				comodojo_debug('User '.$this->userName.' authenticated via local database','INFO','authentication');
				return $result["result"][0];
			}
			else {
				comodojo_debug('Cannot authenticate user '.$this->userName.' via local database','WARNING','authentication');
				return false;
			}
		}
		else if ($result["resultLength"] == 1 AND $result["result"]['rpc'] == 1) {
			//maybe user profile was updated locally, so return local values but check pwd remotely
			try {
				$client = new rpc_client();
				$response = $client->send('comodojo.login',Array("userName"=>$this->userName,"userPass"=>$this->userPass));
			}
			catch (Exception $e){
				comodojo_debug('Cannot authenticate user '.$this->userName.' via external RPC: '.$e->getMessage(),'WARNING','authentication');
				return false;
			}

			if ($response['success']) {
				comodojo_debug('User '.$this->userName.' authenticated via external RPC','INFO','authentication');
				$this->user_to_cache($this->userName, $this->userPass);
				return $result["result"][0];
			}
			else {
				comodojo_debug('Cannot authenticate user '.$this->userName.' via external RPC: user unknown or invalid pwd','WARNING','authentication');
				return false;
			}
			
		}
		else {
			comodojo_load_resource('user_manager');
			try {
				$client = new rpc_client();
				$response = $client->send('comodojo.login',Array("userName"=>$this->userName,"userPasss"=>$this->userPass));
				if ($response['success']) {
					comodojo_debug('User '.$this->userName.' authenticated via external RPC, now creating local echo','INFO','authentication');
					$um = new user_manager();
					$result = $um->create_user_profile($this->userName, NULL, COMODOJO_REGISTRATION_DEFAULT_ROLE, 1, Array("rpc"=>1));
					$this->user_to_cache($this->userName, $this->userPass);
					return $response["result"];
				}
				else {
					comodojo_debug('Cannot authenticate user '.$this->userName.' via external RPC: user unknown or invalid pwd','WARNING','authentication');
					return false;
				}
				
			}
			catch (Exception $e){
				comodojo_debug('Cannot authenticate user '.$this->userName.' via external RPC: '.$e->getMessage(),'WARNING','authentication');
				return false;
			}
			
		}
		
	}

	/**
	 * Validate user from cache if:
	 * - users cache enabled;
	 * - user already defined in local user database;
	 * - cache is not expired
	 */
	private function user_from_cache($userName, $userPass) {
		
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
	private function user_to_cache($userName, $userPass /*, $userRole (for future use) */ ) {
				
		if (!COMODOJO_AUTHENTICATION_CACHE_ENABLED) return;
		
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
//********************* PRIVATE METHODS *******************/

}

function loadHelper_authentication() { return false; }

?>