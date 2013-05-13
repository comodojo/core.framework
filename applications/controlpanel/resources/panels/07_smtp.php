<?php

/** 
 * controlpanel panel definition
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

function get_smtp_service() {
	return Array(
		Array("name"=>"SMTP","value"=>0),
		Array("name"=>"mail","value"=>1),
		Array("name"=>"SendMail","value"=>2)
	);
}

function get_smtp_security() {
	return Array(
		Array("name"=>"No","value"=>0),
		Array("name"=>"ssl","value"=>1),
		Array("name"=>"tls","value"=>2)
	);
} 

$panels = Array(
	"smtp" => Array(
		"builder"	=>	"form",
		"icon"		=>	"smtp.png",
		"label"		=>	"0160",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SMTP_SERVER","SMTP_PORT","SMTP_SERVICE","SMTP_AUTHENTICATED","SMTP_SECURITY","SMTP_USER","SMTP_PASSWORD","SMTP_ADDRESS")
	)
);

$options = Array(
	"SMTP_SERVER"		=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0161",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_PORT"		=>	Array(
		"type"		=>	"NumberTextBox",
		"label"		=>	"0162",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false,
		"min"		=>	1,
		"max"		=>	65535
	),
	"SMTP_SERVICE"=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0163",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	get_smtp_service()
	),
	"SMTP_AUTHENTICATED"=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0164",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_SECURITY"=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0165",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_smtp_security()
	),
	"SMTP_USER"=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0166",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_PASSWORD"=>	Array(
		"type"		=>	"PasswordTextBox",
		"label"		=>	"0167",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_ADDRESS"=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0168",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>