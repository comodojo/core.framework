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
		Array("label"=>"SMTP","id"=>'smtp'),
		Array("label"=>"mail","id"=>'mail'),
		Array("label"=>"SendMail","id"=>'sendmail')
	);
}

function get_smtp_security() {
	return Array(
		Array("label"=>"No","id"=>'off'),
		Array("label"=>"ssl","id"=>'ssl'),
		Array("label"=>"tls","id"=>'tls')
	);
} 

$panels = Array(
	"smtp" => Array(
		"builder"	=>	"form",
		"icon"		=>	"smtp.png",
		"label"		=>	"smt_0",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("SMTP_SERVER","SMTP_PORT","SMTP_SERVICE","SMTP_AUTHENTICATED","SMTP_SECURITY","SMTP_USER","SMTP_PASSWORD","SMTP_ADDRESS")
	)
);

$options = Array(
	"SMTP_SERVER"		=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"smt_1",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_PORT"		=>	Array(
		"type"		=>	"NumberTextBox",
		"label"		=>	"smt_2",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false,
		"min"		=>	1,
		"max"		=>	65535
	),
	"SMTP_SERVICE"=>	Array(
		"type"		=>	"Select",
		"label"		=>	"smt_3",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	get_smtp_service()
	),
	"SMTP_AUTHENTICATED"=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"smt_4",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_SECURITY"=>	Array(
		"type"		=>	"Select",
		"label"		=>	"smt_5",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_smtp_security()
	),
	"SMTP_USER"=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"smt_6",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_PASSWORD"=>	Array(
		"type"		=>	"PasswordTextBox",
		"label"		=>	"smt_7",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"SMTP_ADDRESS"=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"smt_8",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>