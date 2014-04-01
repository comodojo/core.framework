<?php

/**
 * services.php
 * 
 * Integrated, multi-functional interface for REST services dispatchment.
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

require 'comodojo/global/comodojo_basic.php';

class services extends comodojo_basic {

	public $script_name = 'services.php';
	
	public $use_session_transport = false;
	
	public $require_valid_session = false;
	
	public $do_authentication = false;
	
	public $auto_set_header = false;
	
	private $application = false;
	
	private $method = false;
	
	private $transport = 'JSON';
	
	private $content_type = false;
	
	private $app_exec = false;
	
	private $app_run = false;
	
	private $origins = false;
	
	private $origin = false;
	
	private $cache = false;
	
	public function logic($attributes) {
		
		if (!defined('COMODOJO_SERVICES_ENABLED') OR @!COMODOJO_SERVICES_ENABLED) throw new Exception("services closed", 200);
		
		if (!isset($attributes['service'])) throw new Exception("conversation error", 400);
		
		if (isset($attributes['transport'])) { $this->transport = strtoupper($attributes['transport']); }
		
		try {
			list($service_properties, $exec_name, $service_service_file) = $this->get_service_properties($attributes);
		}
		catch (Exception $e) {
			throw $e;
		}
		
		if (!$service_properties['enabled']) {
			comodojo_debug("Unable to serve content for service: ".$service_properties['name'].", service disabled",'WARNING','services');
			throw new Exception("unknown service or service disabled", 400);
		}
		
		comodojo_load_resource('events');
		$event = new events();
		$event->record('service_request', $service_properties['name']);
		
		$this->cache = strtoupper($service_properties['cache']);
		
		if ($this->cache == 'SERVER' OR $this->cache == 'BOTH') comodojo_load_resource('cache');
		
		if (!empty($service_properties['content_type'])) {
			$this->transport = false;
			$this->content_type = $service_properties['content_type'];
		}
		
		if ($service_properties['access_control_allow_origin'] == "*") $this->origin = '*';
		elseif (!empty($service_properties['access_control_allow_origin'])) {
			$this->origins = explode(",",$service_properties['access_control_allow_origin']);
			if (!in_array($_SERVER['HTTP_ORIGIN'],$origins)) {
				comodojo_debug("Not allowed orign (".$_SERVER['HTTP_ORIGIN'].") request for service: ".$service_properties['name'],'WARNING','services');
				throw new Exception("Origin not allowed", 403);
			}
			else $this->origin = $_SERVER['HTTP_ORIGIN'];
		}
		else $this->origin = false;
		
		if (!in_array($_SERVER['REQUEST_METHOD'], explode(',',$service_properties['supported_http_methods']))) {
			comodojo_debug("Unsupported http method (".$_SERVER['REQUEST_METHOD'].") for service: ".$service_properties['name'],'WARNING','services');
			throw new Exception($service_properties['supported_http_methods'], 405);
		} 
		
		$request = 'COMODOJO_SERVICES-'.var_export($attributes,true);
		
		if ($this->cache == 'SERVER' OR $this->cache == 'BOTH') {			
			$c = new cache();
			$cache = $c->get_cache($request, false, false, $service_properties['ttl']);
			if ($cache !== false) {
				comodojo_debug('Data for service: '.$service_properties['name'].' loaded from cache','INFO','services');
				return $this->compose_return_data($cache[2],$cache[0]);
			}
		}
		
		try {
			if (strtoupper($service_properties['type']) == "SERVICE") {
				if (!is_readable($service_service_file)) {
					comodojo_debug('Cannot read service file: '.$service_service_file,'ERROR','services');
					throw new Exception('Internal Server Error', 500);
				}

				comodojo_load_resource('service');
				require $service_service_file;
				
				if (!class_exists($exec_name)) {
					comodojo_debug('Wrong class implementation for service: '.$service_properties['name'],'ERROR','services');
					throw new Exception('Internal Server Error', 500);
				}
				$service_run = new $exec_name;
				$implemented_methods = $service_run->getServiceImplementedMethods($service_properties['supported_http_methods']);
				
				if (!in_array($_SERVER['REQUEST_METHOD'], $implemented_methods)) {
					comodojo_debug('Service '.$service_properties['name'].'was requested with not implemented http method','WARNING','services');
					throw new Exception(implode(",",$implemented_methods), 501);
				}
				if (!attributes_to_parameters_match($attributes, $service_properties['required_parameters'])) {
					comodojo_debug('Unsustainable request for service '.$service_properties['name'].': parameters mismatch','WARNING','services');
					throw new Exception("conversation error", 400);
				}
				$current_method = strtolower($_SERVER['REQUEST_METHOD']);
				
				$result = $service_run->$current_method($attributes);
				
				comodojo_debug('Serving result for service: '.$service_properties['name'].' (plain service)','INFO','services');
				
			}
			elseif (strtoupper($service_properties['type']) == "APPLICATION") {
				$this->app_exec = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER.$service_properties['service_application'].'/'.$service_properties['service_application'].'.php';
				if (!is_readable($this->app_exec)) {
					comodojo_debug('Cannot read app file for service: '.$service_service_file,'ERROR','services');
					throw new Exception('Internal Server Error', 500);
				}

				require $this->app_exec;
				if (!class_exists($service_properties['service_application'])) {
					comodojo_debug('Wrong application class for service: '.$service_properties['name'],'ERROR','services');
					throw new Exception('Internal Server Error', 500);
				}
				
				$app_run = new $service_properties['service_application'];
				$method = $app_run->get_registered_method($service_properties['service_method']);
				if (!$method) {
					comodojo_debug("Unsustainable request: method ".$this->method." not registered correctly",'ERROR','services');
					throw new Exception('Internal Server Error', 500);
				}
				
				if (!attributes_to_parameters_match($attributes, $method[1])) {
					comodojo_debug('Unsustainable request for service '.$service_properties['name'].': parameters mismatch','WARNING','services');
					throw new Exception("conversation error",400);
				}

				if (!attributes_to_parameters_match($attributes, $service_properties['required_parameters'])) {
					comodojo_debug('Unsustainable request for service '.$service_properties['name'].': parameters mismatch','WARNING','services');
					throw new Exception("conversation error",400);
				}
				
				$result = $app_run->$method[0]($attributes);
				
				comodojo_debug('Serving result for service: '.$service_properties['name'].' (application service)','INFO','services');
				
			}
			else throw new Exception('Internal Server Error', 500);
		}
		catch (Exception $e) {
			if ($e->getCode() <= 505) throw $e;
			else throw new Exception('Internal Server Error', 500);
		}
		
		$to_return = $this->encode_return_data($result);
		
		if ($this->cache == 'SERVER' OR $this->cache == 'BOTH') $c->set_cache($to_return, $request, false, false);
		
		return $this->compose_return_data($to_return, $service_properties['ttl']);
		
	}
	
	private function get_service_properties($attributes) {
		
		$service_properties_file = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$attributes['service'].'.properties';
		$service_service_file = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$attributes['service'].'.service';
		if (!is_readable($service_properties_file)) {
			comodojo_debug("Cannot read service properties file for service: ".$attributes['service'],'ERROR','services');
			throw new Exception("unknown service or service disabled", 400);
		}
		
		$service_file = file_get_contents($service_properties_file);
		$service_properties = json2array($service_file);
		if (!is_array($service_properties)) {
			comodojo_debug("Error reading service properties file: ".$service_properties_file,'ERROR','services');
			throw new Exception('Internal Server Error', 500);
		}

		$exec_name = $service_properties['name'];

		if (strtoupper($service_properties['type']) == "ALIAS") {
			
			$service_properties_file = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_properties['alias_for'].'.properties';
			$service_service_file = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_properties['alias_for'].'.service';
			
			if (!is_readable($service_properties_file)) {
				comodojo_debug("Cannot read alias service properties file: ".$service_properties_file,'ERROR','services');
				throw new Exception("unknown service or service disabled", 400);
			}
			
			$alias_service_file = file_get_contents($service_properties_file);
			$alias_service_properties = json2array($alias_service_file);
			if (!is_array($alias_service_properties)) {
				comodojo_debug("Error reading alias service properties file: ".$service_properties_file,'ERROR','services');
				throw new Exception('Internal Server Error', 500);
			}
			else {
				comodojo_debug("Service ".$service_properties['name']." is an alias for ".$service_properties['alias_for'].", now merging parameters",'ERROR','services');
				
				$service_properties['enabled'] = ($service_properties['enabled'] AND $alias_service_properties['enabled']);
				$service_properties['alias_for'] = false;
				$service_properties['type'] = $alias_service_properties['type'];
				//$service_properties['required_parameters'] = $alias_service_properties['required_parameters'];
				$service_properties['service_application'] = $alias_service_properties['service_application'];
				$service_properties['service_method'] = $alias_service_properties['service_method'];
				$exec_name = $alias_service_properties['name'];
				
			}
			
		}
		
		return Array($service_properties, $exec_name, $service_service_file);
		
	}
	
	public function error($status_code, $error_name) {
		
		if ($status_code > 505) {
			set_header(Array('statusCode' => 500), 0);
			return 'Internal Server Error';
		}
		
		switch ($status_code) {
			case 405:
				set_header(Array(
					'statusCode'		=>	$status_code,
					'allowedMethods'	=>	$error_name
				), 0);
				$to_return = NULL;
			break;
			case 501:
				set_header(Array(
					'statusCode'			=>	$status_code,
					'implementedMethods'	=>	$error_name
				), 0);
				$to_return = NULL;
			break;
			default:
				set_header(Array(
					'statusCode'			=>	$status_code
				), strlen($error_name));
				$to_return = $error_name;
			break;
		}
		
		return $to_return;
		
	}
	
	private function compose_return_data($to_return, $ttl=null) {
		
		switch ($this->transport) {
			case 'JSON': $contentType = 'application/json'; break;
			case 'XML': $contentType = 'application/xml'; break;
			case 'YAML': $contentType = 'application/x-yaml'; break;
			default: $contentType = $this->content_type; break;
		}
		
		if (in_array($this->cache,Array('SERVER','CLIENT','BOTH'))) $_ttl = $ttl;
		else $_ttl = null;
		
		set_header(Array(
			'statusCode'				=>	is_null($to_return) ? 204 : 200,
			'ttl'						=> 	$_ttl,
			'contentType'				=>	$contentType,
			'charset'					=>	COMODOJO_DEFAULT_ENCODING,
			'accessControlAllowOrigin'	=>	$this->origin
		), strlen($to_return));
		
		return $to_return;
		
	}
	
	private function encode_return_data($data) {
		
		switch ($this->transport) {
			case 'JSON': $to_return = array2json($data); break;
			case 'XML': $to_return = array2xml($data); break;
			case 'YAML': $to_return = array2yaml($data); break;
			default: $to_return = $data; break;
		}
		
		return $to_return;
		
	}
	
}

$service = new services();

?>