<?php

/**
 * ssh.php
 * 
 * SSH connection manager.
 * 
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

/**
 * The ssh class in the little comodojo world.
 * 
 * PLEASE NOTE: this class will return exceptions ONLY on constructor and connect(), NO STD ARRAY HERE!
 * 
 */
class ssh {

/*********************** PUBLIC VARS **********************/
	/**
	 * Force non-native ssh implementation (instead use phpseclib)
	 * @var	bool 
	 */
	public $forceSecLib = false;
	
	/**
	 * Remote device address
	 * @var	string	contains IP address or valid hostname 
	 */
	public $address = false;
	
	/**
	 * Remote port
	 * @var	integer	a valid TCP port (up to 65535)
	 */
	public $port = 22;
	
	/**
	 * Connection timeout
	 * @var	integrer	seconds to wait for connection
	 */
	public $timeout = 10;

	/**
	 * Remote device username
	 * @var	string
	 */
	public $user = false;
	
	/**
	 * Remote device password
	 * @var	string 
	 */
	public $password = false;
/*********************** PUBLIC VARS **********************/
	
/********************** PRIVATE VARS **********************/
	/**
	 * Is native ssh implementation used?
	 */
	private $isNativeSSH = false;
	
	/**
	 * Internal pointer to ssh connection
	 */
	private $_ssh = false;
	
	/**
	 * Internal pointer to sftp connection
	 */
	private $_sftp = false;

	/**
	 * Internal time reference
	 */
	private $_fixedTimeReference = false;
/********************** PRIVATE VARS **********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Constructor class; it will prepare ssh environment.
	 * 
	 * You can specify one or more parameter as listed below.
	 * 
	 * @param	string	$address	Remote address (IP) or hostname
	 * @param	integer	$port		A valid TCP port (ip to 65535)
	 * @param	string	$user		Remote device username
	 * @param	strin	$password	Remote device password
	 */
	public function __construct($address = false, $port = false, $user = false, $password = false) {
		
		$this->address = !$address ? $this->address : $address;
		$this->user = !$user ? $this->user : $user;
		$this->password = !$password ? $this->password : $password;
		
		comodojo_debug("Connecting to ".$this->address." in ssh as ".$this->user,"INFO","ssh");
		
		$this->_fixedTimeReference = strtotime('now');
		
	}

	/**
	 * Connect to $this::address.
	 * Throws exception in case of errors :)
	 */
	public function connect() {
		
		if (!$this->address OR !$this->user OR !$this->password) throw new Exception("Invalid address, user or password",1601);
		
		if (function_exists("ssh2_connect") AND !$this->forceSecLib) {
			$this->isNativeSSH = true;
			$this->_ssh = ssh2_connect($this->address, $this->port);
			if (!ssh2_auth_password($this->_ssh, $this->user, $this->password)) throw new Exception("Failed authentication or network unavailable",1602);
			comodojo_debug("Connected to ".$this->address." w native transport","INFO","ssh");
		}
		else {
			comodojo_load_resource('Net/SSH2');
			comodojo_load_resource('Net/SFTP');
			$this->isNativeSSH = false;
			$this->_ssh = new Net_SSH2($this->address,$this->port,$this->timeout);
			if (!$this->_ssh->login($this->user, $this->password)) throw new Exception("failed authentication or network unavailable",1602);
			comodojo_debug("Connected to ".$this->address." w phplibsec transport","INFO","ssh");
		}
		
	}
	
	/**
	 * Exec command on remote device and return result.
	 * 
	 * @param	string	$command	A string contains command to exec on remote device
	 */
	public function exec($command = false) {
		
		if (!$command) throw new Exception("Invalid command",1603);
		
		comodojo_debug("Executing command: ".$command,"INFO","ssh");
		
		if ($this->isNativeSSH) {
			return ssh2_exec($this->_ssh, $command);
		}
		else {
			return $this->_ssh->exec($command); 
		}
		
	}

	/**
	 * Get file from device, in sftp or scp
	 * 
	 * @param	string	$remote	The remote file (path+fileName) to get
	 * 
	 * @return	string			A string contains the remote file content
	 */
	public function get($remote = false) {
		
		if (!$remote) throw new Exception("Invalid remote file",1604);
		
		comodojo_debug("Getting remote file: ".$remote,"INFO","ssh");
		
		$local = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_TEMP_FOLDER."file_get_".str_replace("/","_",$remote).$this->_fixedTimeReference;
		
		if ($this->isNativeSSH) {
			$result = ssh2_scp_recv($this->_ssh, $remote, $local);
			if (!$result) throw new Exception("Error retrieving remote file",1605);
			$toReturn = file_get_contents($local);
			unlink($local);
			return $toReturn;
		}
		else {
			if (!$this->_sftp){
				$this->_sftp = new Net_SFTP($this->address,$this->port,$this->timeout);
				if (!$this->_sftp->login($this->user, $this->password)) throw new Exception("failed authentication or network unavailable",1602);
			}
			$result = $this->_sftp->get($remote, $local);
			if (!$result) throw new Exception("Error retrieving remote file",1605);
			$toReturn = file_get_contents($local);
			unlink($local);
			return $toReturn;
		}
		
	}

	/**
	 * Put content in remote file
	 * 
	 * @param	string	$content	The content of remote file to write
	 * @param	string	$remote		The name of the remote file
	 * 
	 * @return	bool				TRUE on success (throws exception on errors) 
	 */
	public function put($content=NULL,$remote) {
		
		if (!$remote) throw new Exception("Invalid remote file",1604);
		
		comodojo_debug("Writing remote file: ".$remote,"INFO","ssh");
		
		if ($this->isNativeSSH) {
			$local = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_TEMP_FOLDER."file_put_".str_replace("/","_",$remote).$this->_fixedTimeReference;
			$fh = fopen($local, 'w');
			if (!$fh) throw new Exception("Error writing local file",1607);
			if (!fwrite($fh, $content)) throw new Exception("Error writing local file",1607);
			fclose($fh);
			$result = ssh2_scp_send($this->_ssh, $local, $remote);
			unlink($local);
			if (!$result) throw new Exception("Error writing remote file",1606);
			return true;
		}
		else {
			if (!$this->_sftp){
				$this->_sftp = new Net_SFTP($this->address,$this->port,$this->timeout);
				if (!$this->_sftp->login($this->user, $this->password)) throw new Exception("failed authentication or network unavailable",1602);
			}
			$result = $this->_sftp->put($remote, $content);
			if (!$result) throw new Exception("Error writing remote file",1606);
			return true;
		}
		
	}
/********************* PUBLIC METHODS ********************/

}

function loadHelper_ssh() { return false; }

?>