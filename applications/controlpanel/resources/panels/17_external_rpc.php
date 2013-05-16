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

function get_external_rpc_mode() {
	return Array(
		Array("name"=>"PlainText", "value"=>0),
		Array("name"=>"SharedKey", "value"=>1)
	);
}

function get_external_rpc_transport() {
	return Array(
		Array("name"=>"XML", "value"=>"XML"),
		Array("name"=>"JSON", "value"=>"JSON")
	);
}
 
$panels = Array(
	"external_rpc" => Array(
		"builder"	=>	"form",
		"icon"		=>	"external_rpc.png",
		"label"		=>	"0250",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("EXTERNAL_RPC_SERVER","EXTERNAL_RPC_PORT","EXTERNAL_RPC_MODE","EXTERNAL_RPC_TRANSPORT","EXTERNAL_RPC_KEY")
	)
);

$options = Array(
	"EXTERNAL_RPC_SERVER"	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0251",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"EXTERNAL_RPC_PORT"		=>	Array(
		"type"		=>	"NumberTextBox",
		"label"		=>	"0252",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false,
		"min"		=>	1,
		"max"		=>	65535
	),
	"EXTERNAL_RPC_MODE"		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0253",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_external_rpc_mode()
	),
	"EXTERNAL_RPC_TRANSPORT" 		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0254",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	get_external_rpc_transport()
	),
	"EXTERNAL_RPC_KEY"	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0255",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>