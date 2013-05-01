<?php

/**
 * stage.php
 * 
 * base stage class; each stage SHOULD extend this class;
 * 
 * @package		Comodojo Installer
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class stage_base {

	public  $back_button_disabled = false;
	public  $back_button_label = "<<";
	private $back = null;

	public  $next_button_disabled = false;
	public  $next_button_label = ">>";
	private $next = null;

	private $current = null;

	public $i18n = Array();
		
	public final function __construct($back,$next,$current,$values) {

		$this->back = $back;
		$this->next = $next;
		$this->current = $current;

		if (sizeof($values) != 0) $this->update_values($values);

		if (isset($_SESSION[SITE_UNIQUE_IDENTIFIER]['installationLanguage'])){
			require(COMODOJO_SITE_PATH."comodojo/installer/i18n/i18n_installer_".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installationLanguage'].".php");
			$this->i18n = $i18n_installer;
		}

	}

	public final function dispatch() {

		try {
			$out = $this->output();
			$to_return = Array(
				"success"				=>	true,
				"backButtonDisabled"	=>	$this->back_button_disabled,
				"backButtonLabel"		=>	$this->back_button_label,
				"backButtonStage"		=>	$this->back,
				"nextButtonDisabled"	=>	$this->next_button_disabled,
				"nextButtonLabel"		=>	$this->next_button_label,
				"nextButtonStage"		=>	$this->next,
				"progressBarProgress"	=>	$this->current,
				"formComponents"		=>	$out
			);
		}
		catch (Exception $e) {
			$to_return = Array(
				"success"	=>	false,
				"result"	=>	$e->getMessage(),
				"code"		=>	$e->getCode()
			);
		}

		return $to_return;

	}

	public final function update_values($values) {

		foreach ($values as $key=>$value) {
		
			if ($key == "installationLanguage" OR $key == "switchAdvanced") {
				$_SESSION[SITE_UNIQUE_IDENTIFIER][$key] = $value;
			}
			elseif ($key == "dojo.preventCache" OR $key == "dojo_preventCache") {
				continue;
			}
			else {
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values'][$key] = $value == "false" ? 0 : $value;
			}
		
		}
	
	}

	public function output() {
		return false;
	}

}