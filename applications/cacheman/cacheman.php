<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class cacheman extends application {
	
	public function init() {
		$this->add_application_method('get_stats', 'getStats', Array(), 'Returns cache statistics; no extra parameter required.',false);
		$this->add_application_method('purge_cache', 'purgeCache', Array(), 'Clean ALL comodojo cache pages; no extra parameter required.',false);
	}
	
	public function getStats() {
		comodojo_load_resource('cache');
		$c = new cache();
		return $c->get_stats();
	}
	
	public function purgeCache() {
		comodojo_load_resource('cache');
		$c = new cache();
		return $c->purge_cache();
	}
	
}

?>
