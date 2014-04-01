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

class eventviewer extends application {
	
	public function init() {
		$this->add_store_methods('events',Array("GET","QUERY"));
		$this->add_application_method('consolidate_events', 'consolidateEvents', Array(), 'No description available, sorry',false);
	}

	public function consolidateEvents() {
		comodojo_load_resource('events');
		try {
			$ev = new events();
			$result = $ev->consolidate_events();
			if ($result == -1) throw new Exception("Unknown error in events consolidation", 0);
		}
		catch (Exception $e){
			throw $e;
		}
		return $result;
	}
	
}

?>