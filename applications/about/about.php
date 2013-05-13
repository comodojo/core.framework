<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class about extends application {
	
	public function init() {
		$this->add_application_method('get_info', 'getInfo', Array(), 'About comodojo',true);
	}
	
	public function getInfo($params) {
		$info = explode("\n",comodojo_version());
		$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/others/about.html");
		$index = str_replace("*_ABOUT_PRODUCT_*",$info[0],$index);
		$index = str_replace("*_ABOUT_VERSION_*",$info[1],$index);
		$index = str_replace("*_ABOUT_BUILD_*",$info[2],$index);
		return $index;
	}
	
}

?>
