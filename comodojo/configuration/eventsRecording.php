<?php

/** 
 * eventsRecording.php
 * 
 * This file enable or disable recording of events.
 *
 * To enable or disable events recording change the EVENTS_ENABLED variable
 * in _options table.
 *
 * The "type" object is reserved for future use.
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.orgs
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$_recordUnknownEvents = true;
$_recordUnknownEventsAs = "unknown";

$_events = array(
	//GENERAL
	"site_hit"	 								=> array("enabled"=>true,"type"=>"info"),
	//AUTHENTICATION	
	"user_login"								=> array("enabled"=>true,"type"=>"info"),
	"user_logout"								=> array("enabled"=>true,"type"=>"info"),
	"user_chpasswd"								=> array("enabled"=>true,"type"=>"info"),
	"user_rstpasswd"							=> array("enabled"=>true,"type"=>"info"),
	"user_enable"								=> array("enabled"=>true,"type"=>"info"),
	"user_disable"								=> array("enabled"=>true,"type"=>"info"),
	//SERVICES
	"service_request"							=> array("enabled"=>true,"type"=>"info"),
	//KEYCHAINS
	"keychain_get_account"						=> array("enabled"=>true,"type"=>"info"),
	"keychain_set_account"						=> array("enabled"=>true,"type"=>"info"),
	"keychain_use_system_account"				=> array("enabled"=>true,"type"=>"info"),
	"keychain_delete_account"					=> array("enabled"=>true,"type"=>"info"),
	//fileDownload
	"download_file"								=> array("enabled"=>true,"type"=>"info"),
	//controlpanel
	"configuration_change"						=> array("enabled"=>true,"type"=>"info"),
	//REGISTRATION
	"user_registered"							=> array("enabled"=>true,"type"=>"info"),
	"user_authorized"							=> array("enabled"=>true,"type"=>"info"),
	"user_confirmed"							=> array("enabled"=>true,"type"=>"info"),
	"user_rejected"								=> array("enabled"=>true,"type"=>"info"),
	"user_new_notification"						=> array("enabled"=>true,"type"=>"info")
);

?>