<?php

/** 
 * ldap.php
 * 
 * talk with local ldap for user auth or forest browsing
 *
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 * 
 * @todo		Class currently works but it's still unfinished
 */

class ldap {
	
/*********************** PUBLIC VARS *********************/
	/**
	 * Name for user to auth or to search
	 * @var	string
	 */
	public $userName = false;
	
	/**
	 * Password for user to auth or to search
	 * @var	string
	 */
	public $userPass = false;
/*********************** PUBLIC VARS *********************/

/********************** PRIVATE VARS *********************/
	private $ldaph = false;
	private $listFilter = false;
	private $listerUserName = false;
	private $listerUserPass = false;
	private $domain = false;
	private $userList = false;
	private $userDn = false;
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Constructor class
	 * 
	 * Connect & bind to LDAP server
	 */
	public function __construct() {
		
		if (is_null(COMODOJO_LDAP_SERVER) OR is_null(COMODOJO_LDAP_PORT) OR is_null(COMODOJO_LDAP_DC)) {
			comodojo_debug('Invalid LDAP parameters','ERROR','ldap');
			throw new Exception("Invalid LDAP parameters", 1401);
		}
		
		$this->composeDc();
		
	}
	
	/**
	 * Authenticate an user via LDAP
	 * 
	 * @param	string	$userName	The user to auth
	 * @param	string	$userPass	The password for user
	 * 
	 * @return	bool
	 */
	public function ldapAuth($userName=false, $userPass=false) {
		
		if ($userName === false) $this->userName = $userName;
		if ($userPass === false) $this->userPass = $userPass;
		
		if(!$this->userName OR !$this->userPass) { 
			comodojo_debug('Invalid LDAP user/pass','ERROR','ldap');
			throw new Exception("Invalid LDAP user/pass", 1402);
		}
		
		comodojo_debug('Starting LDAP auth','INFO','ldap');
		
		if (!$this->setupConnection()) {
			comodojo_debug('Unable to connect to ldap server','ERROR','ldap');
			throw new Exception("Unable to connect to ldap server", 1403);
		}
		
		$this->listFilter = COMODOJO_LDAP_FILTER . $this->userName;
		
		if (!$this->listDirectoryHelper()) {
			comodojo_debug('Unable to list directory','ERROR','ldap');
			$this->unsetConnection();
			throw new Exception("Unable to list directory", 1404);
		}
		
		if ($this->userList["count"] == 0) {
			comodojo_debug('Unknown user','INFO','ldap');
			$toReturn = false;
		}
		elseif ($this->userList["count"] > 1) {
			comodojo_debug('Multiple user match serach criteria','INFO','ldap');
			$toReturn = false;
		}
		else {
			$this->userDn = $this->userList[0]["dn"];
				if (!$this->_bindSingleUser()) {
					comodojo_debug('Wrong user password','INFO','ldap');
					$toReturn = false;
				}
				else {
					comodojo_debug('User '.$this->userName.' authenticated','INFO','ldap');
					$toReturn = true;
				}
		}
		
		$this->unsetConnection();
		
		return $toReturn;
			
	}
	
	/**
	 * Check if user is in LDAP directory
	 * 
	 * @param	string	$user	The user to search for
	 */
	public function checkUserInDirectory($user=false) {
		
		$userToSearch = !$user ? $this->userName : $user;
		
		if (!$userToSearch) { 
			comodojo_debug('Invalid user to search for','ERROR','ldap');
			throw new Exception("Invalid user to search for", 1405);
		}
	
		comodojo_debug('Starting LDAP user presence','INFO','ldap');
		
		if (!$this->setupConnection()) {
			comodojo_debug('Unable to connect to ldap server','ERROR','ldap');
			throw new Exception("Unable to connect to ldap server", 1403);
		}
	
		$this->listFilter = COMODOJO_LDAP_FILTER . $this->userName;
		
		if (!$this->listDirectoryHelper()) {
			comodojo_debug('Unable to list directory','ERROR','ldap');
			$this->unsetConnection();
			throw new Exception("Unable to list directory", 1404);
		}
		
		if ($this->userList["count"] == 1) $toReturn = true;
		else $toReturn = false;
		
		$this->unsetConnection();
		
		return $toReturn;
		
	}
	
	/**
	 * List the directory
	 */
	public function listDirectory() {
			
		comodojo_debug('Starting LDAP directory listing','INFO','ldap');
		
		if (!$this->setupConnection()) {
			comodojo_debug('Unable to connect to ldap server','ERROR','ldap');
			throw new Exception("Unable to connect to ldap server", 1403);
		}
		
		$this->listFilter = COMODOJO_LDAP_FILTER . "*";
		
		if (!$this->listDirectoryHelper()) {
			comodojo_debug('Unable to list directory','ERROR','ldap');
			$this->unsetConnection();
			throw new Exception("Unable to list directory", 1404);
		}
		
		comodojo_debug('LDAP directory listing completed','INFO','ldap');
		
		$this->unsetConnection();
	
		return $this->userList;
	
	}
	
	/**
	 * Search directory by custom filter
	 * 
	 * @param	string	$filter	A custom filter to search for
	 */
	public function searchDirectory($filter) {
		
		if (!$filter) { 
			comodojo_debug('Invalid filter to search for','ERROR','ldap');
			throw new Exception("Invalid filter to search for", 1406);
		}
		
		comodojo_debug('Starting LDAP directory search with filter: '.$fitler,'INFO','ldap');
		
		if (!$this->setupConnection()) {
			comodojo_debug('Unable to connect to ldap server','ERROR','ldap');
			throw new Exception("Unable to connect to ldap server", 1403);
		}
		
		$this->listFilter = $filter;
		
		if (!$this->listDirectoryHelper()) {
			comodojo_debug('Unable to list directory','ERROR','ldap');
			$this->unsetConnection();
			throw new Exception("Unable to list directory", 1404);
		}
		
		comodojo_debug('LDAP directory search completed','INFO','ldap');
		
		$this->unsetConnection();
	
		return $this->userList;
	
	}

/********************* PUBLIC METHODS ********************/

/********************* PRIVATE METHODS *******************/	
	/**
	 * Setup an LDAP connection to server (global)
	 */
	private function setupConnection() {
		
		$this->listerUserName = is_null(COMODOJO_LDAP_LISTER_USERNAME) ? $this->userName : COMODOJO_LDAP_LISTER_USERNAME;
		$this->listerUserPass = is_null(COMODOJO_LDAP_LISTER_PASSWORD) ? $this->userPass : COMODOJO_LDAP_LISTER_PASSWORD;
		 
		$this->ldaph = ldap_connect(COMODOJO_LDAP_SERVER, COMODOJO_LDAP_PORT);
		if (!$this->ldaph) {
			comodojo_debug('LDAP server '.COMODOJO_LDAP_SERVER.' is not responding','ERROR','ldap');
			return false;
		}
		
		comodojo_debug('Connected to LDAP server '.COMODOJO_LDAP_SERVER,'INFO','ldap');
		
		if (COMODOJO_LDAP_COMPATIBLE) {
			ldap_set_option($this->ldaph, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($this->ldaph, LDAP_OPT_REFERRALS, 0);
		}
		
		$bind = @ldap_bind($this->ldaph,$this->listerUserName,$this->listerUserPass);
		if (!$bind) {
			comodojo_debug('Server refuse to bind: '.ldap_error($this->ldaph),'ERROR','ldap');
			return false;
		}
		
		comodojo_debug('Binded to LDAP server '.COMODOJO_LDAP_SERVER,'INFO','ldap');
		
		return true;

	}
	
	/**
	 * Unset a previously opened ldap connection
	 */
	private function unsetConnection() {
		@ldap_unbind($this->ldaph);
	}

	/**
	 * Compose DC from global DC's
	 */
	private function composeDc() {
		$pDc = str_replace(' ', '', COMODOJO_LDAP_DC);
	  	$tDc = explode(",",$pDc);
		foreach($tDc as $i=>$n) {
			$tDc_p = explode("dc=",$n);
			$tDc[$i] = $tDc_p[1];
		}
		foreach($tDc as $i=>$n) {
			$this->domain .= $n.".";
		}
		$this->domain = "@".substr_replace($this->domain ,"",-1);
	}
	
	/**
	 * Internal directory lister
	 */
	private function listDirectoryHelper() {
		
		$result = ldap_search($this->ldaph,trim(COMODOJO_LDAP_DC).(COMODOJO_LDAP_OTHER_DN !== NULL ? ",".trim(COMODOJO_LDAP_OTHER_DN) : ''),$this->listFilter);
		if (!$result) {
			comodojo_debug('Unable to search through ldap directory','ERROR','ldap');
			return false;
		}
		$this->userList = ldap_get_entries($this->ldaph,$result);
		if (!$this->userList) {
			comodojo_debug('Unable to get entries from ldap directory','ERROR','ldap');
			return false;
		}
		
		return true;
		
	}
	
	/**
	 * Bind single user to ldap
	 */
	private function bindSingleUser() {
		$bind = @ldap_bind($this->ldaph,$this->userDn,$this->userPass);
		return !$bind ? false : true;
	}
/********************* PRIVATE METHODS *******************/
	
}

function loadHelper_ldap() { return false; }

?>