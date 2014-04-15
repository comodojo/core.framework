<?php

/**
 * Cacheman - get statistics about cache usage and clean cache
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class cacheman extends application {
	
	public function init() {
		$this->add_application_method('getStats', 'get_stats', Array(), 'Returns cache statistics; no extra parameter required.',false);
		$this->add_application_method('purgeCache', 'purge_cache', Array(), 'Clean ALL comodojo cache pages; no extra parameter required.',false);
	}
	
	public function get_stats() {
		comodojo_load_resource('cache');
		$c = new cache();
		return $c->get_stats();
	}
	
	public function purge_cache() {
		comodojo_load_resource('cache');
		$c = new cache();
		return $c->purge_cache();
	}
	
}

?>
