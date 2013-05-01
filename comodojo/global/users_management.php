<?php

/**
 * Provide *EVERY* function connected to user management such as add-user, change-password, ...;
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class users_management {

/********************** PRIVATE VARS *********************/
	/**
	 * Restrict password management to administrator.
	 * 
	 * If disabled, it will not check user role (=1).
	 * 
	 * @default false;
	 */
	 private $restrict_management_to_administrators = false;
/********************** PRIVATE VARS *********************/

/********************** PUBLIC VARS *********************/
	/**
	 * If true, user_add will not encrypt userPass value on user creation.
	 *
	 * @default false;
	 */
	 private $do_not_encrypt_userPass = false;
/********************** PUBLIC VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Change user password.
	 * PLEASE NOTE: user_management::change_user_password() will only works with local users or remote user via RPC
	 * 
	 * @todo	Extend also on ldap
	 * 
	 * It will check user presence and then try to change password.
	 * 
	 * @param	string	$userName		The name of the user to chpasswd
	 * @param	string	$oldPassword	The OLD password for the user to chpasswd
	 * @param	string	$newPassword	The NEW password for the user to chpasswd
	 * 
	 * @return	bool					True in case of success, false otherwise.
	 */
	public	function change_user_password($userName, $oldPassword, $newPassword) {
			
		if (empty($userName) OR empty($oldPassword) OR empty($newPassword)) {
			comodojo_debug('Invalid username, wrong old password or empty new password provided','ERROR','user_management');
			throw new Exception("Invalid username, wrong old password or empty new password provided", 2601);
		}
		
		if (is_null($userName) OR $userName != COMODOJO_USER_NAME) {
			comodojo_debug('Password can be changed only by user','ERROR','user_management');
			throw new Exception("Password can be changed only by user", 2602);
		}
		
		comodojo_load_resource('events');
		$events = new events();
		
		try{
			$presence = $this->findRegisteredUser($userName, false, false);
			if ($presence == 'LOCAL') {
				comodojo_debug('Changing local password from user: '.$userName,'INFO','user_management');
				$success = $this->change_user_password_local($userName, $oldPassword, $newPassword);
			}
			else if ($presence == 'RPC') {
				comodojo_debug('Changing remote (RPC) password from user: '.$userName,'INFO','user_management');
				$success = $this->change_user_password_external_rpc($userName, $oldPassword, $newPassword);
			}
			else if ($presence == 'LDAP') {
				comodojo_debug('Unsupported action','ERROR','user_management');
				throw new Exception("Unsupported action", 2614);
			}
			else {
				comodojo_debug('Unknown user','ERROR','user_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			$events->record('user_chpasswd', $userName, false);
			throw $e;
		}
		
		$events->record('user_chpasswd', $userName, true);
		
		return $success;
		
	}

	/**
	 * Reset user password.
	 * PLEASE NOTE: user_management::changeUserPassword() will only works with local users or remote user via RPC
	 * 
	 * @todo	Extend also on ldap
	 * 
	 * It will check user presence and then try to change password.
	 * 
	 * @param	string	$userName		The name of the user to rstpasswd
	 * 
	 * @return	bool					True in case of success, false otherwise.
	 */
	public	function reset_user_password($userName) {
			
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','user_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','user_management');
			throw new Exception("Only administrators can manage users", 2605);
		}
		
		comodojo_load_resource('events');
		$events = new events();
		
		try{
			$presence = $this->findRegisteredUser($userName, false, false);
			if ($presence == 'LOCAL') {
				comodojo_debug('Changing local password from user: '.$userName,'INFO','user_management');
				$success = $this->reset_user_password_local();
			}
			else if ($presence == 'RPC') {
				comodojo_debug('Changing remote (RPC) password from user: '.$userName,'INFO','user_management');
				$success = $this->reset_user_password_external_rpc();
			}
			else if ($presence == 'LDAP') {
				comodojo_debug('Unsupported action','ERROR','user_management');
				throw new Exception("Unsupported action", 2614);
			}
			else {
				comodojo_debug('Unknown user','ERROR','user_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			$events->record('user_rstpasswd', $userName, false);
			throw $e;
		}
		
		$events->record('user_rstpasswd', $userName, true);
		
		return $success;
		
	}
	
	/**
	 * Get the list of users in database.
	 * 
	 * Users' list does not include promised accounts.
	 * 
	 * @param	integer	$userImageDimensions	
	 * 
	 * @return	array
	 */
	public function get_users($userImageDimensions=64) {
		
		comodojo_load_resource('database');
		comodojo_load_resource('cache');
		comodojo_load_resource('user_avatar');
		
		$request = 'COMODOJO_USER_MANAGEMENT_GET_USERS_'.$userImageDimensions;
		
		try {

			$c = new cache();
			$cache = $c->get_cache($request, 'JSON', false);
			
			if ($cache !== false) {
				$to_return = $cache[2]['cache_content'];
			}
			else {
				
				$to_return = array();
				
				$db = new database();
				$db->table('users')
				->keys(Array("userName","completeName","email","gravatar"))
				->where("enabled","=",1)
				->get();

				if (!$userImageDimensions) {
					foreach ($results['result'] as $result) {
						array_push($to_return,Array("userName"=>$result['userName'],"completeName"=>$result['completeName']));
					}
				}
				else {
					foreach ($results['result'] as $result) {
						array_push($to_return,Array("userName"=>$result['userName'],"completeName"=>$result['completeName'],"userImage"=>get_user_avatar($result['userName'],$result['email'],$result['gravatar'],$userImageDimensions)));
					}
				}

				$c->set_cache(Array('cache_content'=>$to_return), $request, 'JSON', false);

			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $to_return;
	}
	
	/**
	 * Get user details
	 * 
	 * @param	string 	$userName	
	 * @param	integer	$userImageDimensions	
	 * 
	 * @return	array
	 */
	public function get_user($userName, $userImageDimensions=64) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','user_management');
			throw new Exception("Invalid username", 2604);
		}

		comodojo_load_resource('database');
		comodojo_load_resource('cache');
		comodojo_load_resource('user_avatar');
		
		$request = 'COMODOJO_USER_MANAGEMENT_GET_USER_'.$userName."_".$userImageDimensions;
		
		try {
			$c = new cache();
			$cache = $c->get_cache($request, 'JSON', false);
			if ($cache !== false) {
				$to_return = $cache[2]['cache_content'];
			}
			else {
				$db = new database();
				$db->table('users')
				->keys(Array("userName","completeName","email","gravatar"))
				->where("userName","=",$userName)
				->and_where("enabled","=",1)
				->get();

				if ($result['resultLength'] == 1) {
					$to_return = !$userImageDimensions ? Array("userName"=>$result['result'][0]['userName'],"completeName"=>$result['result'][0]['completeName']) : Array("userName"=>$result['result'][0]['userName'],"completeName"=>$result['result'][0]['completeName'],"userImage"=>get_user_avatar($result['result'][0]['userName'],$result['result'][0]['email'],$result['result'][0]['gravatar'],$userImageDimensions));
					$c->set_cache(Array('cache_content'=>$to_return), $request, 'JSON', false);
				} 
				else {
					comodojo_debug('Unknown user','ERROR','user_management');
					throw new Exception("Unknown user", 2603);
				}
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $to_return;
	}
	
	public function add_user($userName, $userPass, $email, $params=Array()) {
		
		if (empty($userName) OR empty($userPass) OR empty($email) OR !is_array($params)) {
			comodojo_debug('Invalid user parameters','ERROR','user_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','user_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		comodojo_load_resource('database');
		comodojo_load_resource('filesystem');

		if ($this->findRegisteredUser($userName, true, true)) {
			comodojo_debug('User already registered','ERROR','user_management');
			throw new Exception("User already registered", 2613);
		}

		$_params = Array(
			'userName'	=>	$userName,
			'userPass'	=>	!$this->do_not_encrypt_userPass ? md5($userPass) : $userPass,
			'email'		=>	$email,
			'userRole'	=>	!isset($params['userRole']) ? COMODOJO_REGISTRATION_DEFAULT_ROLE : (is_numeric($params['userRole']) ? $params['userRole'] : COMODOJO_REGISTRATION_DEFAULT_ROLE),
			'enabled'	=>	!isset($params['enabled']) ? false : (!$params['enabled']) ? false : true),
			'ldap'		=>	!isset($params['ldap']) ? false : (!$params['ldap']) ? false : true),
			'rpc'		=>	!isset($params['rpc']) ? false : (!$params['rpc']) ? false : true),
			'completeName'	=> !isset($params['completeName']) ? null : (is_scalar($params['completeName']) ? $params['completeName'] : null),
			'gravatar'	=>	!isset($params['gravatar']) ? false : (!$params['gravatar']) ? false : true),
			'birthday'	=>	!isset($params['birthday']) ? null : (is_scalar($params['birthday']) ? $params['birthday'] : null),
			'gender'	=>	!isset($params['gender']) ? null : (is_scalar($params['gender']) ? $params['gender'] : null),
			'url'		=>	!isset($params['url']) ? null : (filter_var($params['url'], FILTER_VALIDATE_URL) === FALSE ? null : $params['url']),
			'private_identifier'	=>	random(),
			'public_identifier'		=>	random()	
		);

		try {
			$db = new database();
			$result = $db->return_id()
			->table('users')
			->keys(Array("userName","userPass","email","userRole","enabled","ldap","rpc","completeName","gravatar","birthday","gender","url","private_identifier","public_identifier")
			->values($_params)
			->store();
			$fs = new filesystem();
			$fs->createHome($userName);
		}
		catch (Exception $e){
			throw $e;
		}

		return $result["transactionId"];

	}
	
	public function edit_user($userName,$params=Array()) {

		if (empty($userName) OR !is_array($params)) {
			comodojo_debug('Invalid user parameters','ERROR','user_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		if ($userName != COMODOJO_USER_NAME AND $this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','user_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		comodojo_load_resource('database');

		if (!$this->findRegisteredUser($userName, true, false)) {
			comodojo_debug('Unknown user','ERROR','user_management');
			throw new Exception("Unknown user", 2603);
		}

		$_keys = Array();
		$_values = Array();

		$_keys_pool = Array("email","userRole","enabled","ldap","rpc","completeName","gravatar","birthday","gender","url");

		foreach ($params as $key => $value) {
			if (in_array($key, $_keys_pool)) {
				array_push($_keys, $key);
				array_push($_values, $value);
			}
		}

		if (empty($_keys)) {
			comodojo_debug('Invalid user parameters','ERROR','user_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		try {
			$db = new database();
			$result = $db->return_id()
			->table('users')
			->keys($_keys)
			->values($_values)
			->where("userName","=",$userName)
			->update();
		}
		catch (Exception $e){
			throw $e;
		}

		return true;

	}
	
	public function delete_user($userName) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','user_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($userName != COMODOJO_USER_NAME AND $this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','user_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		comodojo_load_resource('events');
		$events = new events();
		
		try {
			$presence = $this->findRegisteredUser($userName, true, false);

			if ($presence != false) {
				comodojo_debug('Deleting user: '.$userName." defined as ".$presence,'INFO','user_management');
				$success = $this->delete_user_local($userName, true);
			}
			else {
				comodojo_debug('Unknown user','ERROR','user_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			$events->record('user_delete', $userName, false);
			throw $e;
		}
		
		$events->record('user_delete', $userName, true);
		
		return $success;
		
	}
	
	public function enable_user($userName) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','user_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','user_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		comodojo_load_resource('events');
		$events = new events();
		
		try {
			$presence = $this->findRegisteredUser($userName, true, false);
			if ($presence != false) {
				comodojo_debug('Enabling  user: '.$userName." defined as ".$presence,'INFO','user_management');
				$success = $this->enable_user_local($userName, true);
			}
			else {
				comodojo_debug('Unknown user','ERROR','user_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			$events->record('user_enable', $userName, false);
			throw $e;
		}
		
		$events->record('user_enable', $userName, true);
		
		return $success;
		
	}
	
	public function disable_user($userName) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','user_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','user_management');
			throw new Exception("Only administrators can manage users", 2605);
		}
		
		comodojo_load_resource('events');
		$events = new events();
		
		try {
			$presence = $this->findRegisteredUser($userName, true, false);
			if ($presence != false) {
				comodojo_debug('Disabling user: '.$userName." defined as ".$presence,'INFO','user_management');
				$success = $this->enable_user_local($userName, false);
			}
			else {
				comodojo_debug('Unknown user','ERROR','user_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			$events->record('user_disable', $userName, false);
			throw $e;
		}
		
		$events->record('user_disable', $userName, true);
		
		return $success;
		
	}
	
	public function userId_by_userName($userName) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','user_management');
			throw new Exception("Invalid username", 2604);
		}
		
		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$result = $db->table('users')
			->keys("userId")
			->where("userName","=",$userName)
			->get();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return isset($result["result"][0]["userId"]) ? $result["result"][0]["userId"] : false;
		
	}
	
	public final function get_private_identifier($userName, $userPass) {
		
		if (empty($userName) OR empty($userPass)) {
			comodojo_debug('Invalid user parameters','ERROR','user_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		comodojo_load_resource('database');
		try {
			$db = new database();
			$result = $db->table('users')
			->keys(Array("private_identifier"))
			->where("userName","=",$userName)
			->and_where("userPass","=",md5($userPass))
			->get();
			if ($result['resultLength'] == 1) $id = $result['result'][0]['private_identifier'];
			else {
				//$id = null;
				comodojo_debug('Unknown user','ERROR','user_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			throw $e;
		}
		return $id;
	}
	
	public final function get_public_identifier($userName) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid user parameters','ERROR','user_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$result = $db->table('users')
			->keys(Array("public_identifier"))
			->where("userName","=",$userName)
			->get();
			if ($result['resultLength'] == 1) $id = $result['result'][0]['public_identifier'];
			else {
				//$id = null;
				comodojo_debug('Unknown user','ERROR','user_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			throw $e;
		}
		return $id;
	}
/********************* PUBLIC METHODS ********************/

/********************* PRIVATE METHODS *******************/
	/**
	 * Find an user registered (or promised) in LOCAL database.
	 * 
	 * User can be from ldap or external rpc, but should be also in db (logged in once).
	 * 
	 * @param	string	$userName			The name of the user to look for
	 * @param	string	$includeDisabled	[optional] If true, will include disabled users
	 * @param	string	$includePromised	[optional] If true, will include promised users
	 * 
	 * @return	string						One of {LOCAL, RPC, LDAP}
	 */
	private function findRegisteredUser($userName, $includeDisabled=true, $includePromised=true) {
		
		comodojo_load_resource('database');
		$found = NULL;
		try {
			$db = new database();
			
			$result = $db->table('users')->keys(Array("ldap","rpc"))->where("userName","=",$userName);
			if (!$includeDisabled) {
				$result = $result->and_where("enabled","=",1);
			}
			$result = $result->get();

			if ($result['resultLength'] == 1) $found = $result['result'][0]['ldap'] ? 'LDAP' : ($result['result'][0]['rpc'] ? 'RPC' : 'LOCAL');
			
			if ($includePromised AND is_null($found)) {
				
				$db->clean();
				$result = $db->table('users_registration')
				->keys("id")
				->where("userName","=",$userName)
				->and_where("expired","!=",1)
				->get();

				if ($result['resultLength'] == 1) $found = 'LOCAL';

			} 
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $found;
		
	}
	 
	private function change_user_password_local($userName, $oldPassword, $newPassword) {
		
		comodojo_load_resource('database');
		
		try {
			$db = new database();
			
			// Check for a valid old password
			$result = $db->table('users')
			->keys("userId")
			->where("userName","=",$userName)
			->and_where("enabled","=",1)
			->and_where("userPass","=",md5($oldPassword))
			->get();
			
			if ($result['resultLength'] != 1) {
				comodojo_debug('Wrong password provided','ERROR','user_management');
				throw new Exception("Wrong password provided", 2606);
			};
			
			$db->clean();
			
			$result = $db->table('users')
			->keys("userPass")
			->values(md5($newPassword))
			->where("userName","=",$userName)
			->and_where("enabled","=",1)
			->and_where("userPass","=",md5($oldPassword))
			->update();
			
			//comodojo_debug($result);
			
			if ($result['affectedRows'] != 1) {
				comodojo_debug('Same password provided or error changing password','ERROR','user_management');
				throw new Exception("Same password provided or error changing password", 2607);
			};
		}
		catch (Exception $e){
			throw $e;
		}
		
		return true;
	}
	
	private function change_user_password_external_rpc() {
		
	}
	
	private function reset_user_password_local($userName) {
		
		comodojo_load_resource('database');
		
		$new_password = random(8);
		
		try {

			$db = new database();
			
			$result = $db->table('users')
			->keys("userPass")
			->values(d5($new_password))
			->where("userName","=",$userName)
			->and_where("enabled","=",1)
			->update();
			
			if ($result['affectedRows'] != 1) {
				comodojo_debug('Error resetting password','ERROR','user_management');
				throw new Exception("Error resetting password", 2608);
			};

		}
		catch (Exception $e){
			throw $e;
		}
		
		return $new_password;
	}
	
	private function reset_user_password_external_rpc() {
		
	}
	
	private function enable_user_local($userName,$enable) {
		
		comodojo_load_resource('database');
		
		try {
			$db = new database();

			$result = $db->table('users')
			->keys("enabled")
			->values($enable ? 1 : 0)
			->where("userName","=",$userName)
			->update();
			
			if ($result['affectedRows'] != 1) {
				comodojo_debug('Error enabling/disabling user','ERROR','user_management');
				throw new Exception("Error enabling/disabling user", 2610);
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return true;
		
	}
	
	private function delete_user_local($userName) {
			
		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$result = $db->table('users')->where("userName","=",$userName)->delete();
			
			if ($result['affectedRows'] != 1) {
				comodojo_debug('Error deleting user','ERROR','user_management');
				throw new Exception("Error deleting user", 2611);
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return true;
		
	}
//********************* PRIVATE METHODS *******************/

}

function loadHelper_users_management() { return false; }

?>