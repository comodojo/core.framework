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
 */

class ldap {
	
/********************** PRIVATE VARS *********************/
	private $ldaph = false;

	private $admode = false;

	private $ssl = false;

	private $tls = false;

	private $sso = false;

	private $dc = '';

	private $dn = '';

	private $suffix = '';

	private $user = null;

	private $pass = null;

	private $fields = Array("displayName","givenName","mail","description");

/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Constructor class
	 * 
	 * Prepare environment for connection and bind
	 */
	public function __construct($server, $port) {
		
		if ( empty($server) OR empty($port) ) {
			comodojo_debug('Invalid LDAP parameters','ERROR','ldap');
			throw new Exception("Invalid LDAP parameters", 1401);
		}
		
		if (!function_exists("ldap_connect")) {
			throw new Exception("PHP ldap extension not available", 1407);
		}

		$this->server = $server;
		$this->port = filter_var($port, FILTER_VALIDATE_INT);

		return $this;
		
	}

	public final function dc($dcs) {

		if ( empty($dcs) ) {
			comodojo_debug('Invalid dc','ERROR','ldap');
			throw new Exception($dcs, 1410);
		}

		$pDc = str_replace(' ', '', $dcs);

		$this->dc = $pDc;
		
		return $this;

	}

	public final function dn($dns) {

		if ( empty($dns) ) {
			comodojo_debug('Invalid dn','ERROR','ldap');
			throw new Exception($dns, 1411);
		}

		$pDn = str_replace(' ', '', $dns);

		$this->dn = $pDn;

		return $this;

	}

	public final function admode($mode=true) {

		if ($mode === true) {
			$this->admode = true;
		}
		else {
			$this->admode = false;
		}

		return $this;

	}

	public final function ssl($mode=true) {

		if ($mode === true) {
			$this->ssl = true;
		}
		else {
			$this->ssl = false;
		}

		return $this;

	}

	public final function tls($mode=true) {

		if ($mode === true) {
			$this->tls = true;
		}
		else {
			$this->tls = false;
		}

		return $this;

	}

	public final function sso($mode=true) {

		if ($mode === true) {
			if ( !function_exists('ldap_sasl_bind') ) {
				comodojo_debug('No LDAP SSO support','ERROR','ldap');
				throw new Exception("No LDAP SSO support", 1408);
			}
			$this->sso = true;
		}
		else {
			$this->sso = false;
		}

		return $this;

	}

	public final function account($user, $pass) {
		
		if ( empty($user) OR empty($pass)) {
			comodojo_debug('Invalid LDAP user/pass','ERROR','ldap');
			throw new Exception("Invalid LDAP user/pass", 1402);
		}
		
		$this->user = $user;
		$this->pass = $pass;
		
		return $this;

	}
	
	public final function suffix($s) {
		
		if ( empty($s) ) {
			comodojo_debug('Invalid suffix','ERROR','ldap');
			throw new Exception("Invalid suffix", 1409);
		}

		$this->suffix = '@'.$s;

		return $this;

	}

	public final function fields($f) {

		if ( empty($f) ) {
			$this->fields = null;
		}
		elseif ( is_array($f) ) {
			$this->fields = $f;
		}
		else {
			$this->fields = Array($f);
		}

		return $this; 
	}

	/**
	 * Authenticate an user via LDAP
	 * 
	 * @param	string	$userName	The user to auth
	 * @param	string	$userPass	The password for user
	 * 
	 * @return	bool
	 */
	public function auth($userName, $userPass) {
		
		if( empty($userName) OR empty($userPass) ) { 
			comodojo_debug('Invalid LDAP user/pass','ERROR','ldap');
			throw new Exception("Invalid LDAP user/pass", 1402);
		}
		
		comodojo_debug('Starting LDAP auth','INFO','ldap');
		
		try {
			$auth = $this->setupConnection($userName, $userPass);
		} catch (Exception $e) {
			$this->unsetConnection();
			throw $e;
		}

		$this->unsetConnection();

		return $auth;
			
	}
	
	/**
	 * Check if user is in LDAP directory
	 * 
	 * @param	string	$user	The user to search for
	 */
	public function user_exists($user, $id=false) {
		
		if (empty($user)) { 
			comodojo_debug('Invalid user to search for','ERROR','ldap');
			throw new Exception("Invalid user to search for", 1405);
		}
	
		comodojo_debug('Starting LDAP user presence check','INFO','ldap');
		
		if (!$id) {
			$id = $this->admode ? "samaccountname" : "uid";
		}

		try {
			$this->setupConnection($this->user, $this->pass);
			$result = $this->search_helper($id.'='.$user.$this->suffix);
		} catch (Exception $e) {
			$this->unsetConnection();
			throw $e;
		}

		$this->unsetConnection();

		if ($result["count"] == 1) return true;
		else return false;
		
	}
	
	/**
	 * List the directory
	 */
	public function search($what="*") {
			
		comodojo_debug('Starting LDAP directory search','INFO','ldap');
		
		try {
			$this->setupConnection($this->user, $this->pass);
			$result = $this->search_helper($what);
		} catch (Exception $e) {
			throw $e;
		}

		$this->unsetConnection();

		return $result;
	
		if (empty($what)) { 
			comodojo_debug('Invalid search filter','ERROR','ldap');
			throw new Exception("Invalid search filter", 1406);
		}
	
		comodojo_debug('Starting LDAP search','INFO','ldap');
		
		try {
			$this->setupConnection($this->user, $this->pass);
			$result = $this->search_helper($what);
		} catch (Exception $e) {
			$this->unsetConnection();
			throw $e;
		}

		$this->unsetConnection();

		return $result;

	}

/********************* PUBLIC METHODS ********************/

/********************* PRIVATE METHODS *******************/	
	/**
	 * Setup an LDAP connection to server
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
		
		if ($this->admode) {
			comodojo_debug('Using active directory mode for '.$this->server,'INFO','ldap');
			ldap_set_option($this->ldaph, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($this->ldaph, LDAP_OPT_REFERRALS, 0);
		}

		if ($this->tls) ldap_start_tls($this->ldaph);
		
		if ($this->sso AND $this->admode AND $_SERVER['REMOTE_USER'] AND $_SERVER["REMOTE_USER"] == $user AND $_SERVER["KRB5CCNAME"]) {
			putenv("KRB5CCNAME=".$_SERVER["KRB5CCNAME"]);
			$bind = @ldap_sasl_bind($this->ldaph, NULL, NULL, "GSSAPI");
		}
		else {
			$bind = @ldap_bind($this->ldaph, $user.$this->suffix, $pass);
		}
		if (!$bind) {
			comodojo_debug('Ldap error, server refuse to bind: '.ldap_error($this->ldaph),'ERROR','ldap');
			throw new Exception(ldap_error($this->ldaph), 1402);
		}

		return true;

	}
	
	/**
	 * Unset a previously opened ldap connection
	 */
	private function unsetConnection() {
		@ldap_unbind($this->ldaph);
	}

	/**
	 * 
	 */
	private function search_helper($what) {

		$base = $this->dc . $this->dn;

		if ( empty($this->fields) ) {
			$result = ldap_search($this->ldaph, $base, $what);
		}
		else {
			$result = ldap_search($this->ldaph, $base, $what, $this->fields);
		}

		if (!$result) {
			comodojo_debug('Unable to search through ldap directory','ERROR','ldap');
			throw new Exception(ldap_error($this->ldaph), 1404);
		}

		$to_return = ldap_get_entries($this->ldaph, $result);

		if (!$this->userList) {
			comodojo_debug('Unable to get ldap entries','ERROR','ldap');
			throw new Exception(ldap_error($this->ldaph), 1412);
		}
		
		return $to_return;
		
	}
	
/********************* PRIVATE METHODS *******************/
	
}

function loadHelper_ldap() { return false; }

?>