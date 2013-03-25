<?php

/** 
 * events.php
 * 
 * records events & stats about process, user and client.
 * 
 * Events are enabled via EVENTS_ENABLE parameter at boot time and they will be detailed by
 * specific method invocation - consolidateEvents()
 *
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class events {

	/**
	 * If true, no exception will be thrown in case of error
	 * @var	bool
	 */
	private $fail_silently = false;
	
	/**
	 * If true, events database will be consolidated at each get_event
	 */
	private $auto_consolidate_events = true;

	public function __construct($fail_silently=false) {
		$this->fail_silently = $fail_silently;
	}
	
	/**
	 * Record an event
	 * 
	 * @param	string	$event			The event name
	 * @param	string	$eventReferTo	An extra field that could be used to store info referred to event
	 * @param	bool	$success		[optional] extra field to record success/failure of the action
	 * 
	 * @return	array	$toReturn		The record result as success=[true/false],result=[result/error]
	 */
	public function record($event, $eventReferTo, $success=true) {
		
		if (COMODOJO_EVENTS_ENABLED AND $this->should_be_recorded($event)) {
			
			comodojo_debug('Starting event recording for event: '.$event,'INFO','events');
			
				$eventArray = array(
					0,
					$event,
					$eventReferTo,
					!$success ? 0 : 1,
					strtotime("now"),
					defined('COMODOJO_USER_NAME') ? (is_null(COMODOJO_USER_NAME) ? 'GUEST' : COMODOJO_USER_NAME) : 'GUEST',
					$_SERVER["REMOTE_ADDR"],
					isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : 'unknown',
					null,
					null,
					session_id()
				);
			
			try {
				$this->record_event($eventArray);
			} catch (Exception $e) {
				comodojo_debug('Error recording event: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','events');
				if (!$this->fail_silently) throw $e;
			}
			
			comodojo_debug('Event '.$event.' about session '.session_id().' recorded at '.date(DATE_RFC822),'INFO','events');
			
			return true;
			
		}
		else {
			return false;
		}
		
	}

	public function get_events($limit=100, $offset=0, $params=Array()) {
			
		comodojo_load_resource('database');
		
		if ($this->auto_consolidate_events) {
			try {
				$this->consolidate_events();
			}
			catch (Exception $e) {
				comodojo_debug('consolidate_events fail will NOT stop get_events','ERROR','events');
			}
		}
		
		// compute where
		$_where = array();
		
		if (@$params['type'] != false) {
			array_push($_where,Array("type","=",$params['type']));
		}
		if (@$params['referTo'] != false) {
			if(sizeof($_where) != 0) array_push($_where,"AND");
			array_push($_where,Array("referTo","=",$params['referTo']));
		}
		if (@$params['success'] != false) {
			if(sizeof($_where) != 0) array_push($_where,"AND");
			array_push($_where,Array("success","=",$params['success']));
		}
		if (@$params['userName'] != false) {
			if(sizeof($_where) != 0) array_push($_where,"AND");
			array_push($_where,Array("userName","=",$params['userName']));
		}
		if (@$params['host'] != false) {
			if(sizeof($_where) != 0) array_push($_where,"AND");
			array_push($_where,Array("host","=",$params['host']));
		}
		if (@$params['browser'] != false) {
			if(sizeof($_where) != 0) array_push($_where,"AND");
			array_push($_where,Array("browser","=",$params['browser']));
		}
		if (@$params['OS'] != false) {
			if(sizeof($_where) != 0) array_push($_where,"AND");
			array_push($_where,Array("OS","=",$params['OS']));
		}
		if (@$params['start'] != false AND @$params['end'] != false) {
			//if(sizeof($_where) != 0) array_push($_where,"AND");
			//array_push($_where,Array("OS","=",$params['OS']));
		}
		
		try {
			$db = new database();
			$db->setTable("events");
			$db->setKeys(Array("id","type","referTo","success","timestamp","userName","host","userAgent","browser","OS","sessionId"));
			$db->addOrder("timestamp","DESC");
			if(sizeof($_where) != 0) $db->setWhere($_where);
			if ($limit != 0) {
				if ($offset == 0) $db->setLimit(intval($limit,10));
				else $db->setLimit(intval($offset,10),intval($limit,10));
			}
			$result = $db->read();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving events: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','events');
			if (!$this->fail_silently) throw $e;
		}
		
		return $result['result'];
		
	}

	public function followSession($session_id, $limit=false, $offset=false) {
		
		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$db->setTable("events");
			$db->setKeys(Array("id","type","referTo","success","timestamp","userName","host","userAgent","browser","OS","sessionId"));
			$db->addOrder("timestamp","DESC");
			$db->setWhere(Array("sessionId","=",$session_id));
			if ($limit !== false) {
				if ($offset == 0) $db->setLimit(intval($limit,10));
				else $db->setLimit(intval($offset,10),intval($limit,10));
			}
			$result = $db->read();
		}
		catch (Exception $e) {
			comodojo_debug('Error following session: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','events');
			if (!$this->fail_silently) throw $e;
		}
		
		return $result['result'];
		
	}

	/**
	 * Get detailed information from useragent
	 * 
	 * @param	string	$useragent	The useragent to be processed
	 * 
	 * @return	array	(browser,os)
	 */
	public function get_details($useragent='') {
		
		comodojo_load_resource('browser_detection');
		
		$b_wk = browser_detection('browser_working','',$useragent);
		if ($b_wk == "moz") {
			$b_data = browser_detection('moz_data','',$useragent);
			$b_name = $b_data[0] . "," . $b_data[1];
		}
		elseif ($b_wk == "webkit") { 
			$b_data = browser_detection('webkit_data','',$useragent);
			$b_name = $b_data[0] . "," . $b_data[1];
		}
		else {
			$b_name = browser_detection('browser_name','',$useragent) . "," . browser_detection('browser_number','',$useragent);
		}
		
		$os_name = browser_detection('os','',$useragent) . "," . browser_detection('os_number','',$useragent);
		
		return Array($b_name, $os_name);
			
	}
	
	/**
	 * Get detailed information from useragent
	 * 
	 * @param	string	$useragent	The useragent to be processed
	 * 
	 * @return	array	('browser_working','browser_number','ie_version','dom','safe','os','os_number','browser_name_','ua_type',
	 * 					 'browser_math_number','moz_data' => $a_moz_data,,'webkit_data','mobile_test','mobile_data','true_ie_number',
	 * 					 'run_time','html_type','engine_data')
	 */
	public function get_complete_details($useragent) {
		
		comodojo_load_resource('browser_detection');
		
		return browser_detection('full_assoc','',$useragent);
			
	}
	
	/**
	 * Should a specific event be recorded?
	 * 
	 * @param	string	$event	The event, as coded in eventsRecording.php
	 * 
	 * @return	true if event should be recorded, false otherwise
	 */
	private function should_be_recorded($eventName) {
		
		require(COMODOJO_CONFIGURATION_FOLDER."eventsRecording.php");
		
		if (isset($_events[$eventName])) return $_events[$eventName]['enabled'];
		else return $_recordUnknownEvents;
		
	}

	/**
	 * Do events recording
	 * 
	 * @param	array	$eventArray	An array containing event fields
	 */
	private function record_event($sArray) {
		
		comodojo_load_resource('database');
		
		try {
			$db = new database();
			$db->setTable("events");
			$db->addValues($sArray);
			$result = $db->store();
		} catch (Exception $e) {
			comodojo_debug('Error recording event ',$sArray[1].' about session '.session_id(),'ERROR','events');
			throw $e;
		}
		
	}
	
	/**
	 * Do events consolidation
	 */
	public function consolidate_events() {
		
		comodojo_debug('Consolidating events...','INFO','events');
		
		comodojo_load_resource('database');
		
		$ua_store = Array();
		
		try {
			$db = new database();
			$db->setTable("events");
			$db->setKeys(Array('userAgent'));
			$db->setWhere(Array(Array('browser','IS',NULL), 'AND', Array('OS','IS',NULL)));
			$db->setGroup('userAgent');
			$result = $db->read();
			comodojo_debug('There are '.$result['resultLength'].' user agent class to consolidate.','INFO','events');
			foreach ($result['result'] as $ua) {
				$db->cleanup();
				$db->setTable("events");
				$db->setKeys(Array('browser','OS'));
				$db->setWhere(Array(Array('userAgent','=',$ua['userAgent']), 'AND', Array('browser','IS',NULL), 'AND', Array('OS','IS',NULL)));
				$db->setValues($this->get_details($ua['userAgent']));
				$db->update();
			}
			
		} catch (Exception $e) {
			comodojo_debug('Error consolidating events: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','events');
			if (!$this->fail_silently) throw $e;
		}
		
	}
	
}

function loadHelper_events() { return false; }

?>