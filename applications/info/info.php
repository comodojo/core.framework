<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class info extends application {
	
	public function init() {
		$this->add_application_method('getInfo', 'get_info', Array(), 'info.getInfo() - Return information about comodojo installation. No parameters required.',false);
	}
	
	public function get_info() {
		$this->success = true;
		return Array(
			Array('id'=>1,'info'=>'0001', 'value'=>isset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['QUERIES']) ? $_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['QUERIES'] : 0),
			Array('id'=>2,'info'=>'0002', 'value'=>COMODOJO_EVENTS_ENABLED),
			Array('id'=>3,'info'=>'0003', 'value'=>COMODOJO_STARTUP_CACHE_ENABLED),
			Array('id'=>4,'info'=>'0004', 'value'=>COMODOJO_GLOBAL_DEBUG_ENABLED),
			Array('id'=>5,'info'=>'0005', 'value'=>COMODOJO_GLOBAL_DEBUG_LEVEL),
			Array('id'=>6,'info'=>'0006', 'value'=>COMODOJO_DEFAULT_ENCODING),
			Array('id'=>7,'info'=>'0007', 'value'=>COMODOJO_SITE_LOCALE),
			Array('id'=>8,'info'=>'0008', 'value'=>COMODOJO_SUPPORTED_LOCALES),
			Array('id'=>9,'info'=>'0009', 'value'=>PHP_VERSION),
			Array('id'=>10,'info'=>'0010', 'value'=>COMODOJO_DB_DATA_MODEL),
			Array('id'=>11,'info'=>'0011', 'value'=>COMODOJO_CACHE_ENABLED),
			Array('id'=>12,'info'=>'0012', 'value'=>COMODOJO_CACHE_TTL),
			Array('id'=>13,'info'=>'0013', 'value'=>COMODOJO_JS_DEBUG),
			Array('id'=>14,'info'=>'0014', 'value'=>COMODOJO_JS_DEBUG_POPUP),
			Array('id'=>15,'info'=>'0015', 'value'=>COMODOJO_JS_DEBUG_DEEP),
			Array('id'=>16,'info'=>'0016', 'value'=>COMODOJO_SHELL_ENABLED),
			Array('id'=>17,'info'=>'0017', 'value'=>COMODOJO_SERVICES_ENABLED),
			Array('id'=>18,'info'=>'0018', 'value'=>COMODOJO_RPC_ENABLED),
			Array('id'=>19,'info'=>'0019', 'value'=>COMODOJO_CRON_ENABLED),
			Array('id'=>20,'info'=>'0020', 'value'=>comodojo_version()),
			Array('id'=>21,'info'=>'0021', 'value'=>COMODOJO_USER_NAME),
			Array('id'=>22,'info'=>'0022', 'value'=>COMODOJO_USER_ROLE),
			Array('id'=>23,'info'=>'0023', 'value'=>COMODOJO_AUTHENTICATION_MODE)
		);
	}
	
}

?>