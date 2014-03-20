<?php

/** 
 * Provide users registration functions;
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class registration {
	
/*********************** PUBLIC VARS *********************/
	
/*********************** PUBLIC VARS *********************/

/********************** PRIVATE VARS *********************/
	/**
	 * Check if any expired request at each iteration
	 * 
	 * @var	bool
	 */
	 private $check_expired_requests_auto = true;
	 
	/**
	 * Restrict requests management to administrator.
	 * 
	 * If disabled, it will not check user role (=1).
	 * 
	 * @default false;
	 * @var bool
	 */
	 private $restrict_management_to_administrators = false;

	 /**
	  * If true, localized email template will be used.
	  * If false, default en template will be used.
	  *
	  * Email templates should be defined as:
	  * [comodojo_root_folder]/comodojo/tenplates/mail_registration_[action]_[locale].html
	  *
	  * Possible actions are:
	  * - "confirm": sent to the user to confirm email address
	  * - "notify": sent to the user to notify that account is active.
	  *
	  */
	 private $use_localized_email_templates = true;

	 /**
	  * Reserved usernames (same as users_management)
	  */
	 private $reserved_usernames = Array('admin','administrator','root','toor','comodojo','guest');
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Check if user is registered or promised (i.e. if userName is in use)
	 * 
	 * @param	string	$userName			The name of the user to look for
	 * 
	 * @return	bool						true/false
	 */
	public function check_userName($userName) {
		
		if (in_array(strtolower($userName), $this->reserved_usernames)) {
			comodojo_debug('Invalid user parameters: reserved username '.$userName,'ERROR','registration');
			return true;
		}

		comodojo_load_resource('database');
		$found = false;
		try {
			$db = new database();
			
			$result = $db->table('users')->keys("userId")->where("userName","=",$userName)->get();

			if ($result['resultLength'] == 1) { $found = true; }
			else {
				$db->clean();
				
				$result = $db->table('users_registration')->keys("id")->where("userName","=",$userName)->and_where("expired","!=",1)->get();
				
				if ($result['resultLength'] == 1) {$found = true;}
			}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $found;
		
	}

	/**
	 * Start a new registration process.
	 * 
	 * It will take in input user information and output the registration token
	 * 
	 * @param	String		$userName		The requested userName to register
	 * @param	String		$userPass		The user password
	 * @param	String		$email			The user email
	 * @param	String		$completeName	[optional] user complete name
	 * @param	String		$birthday		[optional] user birthday
	 * @param	String		$gender			[optional] user gender
	 * 
	 * @return	Array						Registration token + Registration id
	 */
	public function new_request($userName, $userPass, $email, $completeName=null, $birthday=null, $gender=null) {
		
		//check if registration is permitted
		if (COMODOJO_REGISTRATION_MODE != 1) throw new Exception("Registrations are closed", 2801);
		
		comodojo_load_resource('database');
		comodojo_load_resource('events');
		
		//first check if userName is available, else return error
		if ($this->check_userName($userName)) throw new Exception("Cannot register: userName is in use", 2802);
		
		//then generate a unique registration code and timestamp reference
		$registration_code = random(256);
		$timestamp = strtotime('now');
		
		//store userName and registration code couple in database
		//and
		//process notification if registration is auto-authorized
		try {
			$db = new database();
			$ev = new events();
			
			$result = $db->return_id()->table('users_registration')
			->keys(Array(
				"timestamp",
				"userName",
				"userPass",
				"email",
				"completeName",
				"birthday",
				"gender",
				"code",
				"authorized"
			))
			->values(Array(
				$timestamp,
				$userName,
				md5($userPass),
				$email,
				$completeName,
				$birthday,
				$gender,
				$registration_code,
				!COMODOJO_REGISTRATION_AUTHORIZATION ? 1 : 0
			))->store();
			
			if (COMODOJO_REGISTRATION_AUTHORIZATION == 0) { $this->send_registration_email($userName, empty($completeName) ? $userName : $completeName, $email, $result["transactionId"], $registration_code);}
			
			$ev->record('user_registered', $userName, true);
			
		}
		catch (Exception $e) {
			$ev->record('user_registered', $userName, false);
			throw $e;
		}
		
		//return registration code
		return Array("id"=>$result["transactionId"],"code"=>$registration_code);

	}
	
	/**
	 * Get registration requests from system. 
	 * 
	 * @param	String		$include_expired		[optional] Include expired requests
	 * @param	String		$include_confirmed		[optional] Include completed requests
	 * 
	 * @return	Array								Registrations
	 */
	public function get_requests($include_expired=false, $include_confirmed=false) {
		
		comodojo_load_resource('database');
		
		if ($this->check_expired_requests_auto) $this->check_expired_requests();
		
		try {

			$db = new database();
			
			$result = $db->table('users_registration')
			->keys(Array(
				"id",
				"timestamp",
				"userName",
				"email",
				"completeName",
				"birthday",
				"gender",
				"authorized",
				"confirmed",
				"expired"
			));
			
			if ($include_expired AND $include_confirmed) {
				//null
			}
			elseif ($include_expired) {
				$result = $result->where("confirmed","=",0);
			}
			elseif ($include_confirmed) {
				$result = $result->where("expired","=",0);
			}
			else {
				$result = $result->where("confirmed","=",0)->and_where("expired","=",0);
			}
			
			$result = $result->get();
			
		}
		catch (Exception $e) {
			throw $e;
		}
		
		//return registration code
		return $result["result"];
		
	}
	
	/**
	 * Authorize request by id 
	 * 
	 * @param	String		$requestId		The request id
	 * 
	 * @return	bool						True in case of error, exception otherwise
	 */
	public function authorize_request($requestId) {
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage requests','ERROR','registration');
			throw new Exception("Only administrators can manage requests", 2804);
		}
		
		comodojo_load_resource('database');
		comodojo_load_resource('events');
		
		$userName			= null;
		$completeName		= null;
		$email				= null;
		$registrationCode	= null;
		
		try {
			$db = new database();
			$ev = new events();
			
			$r = $db->table('users_registration')
			->keys(Array(
				"userName",
				"email",
				"completeName",
				"code"
			))
			->where("id","=",$requestId)
			->and_where("expired","!=",1)
			->and_where("confirmed","!=",1)
			->and_where("authorized","!=",1)
			->get();
			
			if ($r["resultLength"] != 1) {
				$ev->record('user_authorized', $requestId, false);
				throw new Exception("Cannot find request id ".$requestId, 2803);
			}
			else {
				$userName			= $r["result"][0]["userName"];
				$completeName		= $r["result"][0]["completeName"];
				$email				= $r["result"][0]["email"];
				$registrationCode	= $r["result"][0]["code"];
			}
			
			$db->clean();
			
			$result = $db->table('users_registration')->keys("authorized")->values(true)->where("id","=",$requestId)->update();
			
			$this->send_registration_email($userName, is_null($completeName) ? $userName : $completeName, $email, $requestId, $registration_code);
			
			$ev->record('user_authorized', $requestId, true);
		}
		catch (Exception $e) {
			$ev->record('user_authorized', $requestId, false);
			throw $e;
		}
		
		return true;
		
	}
	
	/**
	 * Reject request by id
	 * 
	 * In practical words: let expire request before it's natural lifetime 
	 * 
	 * @param	String		$requestId		The request id
	 * 
	 * @return	bool
	 */
	public function reject_request($requestId) {
		
		if ($this->restrict_management_to_administrators AND COMODOJO_USER_ROLE != 1) {
			comodojo_debug('Only administrators can manage requests','ERROR','registration');
			throw new Exception("Only administrators can manage requests", 2804);
		}
		
		comodojo_load_resource('database');
		comodojo_load_resource('events');
		
		try {
			$db = new database();
			$ev = new events();
			
			$r = $db->table('users_registration')->keys("expired")->values(true)->where("id","=",$requestId)->update();
			
			if ($r["affectedRows"] != 1) {
				$ev->record('user_rejected', $requestId, false);
				throw new Exception("Cannot find request id ".$requestId, 2803);
			}
			
			$ev->record('user_rejected', $requestId, true);
		}
		catch (Exception $e) {
			$ev->record('user_rejected', $requestId, false);
			throw $e;
		}
		
		return true;
		
	}
	
	/**
	 * Confirm request (and register user) by id and registration code
	 * 
	 * @param	String		$requestId				The request id
	 * @param	String		$registrationCode		The registration code
	 * 
	 * @return	bool								True in case of success, exception otherwise
	 */
	public function confirm_request($requestId,$registrationCode) {
		
		comodojo_load_resource('database');
		comodojo_load_resource('events');
		comodojo_load_resource('users_management');
		
		if ($this->check_expired_requests_auto) $this->check_expired_requests();
		
		$userName			= null;
		$userPass			= null;
		$email				= null;
		$completeName		= null;
		$birthday			= null;
		$gender				= null;
		
		try {
			$db = new database();
			$ev = new events();
			
			$r = $db->table('users_registration')
			->keys(Array(
				"userName",
				"userPass",
				"email",
				"completeName",
				"birthday",
				"gender"
			))
			->where("id","=",$requestId)
			->and_where("code","=",$registrationCode)
			->and_where("expired","!=",1)
			->and_where("confirmed","!=",1)
			->and_where("authorized","=",1)
			->get();

			if ($r["resultLength"] != 1) {
				$ev->record('user_confirmed', $requestId, false);
				throw new Exception("Cannot find request id ".$requestId, 2803);
			}
			else {
				$userName			= $r["result"][0]["userName"];
				$completeName		= $r["result"][0]["completeName"];
				$email				= $r["result"][0]["email"];
				$userPass			= $r["result"][0]["userPass"];
				$birthday			= $r["result"][0]["birthday"];
				$gender				= $r["result"][0]["gender"];
			}
			
			$db->clean();
			
			$result = $db->table('users_registration')
			->keys("confirmed")
			->values(true)
			->where("id","=",$requestId)
			->and_where("code","=",$registrationCode)
			->update();
			
			$um = new users_management();
			$um->do_not_encrypt_userPass = true;
			$um->add_user_from_registration = true;
			$um->add_user($userName,$userPass,$email,Array(
				"completeName"	=>	$completeName,
				"birthday"		=>	$birthday,
				"gender"		=>	$gender,
				"enabled"		=>	true
			));
			
			$this->send_welcome_email($userName, is_null($completeName) ? $userName : $completeName, $email, $requestId, $registrationCode);
			
		}
		catch (Exception $e) {
			$ev->record('user_authorized', $requestId, false);
			throw $e;
		}
		
		return Array('userName'=>$userName,'completeName'=>$completeName);
	}
	
	/**
	 * Check for expired registration requests and, in case, let them expire 
	 * 
	 */
	public function check_expired_requests() {
		
		comodojo_load_resource('database');
		
		$timestamp = strtotime('now');
		
		comodojo_debug('Starting expired registration requests cycle','INFO','registration');
		
		try {
			$db = new database();
			
			$r = $db->table('users_registration')
			->keys("expired")
			->values(true)
			->where("timestamp","<=",$timestamp-COMODOJO_REGISTRATION_TTL)
			->and_where("confirmed","=",0)
			->and_where("expired","=",0)
			->update();
			
		}
		catch (Exception $e) {
			comodojo_debug('Cannot check for expired requests: ('.$e->getCode().') - '.$e->getMessage(),'ERROR','registration');
			//throw $e;
		}
		
		comodojo_debug($r["affectedRows"].' registration requests where marked as expired','INFO','registration');
		
	}

	/**
	 * Send another email notification to user
	 * 
	 * It works only if request is NOT expired, NOT confirmed, autohrized 
	 * 
	 * @param	String		$requestId		The request id
	 * 
	 * @return	bool
	 */
	public function send_new_notification($email) {

		comodojo_load_resource('database');
		
		$id					= null;
		$userName			= null;
		$completeName		= null;
		$code				= null;
		
		try {
			$db = new database();
			
			$r = $db->table('users_registration')
			->keys(Array("id","userName","completeName","code"))
			->where("email","=",$email)
			->and_where("expired","!=",1)
			->and_where("confirmed","!=",1)
			->and_where("authorized","=",1)
			->get();
			
			if ($r["resultLength"] != 1) {
				throw new Exception("Cannot find request id ".$requestId, 2803);
			}
			else {
				$id					= $r["result"][0]["id"];
				$userName			= $r["result"][0]["userName"];
				$completeName		= $r["result"][0]["completeName"];
				$code				= $r["result"][0]["code"];
			}
			
			$this->send_registration_email($userName, $completeName, $email, $id, $code);
			
		}
		catch (Exception $e) {
			//$ev->record('user_authorized', $requestId, false);
			throw $e;
		}
	}
	
/********************* PUBLIC METHODS ********************/
	
/********************* PRIVATE METHODS *******************/
	private function send_registration_email($userName, $completeName, $email, $request_id, $registration_code) {

		comodojo_load_resource("mail");

		$localized_email = "mail_registration_confirm_".COMODOJO_CURRENT_LOCALE.".html";

		if ($this->use_localized_email_templates and realFileExists(COMODOJO_SITE_PATH."comodojo/templates/".$localized_email)) {
			$mail_template = $localized_email;
		}
		else {
			$mail_template = "mail_registration_confirm_en.html";
		}
		
		try {
			$mail = new mail();
			$mail->template($mail_template)
				 ->to($email)
				 ->subject("Comodojo registration")
				 ->add_tag("*_COMPLETENAME_*",$completeName)
				 ->add_tag("*_REGID_*",$request_id)
				 ->add_tag("*_REGCODE_*",$registration_code)
				 ->embed(COMODOJO_SITE_PATH."comodojo/images/logo.png","COMODOJO_LOGO","logo")
				 ->send();
		}
		catch (Exception $e) {
			throw $e;			
		}

	}
	
	private function send_welcome_email($userName, $completeName, $email) {

		comodojo_load_resource("mail");

		$localized_email = "mail_registration_notify_".COMODOJO_CURRENT_LOCALE.".html";

		if ($this->use_localized_email_templates and realFileExists(COMODOJO_SITE_PATH."comodojo/templates/".$localized_email)) {
			$mail_template = $localized_email;
		}
		else {
			$mail_template = "mail_registration_notify_en.html";
		}

		try {
			$mail = new mail();
			$mail->template($mail_template)
				 ->to($email)
				 ->subject("Welcome ".$completeName."!")
				 ->add_tag("*_COMPLETENAME_*",$completeName)
				 ->add_tag("*_USERNAME_*",$userName)
				 ->embed(COMODOJO_SITE_PATH."comodojo/images/logo.png","COMODOJO_LOGO","logo")
				 ->send();
		}
		catch (Exception $e) {
			throw $e;			
		}

	}
/********************* PRIVATE METHODS *******************/

}

function loadHelper_registration() {
	return false;
}

?>