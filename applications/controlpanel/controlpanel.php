<?php

/**
 * Comodojo Control Panel
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class controlpanel extends application {

	public function init() {
		$this->add_application_method('get_main_view', 'getMainView', Array(), 'No description available, sorry',false);
		$this->add_application_method('get_state', 'getState', Array("group"), 'No description available, sorry',false);
		$this->add_application_method('set_state', 'setState', Array("group"), 'No description available, sorry',false);
		$this->add_application_method('set_value', 'setValue', Array("option","value"), 'No description available, sorry',false);
	}

	public function getMainView($attributes) {

		try{
			list($panels, $options) = $this->get_states();
		}
		catch (Exception $e) {
			throw $e;
		}

		$_panels = Array();

		foreach ($panels as $name=>$properties) {
			$_panels[$name] = Array(
				"builder"	=>    $properties["builder"],
				"icon"		=>    $properties["icon"],
				"label"		=>    $properties["label"],
				"include"	=>    $properties["include"]
				);
		}

		return ($_panels);

	}

	public function getState($attributes) {

		comodojo_load_resource('database');

		try{
			list($panels, $options) = $this->get_states();
			if (!isset($panels[$attributes['group']])) throw new Exception("Unknown panel/group", 10011);
			$db = new database();
			$values = $db->table($panels[$attributes['group']]['table'])
				->keys(Array('option','value'))
				->where($panels[$attributes['group']]['where'][0],$panels[$attributes['group']]['where'][1],$panels[$attributes['group']]['where'][2])
				->and_where('option','IN',$panels[$attributes['group']]['include'])
				->get();
		}
		catch (Exception $e) {
			throw $e;
		}

		$_values = Array();

		foreach ($values['result'] as $value) {
			$_values[$value['option']] = $value['value'];
		}

		$toReturn = Array(
			"builder"	=>    $panels[$attributes['group']]['builder'],
			"label"		=>    $panels[$attributes['group']]['label'],
			"includes"	=>    Array()
			);

		if (isset($panels[$attributes['group']]["note"])) array_push($toReturn["includes"],$panels[$attributes['group']]["note"]);

		foreach ($panels[$attributes['group']]['include'] as $include) {
            //error_log($include.'='.$_values[$include]);
			if (!isset($options[$include])) throw new Exception("Unknown option in panel/group", 10012);
			if (!array_key_exists($include,$_values)) throw new Exception("Option ".$include." is not in database", 10013);
			array_push($toReturn["includes"],array_merge($options[$include],Array("value"=>$_values[$include]),Array("name"=>$include)));
		}

		return ($toReturn);
	}

	public function setState($attributes) {
		comodojo_load_resource('database');
		comodojo_load_resource('cache');
		$this->cache = new cache();
		$this->cache->purge_cache();
		try {

			if (!isset($this->panels) OR !isset($this->options)) {
				list($this->panels, $this->options) = $this->get_states();
			}

			if (!isset($this->panels[$attributes["group"]])) throw new Exception("Unknown panel/group", 10011);

			$this->db = new database();

			foreach ($this->panels[$attributes["group"]]["include"] as $include) {
            	//check if option/value is required and it's not empty
				if ($this->options[$include]["required"] AND (!isset($attributes[$include]) OR @is_null($attributes[$include]) OR @$attributes[$include] == '')) throw new Exception("Invalid value for option ".$include, 10015);
				else {
					//check if option/value is not required and it's empty
					if (!$this->options[$include]["required"] AND !isset($attributes[$include])) $attributes[$include] = NULL;
					//escape for the bootstrap field
					if ($include == "BOOTSTRAP") $attributes[$include] = stripslashes($attributes[$include]);
		      	    //register value
					$this->setValue(Array("option"=>$include, "value"=>$attributes[$include]));
				}
			}

		}
		catch (Exception $e) {
			throw $e;
		}
		return true;
	}

	public function setValue($attributes) {
		comodojo_load_resource('database');
		comodojo_load_resource('events');
		$ev = new events();
		try {
			if (!isset($this->panels) OR !isset($this->options)) { list($this->panels, $this->options) = $this->get_states(); }
			$found = false;
			$table = false;
			$where = false;
			foreach ($this->panels as $panel) {
				if (in_array($attributes["option"], $panel["include"])) {
					$found = true;
					$table = $panel["table"];
					$where = $panel["where"];
					break;
				}
				else continue;
			}
			if (!$found OR !isset($this->options[$attributes["option"]])) throw new Exception("Option ".$attributes["option"]." is not in database", 10013);
			if (isset($this->options[$attributes["option"]]["condition"]) AND is_array($this->options[$attributes["option"]]["condition"]) AND @count($this->options[$attributes["option"]]["condition"]) == 3) {
				if (!value_coherence_check($this->options[$attributes["option"]]["condition"][0],$this->options[$attributes["option"]]["condition"][1],$this->options[$attributes["option"]]["condition"][2])) throw new Exception("Invalid value for option", 10015);
			}

			if (is_resource($this->db)) {
				$this->db->clean();
				$result = $this->db->table($table)->keys('value')->values($attributes["value"])
					->where("option","=",$attributes["option"])
					->and_where($where[0],$where[1],$where[2])
					->update();
			}
			else {
				$db = new database();
				$result = $db->table($table)->keys('value')->values($attributes["value"])
					->where("option","=",$attributes["option"])
					->and_where($where[0],$where[1],$where[2])
					->update();
				unset($db);
			}

            //if ($result["affectedRows"] == 0) throw new Exception("Error updating option's value", 10014);
			$ev->record('configuration_change', $attributes["option"]);

		}
		catch (Exception $e) {
			$ev->record('configuration_change', $attributes["option"], false);
			throw $e;
		}
		if (!isset($this->cache)) {
			comodojo_load_resource('cache');
			$this->cache = new cache();
			$this->cache->purge_cache();
		}
		return true;
	}

	private function get_states() {

		comodojo_load_resource('cache');
		$request = "COMODOJO_CONTROLPANEL_GET_STATES";

		$c = new cache();
		$cache = $c->get_cache($request, 'JSON', false);
		if ($cache !== false) {
			$to_return = Array($cache[2]['global_panels'],$cache[2]['global_options']);
		}
		else {
			$panels_folder = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER."controlpanel/resources/panels/";

			$handler = opendir($panels_folder);

			$global_panels = Array();

			$global_options = Array();

			if (!$handler) {
				comodojo_debug('Cannot open panels folder','ERROR','filesystem');
				throw new Exception("Cannot open panels folder", 10010);
			}

			while (false !== ($item = readdir($handler))) {
	            //skip references
				if ( ($item == ".") OR ($item == "..") OR ($item[0] == ".") ) { continue; }

				require($panels_folder.$item);

				if (isset($panels) AND isset($options)) {
					foreach ($panels as $panel_name => $panel) { $global_panels[$panel_name] = $panel; }
					foreach ($options as $option_name => $option) { $global_options[$option_name] = $option; }
					unset($panels);
					unset($options);
				}
				else {
					comodojo_debug('Unable to open panel '.$item,'ERROR','filesystem');
					continue;
				}

			}
			closedir($handler);
			$to_return = Array($global_panels, $global_options);
			$c->set_cache(Array('global_panels'=>$global_panels,'global_options'=>$global_options), $request, 'JSON', false);
		}


		return $to_return;

	}

}

?>