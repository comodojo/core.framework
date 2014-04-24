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

comodojo_load_resource('rpc_client');

/**
 * Talk with a generic metaWeblog xmlrpc interface
 */
class metaweblog {
	
	/**
	 * Address of the xmlrpc server interface
	 * 
	 * @param	STRING
	 */
	private $weblogAddress = false;
	
	/**
	 * Username to send to remote server
	 * 
	 * @param	STRING
	 */
	private $weblogUserName = false;
	
	/**
	 * Password to send to remote server
	 * 
	 * @param	STRING
	 */
	private $weblogUserPass = false;

	private $weblogPort = 80;

	/**
	 * Weblog ID (leave it 0 if you're in single-blog mode)
	 * 
	 * @param	STRING
	 */
	private $weblogId = 0;
	
	/**
	 * Messages encoding (will be applied to - almost - every string!)
	 * 
	 * @param	STRING
	 */
	private $encoding = false;

/************************************************************/
	
	/**
	 * Class constructor
	 * 
	 * @param	STRING	$weblogAddress
	 * @param	STRING	$weblogUserName
	 * @param	STRING	$weblogUserName
	 */
	public function __construct($weblogAddress, $weblogUserName, $weblogUserPass, $port=80, $id=0, $encoding = COMODOJO_DEFAULT_ENCODING) {
		
		if (!$weblogAddress OR !$weblogUserName OR !$weblogUserPass) throw new Exception('Invalid parameters for weblog',3201);
		
		$this->weblogAddress = $weblogAddress;
		$this->weblogUserName = $weblogUserName;
		$this->weblogUserPass = $weblogUserPass;
		$this->weblogPort = filter_var($port, FILTER_VALIDATE_INT);
		$this->weblogId = filter_var($id, FILTER_VALIDATE_INT);
		$this->encoding = $encoding;

	}
	
	public function getUsersBlogs($appkey=false) {

		$params = array(
			$appkey,
			$this->weblogUserName,
			$this->weblogUserPass
		);

		try {

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.getUsersBlogs', $params, false);

		}
		catch (Exception $e) {
			throw $e;
		}

		return $response;

	}

	/**
	 * Get [$howmany] posts from blog
	 * 
	 * @param	INT|FALSE	$howmany	Number of post to ask for
	 * @return	ARRAY					Posts
	 */
	public function getRecentPosts($howmany=10) {

		$params = array(
			$this->weblogId,
			$this->weblogUserName,
			$this->weblogUserPass,
			filter_var($howmany, FILTER_VALIDATE_INT)
		);

		try {

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.getRecentPosts', $params, false);

		}
		catch (Exception $e) {
			throw $e;
		}

		return $response;

	}
	
	/**
	 * Retrieve a post from weblog
	 * 
	 * @param	INT|STRING	$postId	Post's ID
	 * @return	ARRAY				Post
	 */
	public function getPost($postId) {

		$params = array(
			$postId,
			$this->weblogUserName,
			$this->weblogUserPass
		);

		try {

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.getPost', $params, false);

		}
		catch (Exception $e) {
			throw $e;
		}

		return $response;

	}
	
	/**
	 * Retrieve a list of categories from weblog
	 * 
	 * @return	ARRAY	Categories
	 */
	public function getCategories() {

		$params = array(
			$this->weblogId,
			$this->weblogUserName,
			$this->weblogUserPass
		);

		try {

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.getCategories', $params, false);

		}
		catch (Exception $e) {
			throw $e;
		}

		return $response;

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
	public function newPost($struct, $publish=true, $type='post') {

		if ( !array_key_exists('title', $struct) OR !array_key_exists('description', $struct) OR empty($type) ) {
			throw new Exception('Invalid post struct',3202);
		}

		$categories = Array();

		if ( array_key_exists('categories', $struct) ) {
			foreach( explode(",", $struct['categories']) as $category) {
				if ( !empty($category) ) array_push( $categories, stripslashes( mb_convert_encoding($category, $this->encoding) ) );
			}
		}

		$post_structure = Array(
			'title'				=>	stripslashes( mb_convert_encoding( $struct['title'], $this->encoding ) ),
			'description'		=>	stripslashes( mb_convert_encoding( $struct['description'], $this->encoding ) ),
			'mt_text_more'		=>	isset($struct['mt_text_more']) ? stripslashes( mb_convert_encoding( $struct['mt_text_more'], $this->encoding ) ) : false,
			'post_type'			=>	$type,
			'mt_allow_comments'	=>	isset($struct['mt_allow_comments']) ? filter_var($struct['mt_allow_comments'], FILTER_VALIDATE_BOOLEAN) : 0,
			'mt_allow_pings'	=>	isset($struct['mt_allow_pings']) ? filter_var($struct['mt_allow_pings'], FILTER_VALIDATE_BOOLEAN) : 0,
			'sticky'			=>	isset($struct['sticky']) ? filter_var($struct['sticky'], FILTER_VALIDATE_BOOLEAN) : 0,
			'categories'		=>	$categories,
			'mt_excerpt'		=>	isset($struct['mt_excerpt']) ? stripslashes( mb_convert_encoding($struct['mt_excerpt'], $this->encoding ) ) : false,
			'mt_keywords'		=>	isset($struct['mt_keywords']) ? stripslashes( mb_convert_encoding($struct['mt_keywords'], $this->encoding ) ) : false
		);

		$params = array(
			$this->weblogId,
			$this->weblogUserName,
			$this->weblogUserPass,
			$post_structure,
			filter_var($publish, FILTER_VALIDATE_BOOLEAN)
		);

		try {

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.newPost', $params, false);

		}
		catch (Exception $e) {
			throw $e;
		}

		return $response;

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
	public function editPost($postId, $struct, $publish=true, $type=false) {

		if ( !filter_var($postId, FILTER_VALIDATE_INT) OR !is_array($struct) OR empty($type) ) {
			throw new Exception('Invalid post struct',3202);
		}

		$categories = false;

		if ( array_key_exists('categories', $struct) ) {
			$categories = Array();
			foreach( explode(",", $struct['categories']) as $category) {
				if ( !empty($category) ) array_push( $categories, stripslashes( mb_convert_encoding($category, $this->encoding) ) );
			}
		}

		$post_structure = Array();

		if ( array_key_exists('title', $struct) ) $post_structure["title"] = stripslashes(mb_convert_encoding($struct['title'], $this->encoding));
		if ( array_key_exists('description', $struct) ) $post_structure["description"] = stripslashes(mb_convert_encoding($struct['description'], $this->encoding));
		if ( array_key_exists('mt_text_more', $struct) ) $post_structure["mt_text_more"] = stripslashes(mb_convert_encoding($struct['mt_text_more'], $this->encoding));
		if ( array_key_exists('mt_allow_comments', $struct) ) $post_structure["mt_allow_comments"] = filter_var($struct['mt_allow_comments'], FILTER_VALIDATE_BOOLEAN);
		if ( array_key_exists('mt_allow_pings', $struct) ) $post_structure["mt_allow_pings"] = filter_var($struct['mt_allow_pings'], FILTER_VALIDATE_BOOLEAN);
		if ( array_key_exists('sticky', $struct) ) $post_structure["sticky"] = filter_var($struct['sticky'], FILTER_VALIDATE_BOOLEAN);
		if ( array_key_exists('mt_excerpt', $struct) ) $post_structure["mt_excerpt"] = stripslashes(mb_convert_encoding($struct['mt_excerpt'], $this->encoding));
		if ( array_key_exists('mt_keywords', $struct) ) $post_structure["mt_keywords"] = stripslashes(mb_convert_encoding($struct['mt_keywords'], $this->encoding));
		if ( $type !== false ) $post_structure["post_type"] = $type;
		if ( $categories !== false ) $post_structure["categories"] = $categories;
		
		$params = array(
			$postId,
			$this->weblogUserName,
			$this->weblogUserPass,
			$post_structure,
			filter_var($publish, FILTER_VALIDATE_BOOLEAN)
		);

		try {

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.editPost', $params, false);

		}
		catch (Exception $e) {
			throw $e;
		}

		return $response;

	}
	
	public function deletePost($postId, $appkey=false) {

		if ( !filter_var($postId, FILTER_VALIDATE_INT) ) {
			throw new Exception('Invalid post struct',3202);
		}

		$params = array(
			$appkey,
			$postId,
			$this->weblogUserName,
			$this->weblogUserPass,
			false
		);

		try {

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.deletePost', $params, false);

		}
		catch (Exception $e) {
			throw $e;
		}

		return $response;

	}

	/**
	 * upload a new media to weblog using metaWeblog.newMediaObject call
	 * 
	 * [...]
	 * 
	 * @param	ARRAY	$struct		A post stuct
	 * @return	INT					Assigned post ID
	 */
	public function newMediaObject($name, $file, $overwrite=false) {

		comodojo_load_resource('filesystem');

		if ( $empty($name) OR $empty($file) ) {
			throw new Exception('Invalid file reference',3203);
		}

		try {
			
			$fs = new filesystem();
			$info = $fs->getInfo($file);
			$mime = $info["mimetype"]:
			$bits = $fs->readFile($file, true);

			$params = Array(
				$this->weblogId,
				$this->weblogUserName,
				$this->weblogUserPass,
				Array(
					"name" => $name,
					"type" => $mime,
					"bits" => $bits,
					"overwrite" => filter_var($overwrite, FILTER_VALIDATE_BOOLEAN)
				)
			);

			$handler = new rpc_client($this->weblogAddress, 'XML', NULL, $this->weblogPort);
			$response = $handler->send('metaWeblog.newMediaObject', $params, false);

		} catch (Exception $e) {
			throw $e;
		}

		return $response;
		
	}
		
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_metaweblog
 */
function loadHelper_metaweblog() { return false; }

?>