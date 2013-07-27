<?php

class stage extends stage_base {

	public function output() {
		
		global $comodojoCustomization;

		$this->back_button_disabled = true;

		$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values'] = $comodojoCustomization['defaultBaseValues'];

		return array(
			array(
				"type"		=>	"info",
				"content"	=>	"<p>Welcome to comodojo installer. Please select language first.</p>"
				),
			array(
				"type"		=>	"Select",
				"label"		=>	"Installation language:",
				"name"		=>	"installationLanguage",
				"value"		=>	"en",
				"options"	=>	array(
					array(
						"id"	=>	"it",
						"label"	=>	"Italiano"
						),
					array(
						"id"	=>	"en",
						"label"	=>	"English"
						)
					)
				)			
		);		

	}			

}

?>