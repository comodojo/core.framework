<?php

/**
 * chpasswd.js
 *
 * Update or reset passwords in Comodojo mode
 *
 * @package		Comodojo Applications
 * @author		comodojo.org
 * @copyright	2010 comodojo.org (info@comodojo.org)
 */

@session_start();

class testMetaWeblog {
	
	protected $kernelRequiredParameters = Array(
		"get_posts"			=>	Array("blog_url","blog_user","blog_pass"),
		"get_post"			=>	Array("blog_url","blog_user","blog_pass","postId"),
		"edit_post"			=>	Array("blog_url","blog_user","blog_pass","postId"),
		"get_categories"	=>	Array("blog_url","blog_user","blog_pass"),
		"write_post"		=>	Array("blog_url","blog_user","blog_pass","title","description")
	);
	
	protected function doCall($selector, $params) {
		
		switch ($selector) {
			case "get_posts":
				$toReturn = $this->getPosts($params);
			break;
			
			case "get_post":
				$toReturn = $this->getPost($params);
			break;

			case "get_categories":
				$toReturn = $this->getCategories($params);
			break;
			
			case "write_post":
				$toReturn = $this->writePost($params);
			break;
			
			case "edit_post":
				$toReturn = $this->editPost($params);
			break;
			
			default:
				$this->success = false;
				$toReturn = false;
			break;
		}
		return $toReturn;
		
	}
	
	protected function doDatastoreCall($selector) {
		$result = false;
		return $result;
	}
	
	public function getPosts($params) {
		
		if (!function_exists("loadHelper_metaWeblogTalk")) {
			require($_SESSION[SITE_UNIQUE_IDENTIFIER]["sitePath"] . "comodojo/abstractionLayers/metaWeblogTalk.php");
		}
		
		try {
			$mwt = new metaWeblogTalk($params['blog_url'], $params['blog_user'], $params['blog_pass']);
			$toReturn = $mwt->getPosts(isset($params['howmany']) ? $params['howmany'] : false);
		}
		catch (Exception $e) {
			$this->success = false;
			return $e->getMessage();
		}
		$this->success = true;
		return $toReturn;
		
	}
	
	public function getPost($params) {
		
		if (!function_exists("loadHelper_metaWeblogTalk")) {
			require($_SESSION[SITE_UNIQUE_IDENTIFIER]["sitePath"] . "comodojo/abstractionLayers/metaWeblogTalk.php");
		}
		
		try {
			$mwt = new metaWeblogTalk($params['blog_url'], $params['blog_user'], $params['blog_pass']);
			$toReturn = $mwt->getPost($params['postId']);
		}
		catch (Exception $e) {
			$this->success = false;
			return $e->getMessage();
		}
		$this->success = true;
		return $toReturn;
		
	}
	
	public function getCategories($params) {
		
		if (!function_exists("loadHelper_metaWeblogTalk")) {
			require($_SESSION[SITE_UNIQUE_IDENTIFIER]["sitePath"] . "comodojo/abstractionLayers/metaWeblogTalk.php");
		}
		
		try {
			$mwt = new metaWeblogTalk($params['blog_url'], $params['blog_user'], $params['blog_pass']);
			$toReturn = $mwt->getCategories();
		}
		catch (Exception $e) {
			$this->success = false;
			return $e->getMessage();
		}
		$this->success = true;
		return $toReturn;
		
	}
	
	public function writePost($params) {
		
		if (!function_exists("loadHelper_metaWeblogTalk")) {
			require($_SESSION[SITE_UNIQUE_IDENTIFIER]["sitePath"] . "comodojo/abstractionLayers/metaWeblogTalk.php");
		}
		
		try {
			$mwt = new metaWeblogTalk($params['blog_url'], $params['blog_user'], $params['blog_pass']);
			$toReturn = $mwt->writePost($params);
		}
		catch (Exception $e) {
			$this->success = false;
			return $e->getMessage();
		}
		$this->success = true;
		return $toReturn;
		
	}
	
	public function editPost($params) {
		
		if (!function_exists("loadHelper_metaWeblogTalk")) {
			require($_SESSION[SITE_UNIQUE_IDENTIFIER]["sitePath"] . "comodojo/abstractionLayers/metaWeblogTalk.php");
		}
		
		try {
			$mwt = new metaWeblogTalk($params['blog_url'], $params['blog_user'], $params['blog_pass']);
			$toReturn = $mwt->editPost($params);
		}
		catch (Exception $e) {
			$this->success = false;
			return $e->getMessage();
		}
		//$cats = false;
		//foreach($toReturn['categories'] as $cat) {
		//	if (!$cats) $cats = $cat;
		//	else $cats .= ",".$cat;
		//}
		//$toReturn['categories'] = $cats;
		
		$this->success = true;
		return $toReturn;
		
	} 
	
}

?>