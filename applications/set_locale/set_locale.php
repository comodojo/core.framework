<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class set_locale extends application {
	
	public function init() {
		$this->add_application_method('get_locale_status', 'getLocaleStatus', Array(), 'No description available, sorry.',false);
	}
	
	public function getLocaleStatus() {
		
		return Array(
			"supportedLocales"	=>	getSupportedLocales(),
			"currentLocale"		=>	COMODOJO_CURRENT_LOCALE
		);
		
	}
	
}

?>
