<?php

/**
 * service.php
 * 
 * A common base for all dispached REST services
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 
class service {
	
	public final function getServiceImplementedMethods($supportedMethods) {
		if (method_exists($this, 'logic')) {
			$_supportedMethods = explode(',',$supportedMethods);
		}
		else {
			$supportedMethods = explode(',',strtoupper($supportedMethods));
			$_supportedMethods = Array();
			foreach ($supportedMethods as $method) {
				if (method_exists($this, strtolower($method))) array_push($_supportedMethods,$method);
			}
		}
		return $_supportedMethods;
	}
	
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_rpc_client
 */
 function loadHelper_service() { return false; }

?>