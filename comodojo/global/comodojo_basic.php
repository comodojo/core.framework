<?php

/**
 * comodojo_basic.php
 * 
 * The base class of every comodojo service (bootstrap, kernel, startup, ...)
 *
 * Extend as your wish :)
 *
 * TODO: This class should have more comments...
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

ob_start();

class comodojo_basic {

	/**
	 * The name of the main script, used in path/url retrieval
	 * @var	string
	 */
	public $script_name = false;

	/**
	 * Header parameters to be passed to set_header
	 * @var	array
	 */
	public $header_params = Array();
	
	/**
	 * Use session transport or not
	 * @var	bool
	 */
	public $use_session_transport = false;
	
	/**
	 * If true, c_basic will return error in case of invalid session
	 * This is used also for kernel to understand session lost
	 * @var	bool
	 */
	public $require_valid_session = false;
	
	/**
	 * If true, c_basic will do authentication on $attributes in order to login/logout user
	 * @var	bool
	 */
	public $do_authehtication = true;

	/**
	 * If auth is not enabled, this will set COMODOJO_* auth constant to NULL
	 * @var	bool
	 */
	public $clean_auth_constants = true;
	
	/**
	 * If true, c_basic will set header params automatically
	 * @var	bool
	 */
	public $auto_set_header = true;
	
	/**
	 * If true, c_basic will check if post size > allowed post size (DISABLE WITH CARE)
	 * @var	bool
	 */
	public $do_safe_post_check = true;

	/**
	 * If true, c_basic will init logic() method with raw post data
	 * @var	string
	 */
	public $raw_attributes = false;
	
	public $locale = '';
	public $unsupported_locale = false;

	/**
	 * Notication that should be raised to user (each script can implement how to notify independently)
	 * @var string
	 */
	public $notification = null;

	/**
	 * Internal pointer to basic values 
	 */
	private $comodojo_basic_values = false;
	private $comodojo_basic_values_from = false;
	
	public function __construct() {
		
		if ($this->use_session_transport) { session_start(); }
		
		$this->get_boot_path_and_url();
		
		if (!is_readable(COMODOJO_BOOT_PATH."comodojo/configuration/static_configuration.php")) {
			@include COMODOJO_BOOT_PATH."comodojo/global/header.php";
			die($this->error(9991,"<script type=\"text/javascript\">setTimeout(\"location.href='installer.php';\",3000);</script><strong>It seems that comodojo is not installed; redirecting to installer in 3 seconds...</strong>"));
		} 
		
		@include COMODOJO_BOOT_PATH."comodojo/configuration/static_configuration.php";
		@include COMODOJO_BOOT_PATH."comodojo/global/common_functions.php";
		@include COMODOJO_BOOT_PATH."comodojo/global/header.php";
		
		if ($this->require_valid_session AND !isset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER])) {
			die($this->error(2107,'Session lost'));
		}
		if (!defined('COMODOJO_UNIQUE_IDENTIFIER')) {
			die($this->error(9992,'Cannot load comodojo configuration file'));
		}
		if (!function_exists('loadHelper_common_functions') OR !function_exists('loadHelper_header') ) {
			die($this->error(9993,'Cannot load required api; see error log for details'));
		}
		
		comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
		comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
		comodojo_debug('  **** Starting new run cycle: '.(!$this->script_name ? 'index.php' : $this->script_name).' ****  ','INFO','comodojo_basic');
		comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
		
		if ($this->use_session_transport) {
			comodojo_debug(' * Session transport is ENABLED','INFO','comodojo_basic');
			if (!isset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['QUERIES'])) $_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['QUERIES'] = 0;
		}
		else {
			comodojo_debug(' * Session transport is DISABLED','INFO','comodojo_basic');
		}
		
		comodojo_debug(' * Startup cache is '.(!COMODOJO_STARTUP_CACHE_ENABLED ? 'DISABLED' : 'ENABLED'),'INFO','comodojo_basic');
		
		if ($this->basic_from_session()) $this->set_basic(false);
		elseif ($this->basic_from_startup_cache()) $this->set_basic(true);
		elseif ($this->basic_from_database()) $this->set_basic(true);
		else die($this->error(9994,'Cannot load startup values, see error log for details'));

		comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');

		if ($this->do_safe_post_check) {
			if (!$this->safe_post_check()) {
				comodojo_debug('POST max size exceeded, execution aborted','WARNING','comodojo_basic');
				comodojo_debug('--------------------------------------------------------------------------','WARNING','comodojo_basic');
				die($this->error(9995,'POST max size exceeded'));
			}
		}
		
		$attributes = $this->get_attributes();

		$this->eval_locale($attributes);
		
		comodojo_debug(' * Boot PATH is '.COMODOJO_BOOT_PATH,'INFO','comodojo_basic');
		comodojo_debug(' * Boot URL is '.COMODOJO_BOOT_URL,'INFO','comodojo_basic');
		comodojo_debug(' * Site PATH is '.COMODOJO_SITE_PATH,'INFO','comodojo_basic');
		comodojo_debug(' * Site URL is '.COMODOJO_SITE_URL,'INFO','comodojo_basic');
		comodojo_debug(' * External URL is '.COMODOJO_SITE_EXTERNAL_URL,'INFO','comodojo_basic');
		comodojo_debug(' * Site LOCALE is '.COMODOJO_SITE_LOCALE,'INFO','comodojo_basic');
		
		if (isset($attributes['application']) AND isset($attributes['method'])) {
			comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
			comodojo_debug(' * Requested application.method is: '.$attributes['application'].'.'.$attributes['method'],'INFO','comodojo_basic');
		}
		
		comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
		
		/*
		 * If user still authenticated (has a public identifier), do not reauth unless each session is
		 * forced to be authenticated (COMODOJO_SESSION_AUTHENTICATED).
		 * If comodojo.login/logout was called, let it pass.
		 */ 
		if($this->do_authehtication AND $this->use_session_transport AND @isset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ID'])
			AND /* isset($attributes['application']) AND */ (@$attributes['application'].'.'.@$attributes['method'] != 'comodojo.login')
			AND /* isset($attributes['method']) AND */ (@$attributes['application'].'.'.@$attributes['method'] != 'comodojo.logout')) {
				
			if(COMODOJO_SESSION_AUTHENTICATED) {
				comodojo_debug(' * Previous authentication found: starting REAUTH','INFO','comodojo_basic');
				comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
				comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ID']);
				$this->auth_login($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_NAME'], $_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_PASS'], true);
			}
			else {
				comodojo_debug(' * Previous authentication found: will NOT REAUTH','INFO','comodojo_basic');
				comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
				comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
				$this->set_auth_session($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_NAME'], $_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_PASS'], Array(
					"userId"		=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ID'],
					"userRole"		=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ROLE'],
					"completeName"	=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_COMPLETE_NAME'],
					"gravatar"		=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_GRAVATAR'],
					"email"			=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_EMAIL'],
					"birthday"		=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_BIRTHDAY'],
					"gender"		=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_GENDER'],
					"url"			=>	$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_URL']
				));
			}

		}
		/*
		 * Login a user if requested...
		 */
		elseif ($this->do_authehtication AND 
			isset($attributes['application']) AND @$attributes['application'] == 'comodojo' AND
			isset($attributes['method']) AND @$attributes['method'] == 'login' AND 
			isset($attributes['userName']) AND isset($attributes['userPass'])
		) {
			comodojo_debug(' * New authentication found: processing LOGIN','INFO','comodojo_basic');
			comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
			comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
			$this->auth_login($attributes['userName'], $attributes['userPass'], false);
		}
		/*
		 * Logout a user if requested
		 */
		elseif ($this->do_authehtication AND
			isset($attributes['application']) AND @$attributes['application'] == 'comodojo' AND
			isset($attributes['method']) AND @$attributes['method'] == 'logout'
		){
			comodojo_debug(' * Processing LOGOUT','INFO','comodojo_basic');
			comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
			comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
			$this->auth_logout(isset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_NAME']) ? $_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_NAME'] : false);
		}
		/*
		 * Do user registration if requested
		 */
		/*elseif ($this->do_authehtication AND
			isset($attributes['application']) AND @$attributes['application'] == 'comodojo' AND
			isset($attributes['method']) AND @$attributes['method'] == 'logout' AND
			isset($attributes['id']) AND isset($attributes['code'])
		) {
			comodojo_load_resource("registration");
			try {
				$re = new registration();
				$this->notification = $re->confirm_request($params['id'],$params['code']);
			} catch (Exception $e) {
				throw $e;
			}
		}
		/*
		 * If no auth or pwd reset or sign up was requested, set unauthenticated session
		 */
		else {

			if ($this->clean_auth_constants) {
				comodojo_debug(' * No authentication found or requested: CLEANING AUTH PARAMETERS','INFO','comodojo_basic');
				$this->set_auth_session();
			}
			else {
				comodojo_debug(' * No authentication found or requested: AUTH PARAMETERS WILL NOT BE CLEANED','INFO','comodojo_basic');
			}
			comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
			comodojo_debug('--------------------------------------------------------------------------','INFO','comodojo_basic');
			
		}

		try {
			$to_return = $this->logic($attributes);
		}
		catch (Exception $e) {
			ob_end_clean();
			die($this->error($e->getCode(),$e->getMessage()));
		}
		
		if ($this->auto_set_header) set_header($this->header_params, strlen($to_return));
		
		ob_end_clean();
		
		die($to_return);
		
	}
	
	public function logic($attributes) {
		return false;
	}

	public final function auth_login($userName, $userPass, $fromSession=false) {
		//if(isset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ID'])) return false;
		comodojo_load_resource('authentication');
		try {
			$au = new authentication();
			$au->loginFromSession = $fromSession;
			$auth = $au->login($userName, $userPass);
		} catch (Exception $e) {
			die($this->error($e->getCode(),$e->getMessage()));
		}
		if (!$auth) {
			$this->set_auth_session();
		}
		else {
			$this->set_auth_session($userName, !$fromSession ? md5($userPass) : $userPass, $auth);
		}
	}
	
	public final function auth_logout($userName = false) {
		comodojo_load_resource('authentication');
		try {
			$au = new authentication();
			$auth = $au->logout($userName);
		} catch (Exception $e) {
			die($this->error($e->getCode(),$e->getMessage()));
		}
		$this->set_auth_session();
	}

	public final function auth_clear() {
		$this->set_auth_session();
	}
	
	private final function set_auth_session($userName=false, $userPass=false, $userInfo=false) {
		if (!$userName OR !$userPass OR !$userInfo) {
			define('COMODOJO_USER_ID',NULL);
			define('COMODOJO_USER_NAME',NULL);
			define('COMODOJO_USER_PASS',NULL);
			define('COMODOJO_USER_ROLE',0);
			define('COMODOJO_USER_COMPLETE_NAME',NULL);
			define('COMODOJO_USER_GRAVATAR',NULL);
			define('COMODOJO_USER_EMAIL',NULL);
			define('COMODOJO_USER_BIRTHDAY',NULL);
			define('COMODOJO_USER_GENDER',NULL);
			define('COMODOJO_USER_URL',NULL);
			if (isset($_SESSION)) {
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ID']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_NAME']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_PASS']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ROLE']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_COMPLETE_NAME']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_GRAVATAR']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_EMAIL']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_BIRTHDAY']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_GENDER']);
				unset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_URL']);
			}
		}
		else {
			define('COMODOJO_USER_ID',$userInfo['userId']);
			define('COMODOJO_USER_NAME',$userName);
			define('COMODOJO_USER_PASS',$userPass);
			define('COMODOJO_USER_ROLE',$userInfo['userRole']);
			define('COMODOJO_USER_COMPLETE_NAME',$userInfo['completeName']);
			define('COMODOJO_USER_GRAVATAR',$userInfo['gravatar']);
			define('COMODOJO_USER_EMAIL',$userInfo['email']);
			define('COMODOJO_USER_BIRTHDAY',$userInfo['birthday']);
			define('COMODOJO_USER_GENDER',$userInfo['gender']);
			define('COMODOJO_USER_URL',$userInfo['url']);
			if (isset($_SESSION) AND $this->use_session_transport) {
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ID'] = $userInfo['userId'];
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_NAME'] = $userName;
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_PASS'] = $userPass;
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_ROLE'] = $userInfo['userRole'];
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_COMPLETE_NAME'] = $userInfo['completeName'];
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_GRAVATAR'] = $userInfo['gravatar'];
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_EMAIL'] = $userInfo['email'];
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_BIRTHDAY'] = $userInfo['birthday'];
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_GENDER'] = $userInfo['gender'];
				$_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['COMODOJO_USER_URL'] = $userInfo['url'];
			}
		}
	}

	public function error($error_code, $error_content) {
		
		include COMODOJO_BOOT_PATH.'comodojo/global/qotd.php';
		
		$index = file_get_contents(COMODOJO_BOOT_PATH . "comodojo/templates/web_error.html");
		
		$index = str_replace("*_ERRORNAME_*",$error_code,$index);
		$index = str_replace("*_ERRORDETAILS_*",$error_content,$index);
		$index = str_replace("*_ERRORQUOTE_*","<em>".get_quote()."</em>",$index);
		
		set_header(Array(
			'statusCode'	=>	200,
			'contentType'	=> 'text/html',
			'charset'		=>	'UTF-8'
		), strlen($index));
		
		return $index;
		
	}
	
	private final function get_boot_path_and_url() {
			
		define('COMODOJO_BOOT_PATH',getcwd()."/");
		
		$http = 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 's' : '') . '://';
		$uri = @$_SERVER['REQUEST_URI'];
		$uri = !$this->script_name ? preg_replace("/\/index.php(.*?)$/i","",$uri) : preg_replace("/\/".$this->script_name."(.*?)$/i","",$uri);
		$currentUrl = $http . @$_SERVER['HTTP_HOST'] . $uri."/";
		
		define('COMODOJO_BOOT_URL',str_replace('%20',' ',$currentUrl));
		
	} 
	
	private final function basic_from_session() {
		if (isset($_SESSION[COMODOJO_SESSION_IDENTIFIER])) {
			$this->comodojo_basic_values = $_SESSION[COMODOJO_SESSION_IDENTIFIER];
			comodojo_debug(' * Basic configuration retrieved from SESSION','INFO','comodojo_basic');
			$this->comodojo_basic_values_from = 'SESSION';
			return true;
		}
		else return false;
	}
	
	private final function basic_from_startup_cache() {
		$cache_file = COMODOJO_BOOT_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.md5(COMODOJO_UNIQUE_IDENTIFIER);
		if (COMODOJO_STARTUP_CACHE_ENABLED AND is_readable($cache_file)) {
			$this->comodojo_basic_values = json2array(file_get_contents($cache_file));
			comodojo_debug(' * Basic configuration retrieved from CACHE','INFO','comodojo_basic');
			$this->comodojo_basic_values_from = 'CACHE';
			return true;
		}
		else return false;
	}
	
	private final function basic_from_database() {
		comodojo_load_resource('database');
		try {
			$db = new database();
			$result = $db->table("options")->keys("*")->where("siteId","=",COMODOJO_UNIQUE_IDENTIFIER)->get();
		} catch (Exception $e) {
			comodojo_debug('Error retrieving base configuration: '.$e->getMessage(),'ERROR','comodojo_basic');
			return false;
		}
		
		//comodojo is installed COMODOJO_UNIQUE_IDENTIFIER isn't consistent
		if (!$result['resultLength']) {
			comodojo_debug('Error retrieving base configuration: invalid COMODOJO_UNIQUE_IDENTIFIER or database corrupted.','ERROR','comodojo_basic');
			return false;
		}
		
		$this->comodojo_basic_values = $result['result'];
		$this->comodojo_basic_values_from = 'DATABASE';
		
		comodojo_debug(' * Basic configuration retrieved from DATABASE','INFO','comodojo_basic');
		return true;
	}
	
	private final function set_basic($setSession) {
		
		switch ($this->comodojo_basic_values_from) {
			case 'SESSION':
			case 'CACHE':
				foreach ($this->comodojo_basic_values as $K=>$V) {
					define(strtoupper($K),$V);
				}
			break;
			
			case 'DATABASE':
				if (!COMODOJO_STARTUP_CACHE_ENABLED) {
					foreach ($this->comodojo_basic_values as $V) {
						define('COMODOJO_'.strtoupper($V['option']),$V['value']);
					}
				}
				else {
					$to_cache = Array();
					foreach ($this->comodojo_basic_values as $V) {
						define('COMODOJO_'.strtoupper($V['option']),$V['value']);
						$to_cache['COMODOJO_'.strtoupper($V['option'])] = $V['value'];
					}
					$this->set_startup_cache($to_cache);
				}
			break;
			
			default:
				die($this->error(9994,'Cannot load startup values, see error log for details'));
			break;
		}
		
		if (/*COMODOJO_SESSION_ENABLED*/false and $setSession) $_SESSION[COMODOJO_SESSION_IDENTIFIER] = $this->comodojo_basic_values;
		
	}
	
	private final function set_startup_cache($content) {
		
		if(!is_array($content) OR !sizeof($content)) return false;
		
		$cache_file = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.md5(COMODOJO_UNIQUE_IDENTIFIER);
		$fh = fopen($cache_file, 'w');
		if (!$fh) return false;
		if (!fwrite($fh, array2json($content))) {
			fclose($fh);
			unlink($cache_file);
			return false;
		}
		
		fclose($fh);
		
		return true;
		
	}

	/**
	 * Takes attributes (or parameters) via [METHOD] and return an
	 * array of them.
	 */
	private final function get_attributes() {
		if (isset($_SERVER['REQUEST_METHOD'])) {
			if ($this->raw_attributes) return file_get_contents('php://input');
			switch($_SERVER['REQUEST_METHOD']) {
				case 'GET':
				case 'HEAD':
					$attributes = $_GET;
				break;
				case 'POST':
					$attributes = $_POST;
				break;
				case 'PUT':
				case 'DELETE':
					parse_str(file_get_contents('php://input'), $attributes);
				break;
			}
			return $attributes;
		}
		else return false;
		
	}
	
	private final function eval_locale($attributes) {
		
		$supportedLocales = explode(',', COMODOJO_SUPPORTED_LOCALES);
		
		if(strtolower(COMODOJO_SITE_LOCALE) == 'auto') {
			
			$cLang = getComodojoCookie('locale');
			
			if (isset($attributes['locale'])) $locale = $attributes['locale'];
			elseif ($cLang == -1 OR $cLang == 'auto') $locale = getLocale();
			else $locale = $cLang;
			
			if (in_array($locale,$supportedLocales)) {
				$this->locale = $locale;
				@setlocale(LC_ALL, $locale);
				$this->unsupported_locale = false;
			}
			else {
				$this->locale = "en";
				@setlocale(LC_ALL, "en");
				$this->unsupported_locale = $locale;
			}
			
		}
		else {
			
			$this->locale = COMODOJO_SITE_LOCALE;
			$this->unsupported_locale = false;
			@setlocale(LC_ALL, COMODOJO_SITE_LOCALE);
			
		}
		
		define('COMODOJO_CURRENT_LOCALE',$this->locale);
		
	}

	public final function safe_post_check() {

		$post_limit = null;

		if (preg_match('/^(\d+)(.)$/', ini_get('post_max_size'), $matches)) {
			if ($matches[2] == 'K') {
				$post_limit = $matches[1] * 1024;
			}
			else if ($matches[2] == 'M') {
				$post_limit = $matches[1] * 1024 * 1024;
			}
			else if ($matches[2] == 'G') {
				$post_limit = $matches[1] * 1024 * 1024 * 1024;
			}
		}

		if (isset($_SERVER["CONTENT_LENGTH"]) AND !is_null($post_limit) AND $_SERVER["CONTENT_LENGTH"] > $post_limit) {
			return false;
		}
		else {
			return true;
		}

	}

}

?>