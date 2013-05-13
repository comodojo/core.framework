<?php

/**
 * Default comodojo framework menubar
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class comodojo_menubar extends application {
	
	public function init() {
		$this->add_application_method('get_user_info', 'getUserInfo', Array(), 'menubar.get_user_info() returns logged in user basic information; no extra parameter is required',false);
	}
	
	public function getUserInfo() {
		
		comodojo_load_resource('user_avatar');
		return Array(
			"id"			=>	COMODOJO_USER_ID,
			"role"			=>	COMODOJO_USER_ROLE,
			"completeName"	=>	COMODOJO_USER_COMPLETE_NAME,
			"avatar"		=>	get_current_user_avatar(),
			"email"			=>	COMODOJO_USER_EMAIL,
			//"userRole"	=>	COMODOJO_USER_BIRTHDAY,
			//"userRole"	=>	COMODOJO_USER_GENDER,
			"url"			=>	COMODOJO_USER_URL
		);
		
	}	
}

?>