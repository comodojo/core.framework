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

class testMailSend {
	
	protected $kernelRequiredParameters = Array(
		"send_mail"	=>	Array("from","to","cc","bcc","subject","priority","message","isHtmlMail")
	);
	
	protected function doCall($selector, $params) {
		
		switch ($selector) {
			case "check_engine":
				$toReturn = $this->checkEngine();
			break;
			
			case "send_mail":
				$toReturn = $this->sendMail($params);
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
	
	public function checkEngine() {
		
		$this->success = true;
		
		return (!$_SESSION[SITE_UNIQUE_IDENTIFIER]["smtpServer"] OR $_SESSION[SITE_UNIQUE_IDENTIFIER]["smtpServer"] == "" OR !$_SESSION[SITE_UNIQUE_IDENTIFIER]["smtpAddress"] OR $_SESSION[SITE_UNIQUE_IDENTIFIER]["smtpAddress"] == "") ? false : true;
		
	}
	
	public function sendMail($params) {
		
		if (!function_exists("loadHelper_mail")) {
			require($_SESSION[SITE_UNIQUE_IDENTIFIER]["sitePath"] . "comodojo/abstractionLayers/smtpTalk.php");
		}
		
		$mail = new mail;
		
		$mail->to = $params['to'];
		$mail->cc = $params['cc'];
		$mail->bcc = $params['bcc'];
		$mail->replyTo = $params['from'];
		$mail->priority = $params['priority'];
		$mail->subject = $params['subject'];
		$mail->message = $params['message'];
		
		$result = $params['isHtmlMail'] ? $mail->sendHtmlMail() : $mail->sendMail();
		
		$this->success = $result['success'];
		return $result['result'];
		
	}
	
}

?>