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
		$this->add_application_method('send', 'Send', Array("to","subject","message"), 'Send mail',false);
	}

	public function Send($params) {
		
		comodojo_load_resource("mail");

		try {

			$mail = new mail(isset($params["from"]) ? $params["from"] : false);
			$mail->to($params["to"])
				 ->subject($params["subject"])
				 ->embed(COMODOJO_SITE_PATH."comodojo/images/logo.png","COMODOJO_LOGO","logo");

			if (isset($params["cc"])) $mail->cc($params["cc"]);
			if (isset($params["bcc"])) $mail->bcc($params["bcc"]);
			if (isset($params["priority"])) $mail->priority(filter_var($params["priority"],FILTER_VALIDATE_INT));
			if (isset($params["format"])) $mail->format($params["format"]);

			$mail->send();
			
		}
		catch (Exception $e) {
			throw $e;			
		}

	}
	
}

?>