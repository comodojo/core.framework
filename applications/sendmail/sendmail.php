<?php

/**
 * Send mail
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class sendmail extends application {
	
	public function init() {
		$this->add_application_method('send', 'Send', Array(), 'Send mail',false);
	}

	public function Send($params) {
		
	}
	
}

?>