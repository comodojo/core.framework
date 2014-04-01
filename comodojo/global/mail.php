<?php

/** 
 * mail.php
 * 
 * talk with smtp external server, send mails ad do some other stuff
 *
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class mail {

/*********************** PRIVATE VARS **********************/
	/**
	 * The template for HTML mail.
	 * Templates are located at '/templates/' directory in comodojo folder.
	 * @var	string
	 */
	private $html_template = 'mail_html.html';

	/**
	 * The template for HTML mail.
	 * Templates are located at '/templates/' directory in comodojo folder.
	 * @var	string
	 */
	private $send_mail_as = 'HTML';	

	/**
	 * The PHPMailer debug level
	 * @var	integer
	 * 
	 * PLEASE NOTE: changing this value (!=0) will result in debug information echoed on output
	 */
	private $debug_level = 0;
	
	/**
	 * Recipient to
	 * @var	string|array
	 */
	private $to = Array();
	
	/**
	 * Recipient in carbon copy
	 * @var	string|array
	 */
	private $cc = Array();
	
	/**
	 * Recipient in blind carbon copy
	 * @var	string|array
	 */
	private $bcc = Array();
	
	/**
	 * Email to reply to
	 * @var	string	contains a valid address to reply to
	 */
	private $reply_to = null;
	
	/**
	 * Mail priority (1 = High, 3 = Normal, 5 = low)
	 * @var	integer
	 */
	private $priority = 3;
	
	/**
	 * Subject
	 * @var	string
	 */
	private $subject = "";
	
	/**
	 * Mail content
	 * @var	string
	 */
	private $message = "";
	
	/**
	 * Extra tags to replace in html mail template
	 * @var	bool|array
	 */
	private $extra_tags = false;

	/**
	 * Array of object to embed
	 */
	private $embed = Array();

	/**
	 * Array of object to attach
	 */
	private $attach = Array();
/*********************** PRIVATE VARS *********************/


/********************* PUBLIC METHODS *********************/	
	
	public function to($address_or_array) {

		if (empty($address_or_array)) {
			comodojo_debug('Invalid RCPT TO','ERROR','mail');
			throw new Exception('Invalid RCPT TO',1702);
		}
		elseif (is_array($address_or_array)) {
			$this->to = array_merge($this->to,$address_or_array);
		}
		elseif (strpos($address_or_array, ',') !== false OR strpos($address_or_array, ';') !== false) {
			$this->to = array_merge($this->to,preg_split("/[;,]+/",preg_replace('/\s+/','',$address_or_array)));
		}
		else {
			array_push($this->to,$address_or_array);
		}

		return $this;

	}

	public function cc($address_or_array) {

		if (empty($address_or_array)) {
			comodojo_debug('Invalid CC','ERROR','mail');
			throw new Exception('Invalid CC',1703);
		}
		elseif (is_array($address_or_array)) {
			$this->cc = array_merge($this->cc,$address_or_array);
		}
		elseif (strpos($address_or_array, ',') !== false OR strpos($address_or_array, ';') !== false) {
			$this->cc = array_merge($this->cc,preg_split("/[;,]+/",preg_replace('/\s+/','',$address_or_array)));
		}
		else {
			array_push($this->cc,$address_or_array);
		}

		return $this;

	}

	public function bcc($address_or_array) {

		if (empty($address_or_array)) {
			comodojo_debug('Invalid BCC','ERROR','mail');
			throw new Exception('Invalid BCC',1704);
		}
		elseif (is_array($address_or_array)) {
			$this->bcc = array_merge($this->bcc,$address_or_array);
		}
		elseif (strpos($address_or_array, ',') !== false OR strpos($address_or_array, ';') !== false) {
			$this->bcc = array_merge($this->bcc,preg_split("/[;,]+/",preg_replace('/\s+/','',$address_or_array)));
		}
		else {
			array_push($this->bcc,$address_or_array);
		}

		return $this;

	}

	public function template($template_name) {

		if (empty($template_name) OR !realFileExists(COMODOJO_SITE_PATH."comodojo/templates/".$template_name)) {
			comodojo_debug('Invalid template','ERROR','mail');
			throw new Exception('Invalid template',1705);
		}
		else {
			$this->html_template = $template_name;
		}

		return $this;

	}

	public function format($as='HTML') {

		$this->send_mail_as = $as == 'HTML' ? 'HTML' : 'PLAIN';

		return $this;

	}

	public function reply_to($address) {

		if (empty($address) OR !is_string($address)) {
			comodojo_debug('Invalid REPLY TO','ERROR','mail');
			throw new Exception('Invalid REPLY TO',1705);
		}
		else {
			$this->reply_to = $address;
		}

		return $this;

	}

	public function priority($priority=3) {

		if (empty($priority) OR !in_array($priority, Array(1,3,5))) {
			comodojo_debug('Invalid mail priority','ERROR','mail');
			throw new Exception('Invalid mail priority',1706);
		}
		else {
			$this->priority = $priority;
		}

		return $this;

	}

	public function add_tag($tag,$replace) {

		if (empty($tag) OR empty($replace) OR !is_string($tag) OR !is_scalar($replace)) {
			comodojo_debug('Invalid tag to replace','ERROR','mail');
			comodojo_debug($tag.'::'.$replace,'ERROR','mail');
			throw new Exception('Invalid tag to replace',1708);
		}
		else {
			$this->extra_tags[$tag] = $replace;
		}

		return $this;
		
	}

	public function subject($text) {

		if (empty($text) OR !is_string($text)) $this->subject = "";
		else $this->subject = $text;

		return $this;

	}

	public function message($text) {

		if (empty($text) OR !is_string($text)) $this->message = "";
		else $this->message = $text;

		return $this;

	}

	public function embed($file_name, $cid, $name) {

		if (empty($file_name) OR empty($cid) OR empty($name)) {
			comodojo_debug('Invalid object to embed','ERROR','mail');
			throw new Exception('Invalid object to embed',1709);
		}
		else {
			array_push($this->embed, Array("filename"=>$file_name,"cid"=>$cid,"name"=>$name));
		}

		return $this;

	}

	public function attach($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream') {

		if (empty($path)) {
			comodojo_debug('Invalid file to attach','ERROR','mail');
			throw new Exception('Invalid file to attach',1710);
		}
		else {
			array_push($this->attach, Array("path"=>$path,"name"=>$name,"encoding"=>$encoding,"type"=>$type));
		}

		return $this;

	}

	public function send() {

		if (empty($this->to) AND empty($this->cc)) {
			comodojo_debug('No RCPT TO or CC specified','ERROR','mail');
			throw new Exception('No RCPT TO or CC specified',1707);
		}

		$this->mail->Priority = $this->priority;
		$this->mail->Subject = $this->subject;

		if ($this->send_mail_as=='HTML') {
			$this->mail->IsHTML(true);
			$body = file_get_contents(COMODOJO_SITE_PATH."comodojo/templates/".$this->html_template);
			$body = str_replace("*_MESSAGE_*",$this->message,$body);
			$body = str_replace("*_SUBJECT_*",$this->subject,$body);
			$body = str_replace("*_SITEURL_*",COMODOJO_SITE_URL,$body);
			$body = str_replace("*_SITETITLE_*",COMODOJO_SITE_TITLE,$body);
			$body = str_replace("*_SITEAUTHOR_*",COMODOJO_SITE_AUTHOR,$body);
			$body = str_replace("*_SITEDATE_*",COMODOJO_SITE_DATE,$body);
			foreach ($this->extra_tags as $tag => $replace) {
				$body = str_replace($tag,$replace,$body);
			}
			$this->mail->Body = stripcslashes($body);
		}
		else {
			$this->mail->Body = $this->message;
		}

		try {
			foreach($this->to as $to) { $this->mail->AddAddress($to); }
			foreach($this->cc as $cc) { $this->mail->AddCC($cc); }
			foreach($this->bcc as $bcc) { $this->mail->AddBCC($bcc); }
			if (!empty($this->reply_to)) $this->mail->AddReplyTo($this->reply_to);
			foreach($this->embed as $embed) { $this->mail->AddEmbeddedImage($embed['filename'],$embed['cid'],$embed['name']); }
			foreach($this->attach as $attach) { $this->mail->AddAttachment($attach['path'],$attach['name'],$attach['encoding'],$attach['type']); }
			$this->mail->Send();
		}
		catch (phpmailerException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
		
		return true;

	}

	/**
	 * Constructor
	 * 
	 * @param	string	$address	Force different "address from"
	 * @param	string	$encoding	Force different encoding
	 * @param	integer	$debugLevel	Force different debug level (use with caution)
	 */
	public function __construct($address=false, $encoding=false, $debugLevel=false) {
		
		if (!COMODOJO_SMTP_SERVER) throw new Exception("Invalid mail server or mail disabled", 1701);
		
		comodojo_load_resource('class.phpmailer');
		
		$this->mail = new PHPMailer(true);

		switch (COMODOJO_SMTP_SERVICE) {
			case 'smtp':
				$this->mail->IsSMTP();
			break;
			case 'mail':
				$this->mail->IsMail();
			break;
			case 'sendmail':
				$this->mail->IsSendmail();
			break;
			default:
				$this->mail->IsSMTP();
			break;
		}
		
		$this->mail->CharSet = !$encoding ? COMODOJO_DEFAULT_ENCODING : $encoding;
		$this->mail->SMTPDebug = !$debugLevel ? $this->debug_level : $debugLevel;
		
		$this->mail->Host = COMODOJO_SMTP_SERVER;
		$this->mail->Port = COMODOJO_SMTP_PORT;
		
		$this->mail->SMTPAuth   = COMODOJO_SMTP_AUTHENTICATED;
		
		$this->mail->Username   = COMODOJO_SMTP_USER;
		$this->mail->Password   = COMODOJO_SMTP_PASSWORD;

		switch (COMODOJO_SMTP_SECURITY) {
			case 'ssl':
				$this->mail->SMTPSecure = 'ssl';
			break;
			case 'tls':
				$this->mail->SMTPSecure = 'tls';
			break;
			default:
				$this->mail->SMTPSecure = '';
			break;
		}
		
		
		$default_from_address = is_null(COMODOJO_SMTP_ADDRESS) ? 'comodojo@localhost' : COMODOJO_SMTP_ADDRESS;

		$_address = $address == false ? $default_from_address : $address;

		$this->mail->SetFrom($_address);
		
	}
/********************* PUBLIC METHODS *********************/

}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_mail
 */
function loadHelper_mail() { return false; }

?>