<?php

/**
 * http.php
 * 
 * Send a http request (optionally with basic authentication support) to 
 * remote host using GET or POST methods and,
 * 
 * Request will be sent using curl (if available) or fsock's (fallback)
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 
class http {

/*********************** PUBLIC VARS *********************/
	/**
	 * Remote host address (complete url)
	 */
	public $address = false;
	
	/**
	 * Remote host port
	 */
	public $port = 80;
	
	/**
	 * Conversation method (GET or POST)
	 */
	public $method = 'GET';
	
	/**
	 * Data to send to host, in unidimensional array form or simple string
	 * 
	 * Example:
	 * 
	 * ::data = Array('dateFrom'=>'2010-10-10','dateTo'=>'2010-10-20')
	 * 
	 * AND GET method will result in:
	 * 
	 * URL->[YOUR_ADDRESS]?dateFrom=2010-10-10&dateTo=2010-10-20
	 * 
	 * @param	ARRAY
	 */
	private $data = false;
	
	/**
	 * Timeout for request, in seconds.
	 * 
	 * @param	INT	seconds
	 * @default	30
	 */
	public $timeout = 30;
	
	/**
	 * HTTP Version (1.0/1.1)
	 * 
	 * @param	STRING	
	 * @default	false	auto
	 */
	public $httpVersion = false;
	
	/**
	 * Does host require a basic auth?
	 */
	public $isAuthenticated = false;
	
	/**
	 * Auth method to use. It currently support only:
	 * - BASIC
	 * - NTLM (only if CURL is available)
	 */
	public $authenticationMethod = 'BASIC';
	
	/**
	 * Remote host auth username
	 */
	public $userName;
	
	/**
	 * Remote host auth password
	 */
	public $userPass;

	/**
	 * Encoding of request
	 * PLEASE NOTE: this should be the same used in xmlRpcEncoder!
	 */
	public $encoding = COMODOJO_DEFAULT_ENCODING;
	
	/**
	 * Request user agent
	 * 
	 * @param	STRING
	 * @default	Comodojo-core_1.0-beta
	 */
	public $userAgent = 'Comodojo-core___CURRENT_VERSION__';
	
	/**
	 * Content type
	 * 
	 * @param	STRING
	 * @default	text/xml
	 */
	public $contentType = 'application/x-www-form-urlencoded';
/*********************** PUBLIC VARS *********************/

/********************** PRIVATE VARS *********************/
	/**
	 * Are we using curl?
	 */
	private $_usingCurl = true;
	
	/**
	 * Data-to-send holder.
	 * @var string
	 */
	private $sent = false;

	/**
	 * Received data holder.
	 * @var string
	 */
	private $received = '';
	
	/**
	 * Remote host 
	 * @var string
	 */
	private $remoteHost = false;

	/**
	 * Remote host path
	 * @var string
	 */
	private $remotePath = false;
	
	/**
	 * Transfer channel
	 * @var resource
	 */
	private $ch = false;
/********************** PRIVATE VARS *********************/

/********************* PRIVATE METHODS *******************/
	/**
	 * Initialize transport layer
	 */
	private function _initTransport() {
		
		if ($this->_usingCurl) {
		
			$this->ch = curl_init();
			
			if (!$this->ch) {
				comodojo_debug("Cannot init data channel","ERROR","httpTalk");
				throw new Exception("Cannot init data channel", 1501);
			}
			
			if (strtoupper($this->method) == 'POST') {
				curl_setopt($this->ch, CURLOPT_POST, true);
				if ($this->data !== false) curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->data);
				curl_setopt($this->ch, CURLOPT_URL, $this->address);
			}
			elseif (strtoupper($this->method) == 'GET' AND is_array($this->data)) {
				curl_setopt($this->ch, CURLOPT_URL, $this->address.'?'.http_build_query($this->data));
			}
			else {
				curl_setopt($this->ch, CURLOPT_URL, $this->address . (is_string($this->data) ? '?'.urlencode($this->data) : ''));
			}
			
			
			if ($this->httpVersion == '1.0') curl_setopt($ch,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_0);
			elseif ($this->httpVersion == '1.1') curl_setopt($ch,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
			else curl_setopt($ch,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_NONE);
		    
		    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($this->ch, CURLOPT_PORT, $this->port);
			
			if ($this->isAuthenticated) {
				curl_setopt($this->ch, CURLOPT_HTTPAUTH, strtoupper($this->authenticationMethod == 'NTLM') ? CURLAUTH_NTLM : CURLAUTH_BASIC);
				curl_setopt($this->ch, CURLOPT_USERPWD, $this->userName.":".$this->userPass); 
			}
			
			curl_setopt($this->ch, CURLOPT_USERAGENT, $this->userAgent);
			
		}
		else {
				
			if ($this->isAuthenticated AND $this->authenticationMethod == 'NTLM') {
				comodojo_debug("NTLM auth with FSOCKS not supported","ERROR","httpTalk");
				throw new Exception("NTLM auth with FSOCKS not supported", 1505);
			}
			$crlf = "\r\n";
			
			$_data = null;
			switch(true) {
				case is_array($this->data):
					$_data = http_build_query($this->data);
				break;
				case is_string($this->data) :
					$_data = urlencode($this->data);
				break;
				default:
					$_data = '';
				break;
			}
			
			if (strtoupper($this->method) == 'GET') $_data = '?'.$_data;
			
			$header  = strtoupper($this->method).' '.$this->remotePath.$_data.' HTTP/'.$this->httpVersion.$crlf;
			$header .= "User-Agent: ".$this->userAgent.$crlf;
			$header .= "Host: ".$this->remoteHost.$crlf;
			
			if ($this->isAuthenticated) $header .= "Authorization: Basic ".base64_encode($this->userName.":".$this->userPass).$crlf;
			
			$header .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'.$crlf;
            $header .= 'Accept-Language: en-us,en;q=0.5'.$crlf;
            $header .= 'Accept-Encoding: deflate'.$crlf;
            $header .= 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7'.$crlf.$crlf;
            
            if (strtoupper($this->method) == 'POST') {
            	$header .= "Content-Type: ".$this->contentType.$crlf;
				$header .= "Content-Length: ".strlen($_data).$crlf.$crlf;
				$this->sent = $header.$_data;
            }
			else {
				$this->sent = $header;
			}
            
			$this->ch = fsockopen($this->remoteHost, $this->port, $errno, $errstr, $this->timeout);
			
			if (!$this->ch) {
				comodojo_debug("Cannot init data channel, fsock error: ".$errno." - ".$errstr,"ERROR","httpTalk");
				throw new Exception("Cannot init data channel", 1501);
			}
			
		}

		comodojo_debug("Ready to send data: ".$this->data,"DEBUG","httpTalk");
		
	}

	/**
	 * Close transport layer
	 */
	private function _closeTransport() {
		if ($this->_usingCurl) {
			curl_close($this->ch);
		}
		else {
			fclose($this->ch);
		}
	}
/********************* PRIVATE METHODS *******************/
	
/********************* PUBLIC METHODS ********************/
	/**
	 * Init transport and send data to the remote host.
	 * 
	 * @return	string	Received Data
	 */
	public function send($data = false) {
			
		if (!$this->address) throw new Exception("Invalid remote host address", 1502);
		
		//if ($data) throw new Exception("Invalid data", 1503);
		
		if ($data !== false) $this->data = $data;
		
		if (function_exists("curl_init")) {
			$this->_usingCurl = true;
			comodojo_debug("Using curl for transaction","DEBUG","httpTalk");
		}
		else {
			$this->_usingCurl = false;
			$_url = parse_url($address);
			$this->remoteHost = $_url['host'];
			$this->remotePath = $_url['path'];
			comodojo_debug("Using fsock for transaction","DEBUG","httpTalk");
		}
		
		try {
			$this->_initTransport();
		}
		catch (Exception $e) {
			throw $e;
		}
		
		if ($this->_usingCurl) {
			$this->received = curl_exec($this->ch);
			if ($this->received === false) {
				comodojo_debug("Cannot exec http request, curl error: ".curl_errno($this->ch)." - ".curl_error($this->ch),"ERROR","httpTalk");
				throw new Exception("Cannot exec http request", 1504);
			}
		}
		else {
			//if (strtoupper($this->method) == 'POST') {
			//	fputs($this->ch, $this->sent, strlen($this->sent));
			//		while (!feof($this->ch)) {
			//		$this->received .= fgets($this->ch, 4096);
			//	}
			//}
			$received = fwrite($this->ch, $this->sent, strlen($this->sent));
			if ($received === false) {
				comodojo_debug("Cannot exec http request, fwrite error.","ERROR","httpTalk");
				throw new Exception("Cannot exec http request", 1504);
			}
			$this->received = '';
			while ($line = fgets($this->ch)) $this->received .= $line;
			$this->received = substr($this->received, strpos($this->received, "\r\n\r\n") + 4);
		}
		
		$this->_closeTransport();
		
		comodojo_debug("Data received: ".$this->received,"DEBUG","httpTalk");
		
		return $this->received;
	}
	
	/**
	 * Reset the data channel for new request
	 * 
	 */
	public function resetChannel() {
		$this->address = false;
		$this->port = 80;
		$this->method = 'GET';
		$this->data = false;
		$this->timeout = 30;
		$this->httpVersion = false;
		$this->isAuthenticated = false;
		$this->authenticationMethod = 'BASIC';
		$this->userName;
		$this->userPass;
		$this->encoding = COMODOJO_DEFAULT_ENCODING;
		$this->userAgent = 'Comodojo-core___CURRENT_VERSION__';
		$this->contentType = 'application/x-www-form-urlencoded';
		$this->sent = false;
		$this->received = '';
		$this->ch = false;
	}
/********************* PUBLIC METHODS ********************/

}

function loadHelper_http() { return false; }

?>