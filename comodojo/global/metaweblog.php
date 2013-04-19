<?php

/** 
 * metaweblog.php
 * 
 * Interface to talk to xmlrpc-powered interfaces supporting metaweblog protocol
 * 
 * MetaWeblog API RFC available at: http://xmlrpc.scripting.com/metaWeblogApi.html
 *
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

/*
 * Include sender, then, if php_xmlrpc is not installed, load local libs
 */
comodojo_load_resource('http');
if (!function_exists('xmlrpc_encode_request')) {
	comodojo_load_resource('xmlRpcEncoder');
	comodojo_load_resource('xmlRpcDecoder');
}

/**
 * Talk with a generic metaWeblog xmlrpc interface
 *  
 */
class metaweblog {
	
	/**
	 * Address of the xmlrpc server interface
	 * 
	 * @param	STRING
	 */
	public $weblogAddress = false;
	
	/**
	 * Username to send to remote server
	 * 
	 * @param	STRING
	 */
	public $weblogUserName = false;
	
	/**
	 * Password to send to remote server
	 * 
	 * @param	STRING
	 */
	public $weblogUserPass = false;
	
	/**
	 * Weblog ID (leave it 0 if you're in single-blog mode)
	 * 
	 * @param	STRING
	 */
	public $weblogId = 0;
	
	/**
	 * Messages encoding (will be applied to - almost - every string!)
	 * 
	 * @param	STRING
	 */
	public $encoding = COMODOJO_DEFAULT_ENCODING;

/************************************************************/
	 
 	private $_nativeRPC = true;

/************************************************************/
	
	/**
	 * Class constructor
	 * 
	 * @param	STRING	$weblogAddress
	 * @param	STRING	$weblogUserName
	 * @param	STRING	$weblogUserName
	 */
	public function __construct($weblogAddress, $weblogUserName, $weblogUserPass) {
		if (!$weblogAddress OR !$weblogUserName OR !$weblogUserPass) throw new Exception('Invalid parameters passed');
		$this->_nativeRPC = !function_exists('xmlrpc_encode_request') ? false : true;
		
		$this->weblogAddress = $weblogAddress;
		$this->weblogUserName = $weblogUserName;
		$this->weblogUserPass = $weblogUserPass;
	}
	
	/**
	 * Get [$howmany] posts from blog
	 * 
	 * @param	INT|FALSE	$howmany	Number of post to ask for
	 * @return	ARRAY					Posts
	 */
	public function getPosts($howmany=false) {
		$params = array($this->weblogId,$this->weblogUserName,$this->weblogUserPass,!$howmany ? 10 : $howmany);
		try {
			$request = $this->_execRequest('metaWeblog.getRecentPosts', $params);
			$toReturn = $this->returnResult($request);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $toReturn;
	}
	
	/**
	 * Retrieve a post from weblog
	 * 
	 * @param	INT|STRING	$postId	Post's ID
	 * @return	ARRAY				Post
	 */
	public function getPost($postId) {
		$params = array($postId,$this->weblogUserName,$this->weblogUserPass);
		try {
			$request = $this->_execRequest('metaWeblog.getPost', $params);
			$toReturn = $this->returnResult($request);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $toReturn;
	}
	
	/**
	 * Retrieve a list of categories from weblog
	 * 
	 * @return	ARRAY	Categories
	 */
	public function getCategories() {
		$params = array($this->weblogId,$this->weblogUserName,$this->weblogUserPass);
		try {
			$request = $this->_execRequest('metaWeblog.getCategories', $params);
			$toReturn = $this->returnResult($request);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $toReturn;
	}
	
	/**
	 * Write post using remote server xmlrpc interface
	 * 
	 * A post struct currently support a non-standard set of elements to better
	 * support modern interfaces such as wordpress one. Anyway, server should ignore
	 * elements not known.
	 * 
	 * Minimum $struct elements to compose new post are:
	 *  - title			STRING	the post title
	 *  - description	STRING	the post content
	 * 
	 * If one or both not defined, method will throw an "Invalid post struct" error.
	 * 
	 * @param	ARRAY	$struct		A post stuct
	 * @return	INT					Assigned post ID
	 */
	public function writePost($struct) {
		if (!$struct['title'] OR !$struct['description']) throw new Exception('Invalid post struct');
		else {
			if (isset($struct['categories'])) {
				$_categories = explode(",", $struct['categories']);
				$categories = Array();
				foreach($_categories as $category_name) {
					if ($category_name != "") array_push($categories,stripslashes(mb_convert_encoding($category_name, $this->encoding)));
				}
			}
			else $categories = Array();
			$_struct = Array(
				'title'				=>	stripslashes(mb_convert_encoding($struct['title'], $this->encoding)),
				'description'		=>	stripslashes(mb_convert_encoding($struct['description'], $this->encoding)),
				'mt_text_more'		=>	isset($struct['mt_text_more']) ? stripslashes(mb_convert_encoding($struct['mt_text_more'], $this->encoding)) : false,
				'post_type'			=>	'post',
				'mt_allow_comments'	=>	isset($struct['mt_allow_comments']) ? ($struct['mt_allow_comments'] ? 1 : 0) : 0,
        		'mt_allow_pings'	=>	isset($struct['mt_allow_pings']) ? ($struct['mt_allow_pings'] ? 1 : 0) : 0,
        		'sticky'			=>	isset($struct['sticky']) ? ($struct['sticky'] ? 1 : 0) : 0,
        		'categories'		=>	$categories,
        		'mt_excerpt'		=>	isset($struct['mt_excerpt']) ? stripslashes(mb_convert_encoding($struct['mt_excerpt'], $this->encoding)) : false,
        		'mt_keywords'		=>	isset($struct['mt_keywords']) ? stripslashes(mb_convert_encoding($struct['mt_keywords'], $this->encoding)) : false
			);
			$params = array($this->weblogId,$this->weblogUserName,$this->weblogUserPass,$_struct,isset($struct['publish']) ? $struct['publish'] : false);
			try {
				$request = $this->_execRequest('metaWeblog.newPost', $params);
				$toReturn = $this->returnResult($request);
			}
			catch (Exception $e) {
				throw $e;
			}
			return $toReturn;
		}	
	}
	
	/**
	 * Edit post using remote server xmlrpc interface, referenced by postId
	 * 
	 * A post struct currently support a non-standard set of elements to better
	 * support modern interfaces such as wordpress one. Anyway, server should ignore
	 * elements not known.
	 * 
	 * @param	ARRAY	$struct		A post stuct
	 * @return	INT					Assigned post ID
	 */
	public function editPost($struct) {
		if (!$struct['postId']) throw new Exception('Invalid post id');
		else {
			if (isset($struct['categories'])) {
				$_categories = explode(",", $struct['categories']);
				$categories = Array();
				foreach($_categories as $category_name) {
					if ($category_name != "") array_push($categories,stripslashes(mb_convert_encoding($category_name, $this->encoding)));
				}
			}
			else $categories = Array();
			
			$_struct = Array(
				'title'				=>	stripslashes(mb_convert_encoding($struct['title'], $this->encoding)),
				'description'		=>	stripslashes(mb_convert_encoding($struct['description'], $this->encoding)),
				'mt_text_more'		=>	isset($struct['mt_text_more']) ? stripslashes(mb_convert_encoding($struct['mt_text_more'], $this->encoding)) : false,
				'post_type'			=>	'post',
				'mt_allow_comments'	=>	isset($struct['mt_allow_comments']) ? ($struct['mt_allow_comments'] ? 1 : 0) : 0,
        		'mt_allow_pings'	=>	isset($struct['mt_allow_pings']) ? ($struct['mt_allow_pings'] ? 1 : 0) : 0,
        		'sticky'			=>	isset($struct['sticky']) ? ($struct['sticky'] ? 1 : 0) : 0,
        		'categories'		=>	$categories,
        		'mt_excerpt'		=>	isset($struct['mt_excerpt']) ? stripslashes(mb_convert_encoding($struct['mt_excerpt'], $this->encoding)) : false,
        		'mt_keywords'		=>	isset($struct['mt_keywords']) ? stripslashes(mb_convert_encoding($struct['mt_keywords'], $this->encoding)) : false
			);
			$params = array($struct['postId'],$this->weblogUserName,$this->weblogUserPass,$struct,isset($struct['publish']) ? $struct['publish'] : false);
			try {
				$request = $this->_execRequest('metaWeblog.editPost', $params);
				$toReturn = $this->returnResult($request);
			}
			catch (Exception $e) {
				throw $e;
			}
			return $toReturn;
		}
	}
	
	/**
	 * upload a new media to weblog using metaWeblog.newMediaObject call
	 * 
	 * [...]
	 * 
	 * @param	ARRAY	$struct		A post stuct
	 * @return	INT					Assigned post ID
	 */
	public function uploadMedia($struct) {
		if (!$struct['name'] OR !$struct['path']) throw new Exception('Invalid file reference');
		else {
			try {
				comodojo_load_resource('fsLayer');
				$fs = new fsLayer();
				$fs->filePath = $struct['path'];
				$fs->fileName = $struct['name'];
				$mime = $fs->getMime();
				$bits = $fs->readFile(true);
				$params = array($this->weblogId,$this->weblogUserName,$this->weblogUserPass,Array("name"=>$struct['name'], "type"=>$mime, "bits"=>$bits));
				$request = $this->_execRequest('metaWeblog.newMediaObject', $params);
				$toReturn = $this->returnResult($request);
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		return $toReturn;
	}
	
/************************************************************/
	
	private function _execRequest($request, $params) {
		if ($this->_nativeRPC) {
			$request = xmlrpc_encode_request($request,$params,array('encoding',$this->encoding));
		}
		else {
			$encoder = new xmlRpcEncoder($request);
			$encoder->add_param("int", $params[0]);
			$encoder->add_param("string", $params[1]);
			$encoder->add_param("string", $params[2]);
			//if (isset($params[3])) $encoder->add_param("struct", $params[3]);
			if (isset($params[3])) {
				if (is_numeric($params[3])) $encoder->add_param("int", $params[3]);
				//elseif (is_array($params[3])) $encoder->add_param("struct", $params[3]);
				else $encoder->add_param("struct", $params[3]);
			}
			if (isset($params[4])) $encoder->add_param("bool", $params[4]);
			$request = $encoder->getData();
		}
		
		try {
			//$sender = new xmlRpcSender($this->weblogAddress, 80, $request);
			$sender = new http();
			$sender->address = $this->weblogAddress;
			$sender->port = 80;
			$sender->method = 'GET';
			$sender->encoding = $this->encoding;
			$sender->contentType = 'text/xml';
			$received = $sender->send($request);
		}
		catch (Exception $e) {
			comodojo_debug("Cannot init sender: ".$e->getMessage(),"ERROR","metaWeblogTalk");
			throw $e;
		}
		
		return $received;
		
	}
	
	private function returnResult($result) {
		
		$result = explode("<methodResponse>", $result);
		$result = "<methodResponse>".$result[1];
		
		if ($this->_nativeRPC) {
			$decoded = xmlrpc_decode($result);
		    if (is_array($decoded) && xmlrpc_is_fault($decoded)) {
		        throw new Exception($response['faultString'], $response['faultCode']);
		    }
		}
		else {
			$decoder = new xmlRpcDecoder();
			$decoded = $decoder->decode($result);
			if (is_numeric($decoded) AND @intval($decoded) == -1) {
				throw new Exception($decoder->getFault(), 1);
			}
		}
		return $decoded;
		
	}
	
}

function loadHelper_metaweblog() { return false; }

?>