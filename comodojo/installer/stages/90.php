<?php

class stage extends stage_base {

	public $out = Array();

	public function check_php() {
		global $comodojoCustomization;
		return version_compare(PHP_VERSION, $comodojoCustomization["minPPHRequired"], '>');
	}

	public function check_gd() {
		return function_exists("gd_info");
	}

	public function check_home_folder_w() {
		$dir =  COMODOJO_SITE_PATH . $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['HOME_FOLDER'];
		return (is_readable($dir) AND is_writable($dir));
	}

	public function check_home_folder_e() {
		$dir =  COMODOJO_SITE_PATH . $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['HOME_FOLDER'];
		return (count(@scandir($dir)) == 2);
	}

	public function check_conf_folder_w() {
		$dir =  COMODOJO_SITE_PATH . $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CONFIGURATION_FOLDER'];
		return (is_readable($dir) AND is_writable($dir));
	}

	public function report_result($condition, $success, $failure, $block=false) {
		if (!$condition) {
			array_push($this->out, array("type"=>!$block ? 'warning' : 'error',"content"=>$failure));
			if ($block) $this->next_button_disabled = true;
		}
		else {
			array_push($this->out, array("type"=>'success',"content"=>$success));
		}
	}

	public function output() {

		$this->next_button_label = $this->i18n['0112'];

		$this->report_result($this->check_php(),$this->i18n["0101"],$this->i18n["0102"],true);
		$this->report_result($this->check_gd(),$this->i18n["0103"],$this->i18n["0104"],false);
		$this->report_result($this->check_home_folder_w(),$this->i18n["0107"],$this->i18n["0108"],true);
		$this->report_result($this->check_home_folder_e(),$this->i18n["0111"],$this->i18n["0125"],false);
		$this->report_result($this->check_conf_folder_w(),$this->i18n["0105"],$this->i18n["0106"],true);

		array_push($this->out,array(
			"type"			=>	"Button",
			"label"			=>	$this->i18n["0109"],
			"id"			=>	"installer_retryVerification",
			"onClick"		=>	"installer._retryVerification();",
			"disabled"		=>	false
			)
		);

		return $this->out;

	}			

}

?>