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

	  /**
	  * Reserved usernames (same as users_management)
	  */
	 private $reserved_usernames = Array('admin','administrator','root','toor','comodojo','guest','shared');

	  /**
	  * If true, localized email template will be used.
	  * If false, default en template will be used.
	  *
	  * Email templates should be defined as:
	  * [comodojo_root_folder]/comodojo/tenplates/mail_users_management_[action]_[locale].html
	  */
	 private $use_localized_email_templates = true;

	 private $reset_by_pwdrecover = false;

/********************** PRIVATE VARS *********************/

/********************** PUBLIC VARS *********************/
	/**
	 * If true, user_add will not encrypt userPass value on user creation.
	 *
	 * @default false;
	 */
	//public $do_not_encrypt_userPass = false;

	/**
	* If true, user will be added skipping promised username control.
	*
	* @default false;
	*/
	public $add_user_from_registration = false;

	/**
	* If true, user will be added skipping admin check.
	*
	* @default false;
	*/
	public $add_user_from_external = false;
/********************** PUBLIC VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Change user password.
	 * PLEASE NOTE: users_management::change_user_password() will only works with local users or remote user via RPC
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
			comodojo_debug('Invalid username, wrong old password or empty new password provided','ERROR','users_management');
			throw new Exception("Invalid username, wrong old password or empty new password provided", 2601);
		}
		
		if (is_null($userName) OR $userName != COMODOJO_USER_NAME) {
			comodojo_debug('Password can be changed only by user','ERROR','users_management');
			throw new Exception("Password can be changed only by user", 2602);
		}
		
		comodojo_load_resource('events');
		$events = new events();
		
		try{
			$presence = $this->findRegisteredUser($userName, false, false);
			if ($presence == 'local') {
				comodojo_debug('Changing local password from user: '.$userName,'INFO','users_management');
				$success = $this->change_user_password_local($userName, $oldPassword, $newPassword);
			}
			//else if ($presence == 'RPC') {
			//	comodojo_debug('Changing remote (RPC) password from user: '.$userName,'INFO','users_management');
			//	$success = $this->change_user_password_external_rpc($userName, $oldPassword, $newPassword);
			//}
			//else if ($presence == 'LDAP') {
			//	comodojo_debug('Unsupported action','ERROR','users_management');
			//	throw new Exception("Unsupported action", 2614);
			//}
			else {
				comodojo_debug('Unsupported action','ERROR','users_management');
				throw new Exception("Unsupported action", 2603);
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
	 * PLEASE NOTE: users_management::changeUserPassword() will only works with local users or remote user via RPC
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
			comodojo_debug('Invalid username','ERROR','users_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1 AND !$this->reset_by_pwdrecover) {
			comodojo_debug('Only administrators can manage users','ERROR','users_management');
			throw new Exception("Only administrators can manage users", 2605);
		}
		
		comodojo_load_resource('events');
		$events = new events();
		
		try{
			$presence = $this->findRegisteredUser($userName, false, false);
			if ($presence == 'local') {
				comodojo_debug('Changing local password from user: '.$userName,'INFO','users_management');
				$success = $this->reset_user_password_local($userName);
			}
			//else if ($presence == 'RPC') {
			//	comodojo_debug('Changing remote (RPC) password from user: '.$userName,'INFO','users_management');
			//	$success = $this->reset_user_password_external_rpc($userName);
			//}
			//else if ($presence == 'LDAP') {
			//	comodojo_debug('Unsupported action','ERROR','users_management');
			//	throw new Exception("Unsupported action", 2614);
			//}
			else {
				comodojo_debug('Unsupported action','ERROR','users_management');
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
	public function get_users($userImageDimensions=64, $enabledOnly=false, $useCache=true) {
		
		comodojo_load_resource('database');
		comodojo_load_resource('cache');
		comodojo_load_resource('user_avatar');
		
		$request = 'COMODOJO_USERS_MANAGEMENT_GET_USERS_'.$userImageDimensions;
		
		try {

			if ($useCache) {
				$c = new cache();
				$cache = $c->get_cache($request, 'JSON', false);
			}
			else {
				$cache = false;
			}

			if ($cache !== false) {
				$to_return = $cache[2]['cache_content'];
			}
			else {
				
				$to_return = array();
				
				$db = new database();
				$results = $db->table('users')->keys(Array("userName","completeName","email","gravatar","userRole","enabled"));

				if ($enabledOnly) {
					$results->where("enabled","=",1);
				}
				
				$results = $results->get();

				foreach ($results['result'] as $user) {
					
					$user_profile = Array(
						"userName"		=> $user['userName'],
						"completeName"	=> $user['completeName'],
						"userRole"		=> $user['userRole']
					);

					if (is_int($userImageDimensions)) $user_profile["userImage"] = get_user_avatar($user['userName'],$user['email'],$user['gravatar'],$userImageDimensions);

					if (!$enabledOnly) $user_profile["enabled"] = $user['enabled'];

					array_push($to_return,$user_profile);

				}

				if ($useCache) $c->set_cache(Array('cache_content'=>$to_return), $request, 'JSON', false);

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
			comodojo_debug('Invalid username','ERROR','users_management');
			throw new Exception("Invalid username", 2604);
		}

		comodojo_load_resource('database');
		comodojo_load_resource('cache');
		comodojo_load_resource('user_avatar');
		
		$request = 'COMODOJO_USERS_MANAGEMENT_GET_USER_'.$userName."_".$userImageDimensions;
		
		try {
			$c = new cache();
			$cache = $c->get_cache($request, 'JSON', false);
			if ($cache !== false) {
				$to_return = $cache[2]['cache_content'];
			}
			else {
				$db = new database();
				$result = $db->table('users')
				->keys(Array("userName","completeName","email","gravatar"))
				->where("userName","=",$userName)
				->and_where("enabled","=",1)
				->get();

				if ($result['resultLength'] == 1) {
					$to_return = !$userImageDimensions ? Array("userName"=>$result['result'][0]['userName'],"completeName"=>$result['result'][0]['completeName']) : Array("userName"=>$result['result'][0]['userName'],"completeName"=>$result['result'][0]['completeName'],"userImage"=>get_user_avatar($result['result'][0]['userName'],$result['result'][0]['email'],$result['result'][0]['gravatar'],$userImageDimensions));
					$c->set_cache(Array('cache_content'=>$to_return), $request, 'JSON', false);
				} 
				else {
					comodojo_debug('Unknown user','ERROR','users_management');
					throw new Exception("Unknown user", 2603);
				}
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $to_return;
	}

	/**
	 * Get user details (extensive version)
	 * 
	 * @param	string 	$userName	
	 * @param	integer	$userImageDimensions	
	 * 
	 * @return	array
	 */
	public function get_user_extensive($userName, $userImageDimensions=64) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','users_management');
			throw new Exception("Invalid username", 2604);
		}

		comodojo_load_resource('database');
		comodojo_load_resource('user_avatar');
	
		try {
			$db = new database();
			$result = $db->table('users')
			->keys(Array("userRole","enabled","authentication","completeName","gravatar","email","birthday","gender","url"))
			->where("userName","=",$userName)
			->get();

			if ($result['resultLength'] == 1) {
				$to_return = Array(
					"userName"	=>	$userName,
					"completeName"=>$result['result'][0]['completeName'],
					"userRole"	=>	$result['result'][0]['userRole'],
					"enabled"	=>	$result['result'][0]['enabled'],
					"authentication"		=>	$result['result'][0]['authentication'],
					"gravatar"	=>	$result['result'][0]['gravatar'],
					"email"		=>	$result['result'][0]['email'],
					"birthday"	=>	$result['result'][0]['birthday'],
					"gender"	=>	$result['result'][0]['gender'],
					"url"		=>	$result['result'][0]['url'],
					"userImage" =>	!$userImageDimensions ? false : get_user_avatar($userName,$result['result'][0]['email'],$result['result'][0]['gravatar'],$userImageDimensions),
				);
			} 
			else {
				comodojo_debug('Unknown user','ERROR','users_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $to_return;
	}
	
	public function add_user($userName, $userPass, $email, $params=Array(), $welcomeEmail=false) {
		
		if (empty($userName) OR empty($userPass) OR empty($email) OR !is_array($params)) {
			comodojo_debug('Invalid user parameters','ERROR','users_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1 AND $this->add_user_from_external == false) {
			comodojo_debug('Only administrators can manage users','ERROR','users_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		if (in_array(strtolower($userName), $this->reserved_usernames)) {
			comodojo_debug('Invalid user parameters: reserved username '+$userName,'ERROR','users_management');
			throw new Exception("User already registered", 2613);
		}

		comodojo_load_resource('database');
		comodojo_load_resource('filesystem');

		if ($this->findRegisteredUser($userName, true, !$this->add_user_from_registration)) {
			comodojo_debug('User already registered','ERROR','users_management');
			throw new Exception("User already registered", 2613);
		}

		$_params = Array(
			'userName'		=>	$userName,
			//'userPass'	=>	!$this->do_not_encrypt_userPass ? md5($userPass) : $userPass,
			'userPass'		=>	md5($userPass),
			'email'			=>	filter_var($email,FILTER_VALIDATE_EMAIL),

			'userRole'		=>	!isset($params['userRole']) ? COMODOJO_REGISTRATION_DEFAULT_ROLE : filter_var($params['userRole'],FILTER_VALIDATE_INT),
			'enabled'		=>	!isset($params['enabled']) ? false : filter_var($params['enabled'],FILTER_VALIDATE_BOOLEAN),
			'authentication'=>	!isset($params['authentication']) ? 'local' : strtolower($params['authentication']),
			'completeName'	=>	!isset($params['completeName']) ? $userName : (is_string($params['completeName']) ? $params['completeName'] : $userName),
			'gravatar'		=>	!isset($params['gravatar']) ? false : filter_var($params['gravatar'],FILTER_VALIDATE_BOOLEAN),
			'birthday'		=>	!isset($params['birthday']) ? null : (is_string($params['birthday']) ? $params['birthday'] : null),
			'gender'		=>	!isset($params['gender']) ? null : (is_string($params['gender']) ? $params['gender'] : null),
			'url'			=>	!isset($params['url']) ? null : (filter_var($params['url'], FILTER_VALIDATE_URL) === FALSE ? null : $params['url']),

			'private_identifier'	=>	random(),
			'public_identifier'		=>	random()	
		);

		try {
			$db = new database();
			if (!is_null($_params["birthday"])) $_params["birthday"] = $db->serialize($_params["birthday"],'DATE');
			$result = $db->return_id()
			->table('users')
			->keys(Array("userName","userPass","email","userRole","enabled","authentication","completeName","gravatar","birthday","gender","url","private_identifier","public_identifier"))
			->values($_params)
			->store();
			$fs = new filesystem();
			$fs->createHome($userName);
			if ($welcomeEmail == true) {
				$this->send_welcome_email($_params['userName'],$_params['completeName'],$_params['email']);
			}
		}
		catch (Exception $e){
			throw $e;
		}

		return $result["transactionId"];

	}
	
	public function edit_user($userName,$params=Array()) {

		if (empty($userName) OR !is_array($params)) {
			comodojo_debug('Invalid user parameters','ERROR','users_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		if ($userName != COMODOJO_USER_NAME AND $this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','users_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		comodojo_load_resource('database');

		if (is_null($this->findRegisteredUser($userName, true, false))) {
			comodojo_debug('Unknown user','ERROR','users_management');
			throw new Exception("Unknown user", 2603);
		}

		$_keys = Array();
		$_values = Array();

		try {

			$db = new database();

			foreach ($params as $key => $value) {
				if (in_array($key, Array("email","userRole","enabled","authentication","completeName","gravatar","birthday","gender","url"))) {
					array_push($_keys, $key);
					switch ($key) {
						case 'email':
							$filtered_value = filter_var($value,FILTER_VALIDATE_EMAIL);
							break;
						case 'userRole':
							$filtered_value = filter_var($value,FILTER_VALIDATE_INT);
							break;
						case 'enabled':
						case 'gravatar':
							$filtered_value = filter_var($value,FILTER_VALIDATE_BOOLEAN);
							break;
						case 'authentication':
							$filtered_value = strtolower($value);
							break;
						case 'birthday':
							$filtered_value = $db->serialize($value,'DATE');
							break;
						default:
							$filtered_value = $value;
							break;
					}
					array_push($_values, $filtered_value);
				}
			}

			if (empty($_keys)) {
				comodojo_debug('Invalid user parameters','ERROR','users_management');
				throw new Exception("Invalid user parameters", 2612);
			}

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
	
	public function set_user_image($userName, $image, $userImageDimensions=64) {

		if (empty($userName) OR empty($image)) {
			comodojo_debug('Invalid user parameters','ERROR','users_management');
			throw new Exception("Invalid user parameters", 2612);
		}

		$imagePath = (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . COMODOJO_HOME_FOLDER . COMODOJO_USERS_FOLDER;
		$thumbPath = (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . COMODOJO_HOME_FOLDER . COMODOJO_THUMBNAILS_FOLDER;
		$imageFile = $userName . '/._avatar.png';

		comodojo_load_resource("filesystem");
		comodojo_load_resource('image_tools');

		try {
			$fs = new filesystem();
			$it = new image_tools();
			$fs->copyFile($image, $imageFile, true);
			$image = $thumbPath . $it->thumbnail($imagePath.$imageFile,$userImageDimensions);
		}
		catch (Exception $e){
			throw $e;
		}

		return $image;
	}

	public function delete_user($userName) {
		
		if (empty($userName)) {
			comodojo_debug('Invalid username','ERROR','users_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($userName != COMODOJO_USER_NAME AND $this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','users_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		comodojo_load_resource('filesystem');
		comodojo_load_resource('events');
		$events = new events();
		$fs = new filesystem();
		
		try {
			$presence = $this->findRegisteredUser($userName, true, false);

			//if ($presence != false) {
			if (!is_null($presence)) {
				comodojo_debug('Deleting user: '.$userName." defined as ".$presence,'INFO','users_management');
				$success = $this->delete_user_local($userName, true);
				$fs->removeHome($userName);
			}
			else {
				comodojo_debug('Unknown user','ERROR','users_management');
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
			comodojo_debug('Invalid username','ERROR','users_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','users_management');
			throw new Exception("Only administrators can manage users", 2605);
		}

		comodojo_load_resource('events');
		$events = new events();
		
		try {
			$presence = $this->findRegisteredUser($userName, true, false);
			//if ($presence != false) {
			if (!is_null($presence)) {
				comodojo_debug('Enabling  user: '.$userName." defined as ".$presence,'INFO','users_management');
				$success = $this->enable_user_local($userName, true);
			}
			else {
				comodojo_debug('Unknown user','ERROR','users_management');
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
			comodojo_debug('Invalid username','ERROR','users_management');
			throw new Exception("Invalid username", 2604);
		}
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage users','ERROR','users_management');
			throw new Exception("Only administrators can manage users", 2605);
		}
		
		comodojo_load_resource('events');
		$events = new events();
		
		try {
			$presence = $this->findRegisteredUser($userName, true, false);
			//if ($presence != false) {
			if (!is_null($presence)) {
				comodojo_debug('Disabling user: '.$userName." defined as ".$presence,'INFO','users_management');
				$success = $this->enable_user_local($userName, false);
			}
			else {
				comodojo_debug('Unknown user','ERROR','users_management');
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
			comodojo_debug('Invalid username','ERROR','users_management');
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
			comodojo_debug('Invalid user parameters','ERROR','users_management');
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
				comodojo_debug('Unknown user','ERROR','users_management');
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
			comodojo_debug('Invalid user parameters','ERROR','users_management');
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
				comodojo_debug('Unknown user','ERROR','users_management');
				throw new Exception("Unknown user", 2603);
			}
		}
		catch (Exception $e){
			throw $e;
		}
		return $id;
	}

	public final function user_recovery_request($email) {

		comodojo_load_resource('database');
		comodojo_load_resource('events');
		
		$this->user_recovery_expire_batch();

		$code = random(256);
		$timestamp = strtotime('now');
		
		try {

			$db = new database();
			$ev = new events();
			
			$isInDatabase = $db->table('users')->keys(Array('userName','completeName'))->where('email','=',$email)->get();

			if ($isInDatabase['resultLength'] != 1) {
				comodojo_debug("Email address is not in users' database","ERROR","users_management");
				throw new Exception("Email address is not in users' database", 2615);
			}

			$db->table('users_recovery')
			->keys(Array(
				"timestamp",
				"userName",
				"email",
				"code",
				"confirmed",
				"expired"
			))
			->values(Array(
				$timestamp,
				$isInDatabase['result'][0]['userName'],
				$email,
				$code,
				0,
				0
			))->store();
			
			$this->send_recovery_email($isInDatabase['result'][0]['userName'], is_null($isInDatabase['result'][0]['completeName']) ? $isInDatabase['result'][0]['userName'] : $isInDatabase['result'][0]['completeName'], $email, $code, $timestamp);
			
			$ev->record('user_recovery_request', $email, true);
			
		}
		catch (Exception $e) {
			$ev->record('user_recovery_request', $email, false);
			throw $e;
		}

	}

	public final function user_recovery_confirm($email,$code) {
		comodojo_load_resource('database');
		comodojo_load_resource('events');
		
		$this->user_recovery_expire_batch();

		try {

			$db = new database();
			$ev = new events();
			
			$isInDatabase = $db->table('users_recovery')
				->keys('userName')
				->where('code','=',$code)
				->and_where('email','=',$email)
				->and_where('expired','=',0)
				->and_where('confirmed','=',0)
				->get();

			if ($isInDatabase['resultLength'] != 1) {
				comodojo_debug("No request found for this email address or request expired","ERROR","users_management");
				throw new Exception("No request found for this email address or request expired", 2616);
			}

			try {
				$this->reset_by_pwdrecover = true;
				$new_pass = $this->reset_user_password($isInDatabase['result'][0]['userName']);
			}
			catch (Exception $e) {
				throw $e;
			}
			

			$db->table('users_recovery')
			->keys("confirmed")
			->values(1)
			->where('code','=',$code)
			->and_where('email','=',$email)
			->store();
			
			$this->send_reset_email($isInDatabase['result'][0]['userName'], empty($isInDatabase['result'][0]['completeName']) ? $isInDatabase['result'][0]['userName'] : $isInDatabase['result'][0]['completeName'], $email, $new_pass);
			
			$ev->record('user_recovery_confirm', $email, true);
			
		}
		catch (Exception $e) {
			$ev->record('user_recovery_confirm', $email, false);
			throw $e;
		}

		return Array("userName" => $isInDatabase['result'][0]['userName'], "email" => $email);
	}

	public final function user_recovery_expire_batch() {
		//Check if request is expired (will use registration TTL as max request lifetime)
		//->where("timestamp","<=",$timestamp-COMODOJO_REGISTRATION_TTL)
		comodojo_load_resource('database');
		comodojo_load_resource('events');
		
		try {

			$db = new database();
			$ev = new events();
			
			$db->table('users_recovery')->keys('expired')->values(1)->where('timestamp','<=',(strtotime('now')-COMODOJO_REGISTRATION_TTL))->update();
			
			$ev->record('user_recovery_expire_batch', '', true);
			
		}
		catch (Exception $e) {
			$ev->record('user_recovery_expire_batch', '', false);
			comodojo_debug($e->getMessage(),"ERROR","users_management");
		}
	}

	public final function search($pattern, $realm) {

		if (empty($pattern) OR empty($realm)) {

		}

		if ($realm == 'local') {

			try {
				$results = $this->search_local($pattern);
			} catch (Exception $e) {
				throw $e;
			}

			return $results;

		}
		else {

			$server = $this->get_auth_server($realm);
			if (!$server) {

			}

			try {
				switch ($server["type"]) {
					case 'ldap':
						$results = $this->search_ldap($pattern, $server);
						break;
					
					case 'rpc':
						$results = $this->search_rpc($pattern, $server);
						break;
				}
			} catch (Exception $e) {
				throw $e;
			}
			
			return $results;

		}

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
			
			$result = $db->table('users')->keys("authentication")->where("userName","=",$userName);
			if (!$includeDisabled) {
				$result = $result->and_where("enabled","=",1);
			}
			$result = $result->get();

			if ($result['resultLength'] == 1) {
				$found = $result['result'][0]['authentication'];
			}

			if ($includePromised AND is_null($found)) {
				
				$db->clean();
				$result = $db->table('users_registration')
				->keys("id")
				->where("userName","=",$userName)
				->and_where("expired","!=",1)
				->get();

				if ($result['resultLength'] == 1) $found = 'local';

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
				comodojo_debug('Wrong password provided','ERROR','users_management');
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
				comodojo_debug('Same password provided or error changing password','ERROR','users_management');
				throw new Exception("Same password provided or error changing password", 2607);
			};
		}
		catch (Exception $e){
			throw $e;
		}
		
		return true;
	}
		
	private function reset_user_password_local($userName) {
		
		comodojo_load_resource('database');
		
		$new_password = random(8);
		
		try {

			$db = new database();
			
			$result = $db->table('users')
			->keys("userPass")
			->values(md5($new_password))
			->where("userName","=",$userName)
			->and_where("enabled","=",1)
			->update();
			
			if ($result['affectedRows'] != 1) {
				comodojo_debug('Error resetting password','ERROR','users_management');
				throw new Exception("Error resetting password", 2608);
			};

		}
		catch (Exception $e){
			throw $e;
		}
		
		return $new_password;
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
				comodojo_debug('Error enabling/disabling user','ERROR','users_management');
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
				comodojo_debug('Error deleting user','ERROR','users_management');
				throw new Exception("Error deleting user", 2611);
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return true;
		
	}

	private function send_recovery_email($userName, $completeName, $email, $code, $timestamp) {

		comodojo_load_resource("mail");

		$localized_email = "mail_users_management_recovery_".COMODOJO_CURRENT_LOCALE.".html";

		if ($this->use_localized_email_templates and realFileExists(COMODOJO_SITE_PATH."comodojo/templates/".$localized_email)) {
			$mail_template = $localized_email;
		}
		else {
			$mail_template = "mail_users_management_recovery_en.html";
		}
		
		try {
			$mail = new mail();
			$mail->template($mail_template)
				 ->to($email)
				 ->subject("Password Reset Request")
				 ->add_tag("*_COMPLETENAME_*",$completeName)
				 ->add_tag("*_USERNAME_*",$userName)
				 ->add_tag("*_EMAIL_*",$email)
				 ->add_tag("*_CODE_*",$code)
				 ->add_tag("*_TIME_*",date(DATE_RFC850,$timestamp))
				 ->embed(COMODOJO_SITE_PATH."comodojo/images/logo.png","COMODOJO_LOGO","logo")
				 ->send();
		}
		catch (Exception $e) {
			throw $e;			
		}

	}

	private function send_reset_email($userName, $completeName, $email, $password) {

		comodojo_load_resource("mail");

		$localized_email = "mail_users_management_reset_".COMODOJO_CURRENT_LOCALE.".html";

		if ($this->use_localized_email_templates and realFileExists(COMODOJO_SITE_PATH."comodojo/templates/".$localized_email)) {
			$mail_template = $localized_email;
		}
		else {
			$mail_template = "mail_users_management_reset_en.html";
		}
		
		try {
			$mail = new mail();
			$mail->template($mail_template)
				 ->to($email)
				 ->subject("Password Reset Request")
				 ->add_tag("*_COMPLETENAME_*",$completeName)
				 ->add_tag("*_USERNAME_*",$userName)
				 ->add_tag("*_EMAIL_*",$email)
				 ->add_tag("*_PASSWORD_*",$password)
				 ->embed(COMODOJO_SITE_PATH."comodojo/images/logo.png","COMODOJO_LOGO","logo")
				 ->send();
		}
		catch (Exception $e) {
			throw $e;			
		}

	}

	private function send_welcome_email($userName, $completeName, $email) {

		comodojo_load_resource("mail");

		$localized_email = "mail_users_management_welcome_".COMODOJO_CURRENT_LOCALE.".html";

		if ($this->use_localized_email_templates and realFileExists(COMODOJO_SITE_PATH."comodojo/templates/".$localized_email)) {
			$mail_template = $localized_email;
		}
		else {
			$mail_template = "mail_users_management_welcome_en.html";
		}
		
		try {
			$mail = new mail();
			$mail->template($mail_template)
				 ->to($email)
				 ->subject("Welcome to ".COMODOJO_SITE_TITLE)
				 ->add_tag("*_COMPLETENAME_*",$completeName)
				 ->add_tag("*_USERNAME_*",$userName)
				 ->embed(COMODOJO_SITE_PATH."comodojo/images/logo.png","COMODOJO_LOGO","logo")
				 ->send();
		}
		catch (Exception $e) {
			throw $e;			
		}

	}

	private function search_local($pattern) {

	}

	private function search_ldap($pattern, $server) {

		comodojo_load_resource('ldap');
		
		try {
			$ldap = new ldap($server["server"], $server["port"]);
			$result = $ldap->base($server["base"])
				->searchbase($server["searchbase"])
				->dn($server["dn"])
				->version($server["version"])
				->ssl($server["ssl"])
				->tls($server["tls"])
				->account($server["listuser"], $server["listpass"])
				->search($pattern);
		}
		catch (Exception $e){
			comodojo_debug('There is a problem with ldap: '.$e->getMessage(),'WARNING','authentication');
			throw $e;
		}

		return $result;

	}

	private function search_rpc($pattern, $server) {

	}

	/**
	 * Parse authentication servers
	 */
	private final function get_auth_server($server) {

		$rpcs = json2array(COMODOJO_AUTHENTICATION_RPCS);

		$ldaps = json2array(COMODOJO_AUTHENTICATION_LDAPS);

		foreach ($rpcs as $rpc) {
			if ($rpc["name"] == $server) {
				return Array(
					"server"	=> $rpc["server"],
					"port"		=> $rpc["port"],
					"transport"	=> $rpc["transport"],
					"sharedkey"	=> $rpc["sharedkey"],
					"type"		=> "rpc"
				);
			}
		}

		foreach ($ldaps as $ldap) {
			if ($ldap["name"] == $server) {
				return Array(
					"server"	=> $ldap["server"],
					"port"		=> $ldap["port"],
					"base"		=> $ldap["base"],
					"dn"		=> $ldap["dn"],
					"searchbase"=> $ldap["searchbase"],
					"version"	=> $ldap["version"],
					"ssl"		=> $ldap["ssl"],
					"tls"		=> $ldap["tls"],
					"listuser"	=> $ldap["listuser"],
					"listpass"	=> $ldap["listpass"],
					"type"		=> "ldap"
				);
			}
		}

		return false;

	}

//********************* PRIVATE METHODS *******************/

}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_users_management
 */
function loadHelper_users_management() { return false; }

?>