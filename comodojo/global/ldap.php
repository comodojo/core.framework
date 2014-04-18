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
	
/********************** PRIVATE VARS *********************/
	private $ldaph = false;
	private $listFilter = false;
	private $listerUserName = false;
	private $listerUserPass = false;
	private $domain = false;
	private $userList = false;
	private $userDn = false;
	private $ssl = false;
	private $tls = false;
	private $sso = false;
	private $suffix = null;
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Constructor class
	 * 
	 * Connect & bind to LDAP server
	 */
	public function __construct($server, $port=389, $dcs, $dns=false, $filter=null, $listuser=null, $listpass=null, $cmode=true, $suffix=null, $ssl=false, $tls=false, $sso=false) {
		
		if (empty($server) OR empty($port) OR empty($dcs)) {
			comodojo_debug('Invalid LDAP parameters','ERROR','ldap');
			throw new Exception("Invalid LDAP parameters", 1401);
		}
		
		if (!function_exists("ldap_connect")) {
			throw new Exception("PHP ldap extension not available", 1407);
		}

		$this->server = $server;
		$this->port = filter_var($port, FILTER_VALIDATE_INT);
		$this->dcs = $dcs;
		$this->dns = $dns;
		$this->filter = $filter;
		$this->listuser = $listuser;
		$this->listpass = $listpass;
		$this->cmode = filter_var($cmode, FILTER_VALIDATE_BOOLEAN);
		$this->suffix = $suffix;
		$this->ssl = filter_var($ssl, FILTER_VALIDATE_BOOLEAN);
		$this->tls = filter_var($tls, FILTER_VALIDATE_BOOLEAN);
		$this->sso = filter_var($sso, FILTER_VALIDATE_BOOLEAN);

		if ($this->sso AND !function_exists('ldap_sasl_bind')) {
			comodojo_debug('No LDAP SSO support','ERROR','ldap');
			throw new Exception("No LDAP SSO support", 1408);
		}

		$this->composeDc($dcs);
		
	}
	
	/**
	 * Authenticate an user via LDAP
	 * 
	 * @param	string	$userName	The user to auth
	 * @param	string	$userPass	The password for user
	 * 
	 * @return	bool
	 */
	public function ldapAuth($userName, $userPass) {
		
		if( empty($userName) OR empty($userPass) ) { 
			comodojo_debug('Invalid LDAP user/pass','ERROR','ldap');
			throw new Exception("Invalid LDAP user/pass", 1402);
		}
		
		//$this->userName = $userName;
		//$this->userPass = $userPass;

		comodojo_debug('Starting LDAP auth','INFO','ldap');
		
		try {
			$auth = $this->setupConnection($userName, $userPass);
		} catch (Exception $e) {
			throw $e;
		}

		return $auth;

		//$this->listFilter = "(|(" . $this->filter . $this->userName . "*))";
		//
		//if (!$this->listDirectoryHelper()) {
		//	comodojo_debug('Unable to list directory','ERROR','ldap');
		//	$this->unsetConnection();
		//	throw new Exception("Unable to list directory", 1404);
		//}
		//
		//if ($this->userList["count"] == 0) {
		//	comodojo_debug('Unknown user','INFO','ldap');
		//	$toReturn = false;
		//}
		//elseif ($this->userList["count"] > 1) {
		//	comodojo_debug('Multiple user match serach criteria','INFO','ldap');
		//	$toReturn = false;
		//}
		//else {
		//	$this->userDn = $this->userList[0]["dn"];
		//		if (!$this->_bindSingleUser()) {
		//			comodojo_debug('Wrong user password','INFO','ldap');
		//			$toReturn = false;
		//		}
		//		else {
		//			comodojo_debug('User '.$this->userName.' authenticated','INFO','ldap');
		//			$toReturn = true;
		//		}
		//}
		
		//$this->unsetConnection();
		
		//return $toReturn;
			
	}
	
	/**
	 * Check if user is in LDAP directory
	 * 
	 * @param	string	$user	The user to search for
	 */
	public function checkUserInDirectory($userToSearch) {
		
		if (empty($userToSearch)) { 
			comodojo_debug('Invalid user to search for','ERROR','ldap');
			throw new Exception("Invalid user to search for", 1405);
		}
	
		comodojo_debug('Starting LDAP user presence','INFO','ldap');
		
		if (!$this->setupConnection()) {
			comodojo_debug('Unable to connect to ldap server','ERROR','ldap');
			throw new Exception("Unable to connect to ldap server", 1403);
		}
	
		$this->listFilter = $this->filter . $this->userName;
		
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
		
		$this->listFilter = $this->filter . "*";
		
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
	private function setupConnection($user=null, $pass=null) {

		if ($this->ssl) {
			$this->ldaph = ldap_connect("ldaps://".$this->server, $this->port);
		}
		else {
			$this->ldaph = ldap_connect($this->server, $this->port);
		}
		
		if (!$this->ldaph) {
			comodojo_debug('Unable to connect to ldap server: '.ldap_error($this->ldaph),'ERROR','ldap');
			throw new Exception(ldap_error($this->ldaph), 1403);
		}
		
		comodojo_debug('Connected to LDAP server '.$this->server,'INFO','ldap');
		
		if ($this->cmode) {
			comodojo_debug('Using compatible mode to contact LDAP server '.$this->server,'INFO','ldap');
			ldap_set_option($this->ldaph, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($this->ldaph, LDAP_OPT_REFERRALS, 0);
		}

		if ($this->tls) ldap_start_tls($this->ldaph);
		
		if ($user !== null AND $pass !== null) {
			//it is an authentication request, so try to bind with user credentials
			if ($this->sso AND $_SERVER['REMOTE_USER'] AND $_SERVER["REMOTE_USER"] == $user AND $_SERVER["KRB5CCNAME"]) {
				putenv("KRB5CCNAME=".$_SERVER["KRB5CCNAME"]);
				$bind = @ldap_sasl_bind($this->ldaph, NULL, NULL, "GSSAPI");
			}
			else {
				$bind = @ldap_bind($this->ldaph, $user.$this->suffix, $pass);
			}
			if (!$bind) {
				comodojo_debug('Auth error, server refuse to bind: '.ldap_error($this->ldaph),'ERROR','ldap');
				throw new Exception(ldap_error($this->ldaph), 1402);
			}
			return true;
		}
		else {

			//it is a search/list request, so try to bind with admin/null credentials

		}



		$this->listerUserName = is_null($this->listuser) ? $this->userName : $this->listuser;
		$this->listerUserPass = is_null($this->listpass) ? $this->userPass : $this->listpass;
		

		$bind = @ldap_bind($this->ldaph,$this->listerUserName,$this->listerUserPass);
		if (!$bind) {
			comodojo_debug('Server refuse to bind: '.ldap_error($this->ldaph),'ERROR','ldap');
			return false;
		}
		
		comodojo_debug('Binded to LDAP server '.$this->server,'INFO','ldap');
		
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
	private function composeDc($dcs) {
		$pDc = str_replace(' ', '', $dcs);
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

		$search_string = trim($this->dcs).($this->dns !== NULL ? ",".trim($this->dns) : '');

		comodojo_debug($search_string);

		$result = ldap_search($this->ldaph, $search_string, $this->listFilter);

		comodojo_debug(ldap_error($this->ldaph));

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