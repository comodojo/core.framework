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

class license extends application {
	
	public function init() {
		$this->add_application_method('get_info', 'getInfo', Array(), 'Comodojo license',false);
	}
	
	public function getInfo($params) {
		$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/others/license.html");
		return $index;
	}
	
}

?>
