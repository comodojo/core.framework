<?php

/**
 * Manage files' acl
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class chmod extends application {
	
	public function init() {
		$this->add_application_method('listUsers', 'list_users', Array(), 'List available users, wildcards included.',false);
		$this->add_application_method('getResourceAcl', 'get_resource_acl', Array('filePath','fileName'), 'Get resource acl; filePath and fileName are required to identify resource.',false);
		$this->add_application_method('setResourceAcl', 'set_resource_acl', Array('filePath','fileName','owners','readers','writers'), 'Set resource acl; filePath and fileName are required to identify resource, owners, readers and writers to define acl.',false);
	}
	
	public function list_users() {
			
		comodojo_load_resource('users_management');
		$um = new users_management();
		try {
			$result = $um->get_users(16, true, true);
		}
		catch (Exception $e){
			throw $e;
		}
		
		array_push($result,array(
			"userName"		=>	"everybody",
			"completeName"	=>	"everybody",
			"userImage"		=>	getSiteUrl() . 'comodojo/icons/64x64/logo.png'
		));
		
		array_push($result,array(
			"userName"		=>	"nobody",
			"completeName"	=>	"nobody",
			"userImage"		=>	getSiteUrl() . 'comodojo/icons/64x64/logo.png'
		));
		
		return $result;
		
	}
	
	public function get_resource_acl($params) {
		
		comodojo_load_resource('filesystem');
		$fs = new filesystem();
		
		try {
			$fs->filePath = $params['filePath'];
			$fs->fileName = $params['fileName'];
			$result = $fs->getPermissions();
			$to_return = Array();
			$acl_id = 1;
			foreach ($result['readers'] as $reader) { array_push($to_return,Array("acl_id"=> $acl_id, "userName"=>$reader,"role"=>'reader')); $acl_id++;}
			foreach ($result['writers'] as $writer) { array_push($to_return,Array("acl_id"=> $acl_id, "userName"=>$writer,"role"=>'writer')); $acl_id++;}
			foreach ($result['owners'] as $owner) { array_push($to_return,Array("acl_id"=> $acl_id, "userName"=>$owner,"role"=>'owner')); $acl_id++;}
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $to_return;
		
	}
	
	public function set_resource_acl($params) {
		
		comodojo_load_resource('filesystem');
		$fs = new filesystem();
		
		try {
			$fs->filePath = $params['filePath'];
			$fs->fileName = $params['fileName'];
			$fs->owners = json2array(stripslashes($params['owners']));
			$fs->readers = json2array(stripslashes($params['readers']));
			$fs->writers = json2array(stripslashes($params['writers']));
			$result = $fs->setPermissions();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result;
		
	}
	
}

?>