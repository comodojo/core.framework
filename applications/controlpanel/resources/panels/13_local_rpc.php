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

function get_local_rpc_mode() {
	return Array(
		Array("name"=>"PlainText", "value"=>0),
		Array("name"=>"SharedKey", "value"=>1),
		Array("name"=>"PlainText + SharedKey", "value"=>2)
	);
}

function get_local_allowed_transport() {
	return Array(
		Array("name"=>"XML + JSON", "value"=>"XML,JSON"),
		Array("name"=>"XML", "value"=>"XML"),
		Array("name"=>"JSON", "value"=>"JSON")
	);
}
 
$panels = Array(
	"local_rpc" => Array(
		"builder"	=>	"form",
		"icon"		=>	"local_rpc.png",
		"label"		=>	"0220",
		"table"		=>	"options",
		"where"		=>	Array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),
		"include"	=>	Array("RPC_ENABLED","RPC_MODE","RPC_ALLOWED_TRANSPORT","RPC_KEY")
	)
);

$options = Array(
	"RPC_ENABLED"	=>	Array(
		"type"		=>	"OnOffSelect",
		"label"		=>	"0221",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	false
	),
	"RPC_MODE"		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0222",
		"required"	=>	true,
		"onclick"	=>	false,
		"options"	=>	get_local_rpc_mode()
	),
	"RPC_ALLOWED_TRANSPORT" 		=>	Array(
		"type"		=>	"Select",
		"label"		=>	"0223",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	get_local_allowed_transport()
	),
	"RPC_KEY"	=>	Array(
		"type"		=>	"TextBox",
		"label"		=>	"0224",
		"required"	=>	false,
		"onclick"	=>	false,
		"options"	=>	false
	)
);

?>