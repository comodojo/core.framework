<?php

/**
 * Basic functions to create, delete, edit comodojo REST services
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class services_management {

/********************** PRIVATE VARS *********************/
	/**
	 * Restrict roles management to administrator.
	 * 
	 * If disabled, it will not check user role (=1).
	 * 
	 * @default true;
	 */
	private $restrict_management_to_administrators = true;

	/**
	 * Reserved service names
	 */
	private $reserved_services = Array('services','service','srootnode','alias','application'); 
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * List local services.
	 * 
	 * This function returns:
	 *  - service name
	 *  - type of service (application/service/alias)
	 *  - service status (enabled/disabled)
	 * Referenced by service file name
	 * 
	 * @return	array	
	 */
	public function get_services() {
	 	
		$services = Array();
		
    	$service_path = opendir(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER);

		while(false !== ($service_item = readdir($service_path))) {

			$service_file_properties = pathinfo(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_item);

			$service_file = $service_file_properties['dirname'].'/'.$service_file_properties['basename'];

			if (!is_dir($service_file) AND $service_file_properties['extension'] == 'properties' AND $service_file_properties['basename'][0] != '.' ) {
					
				$service = file_get_contents($service_file);
				
				if (!$service) {
					comodojo_debug('Unable to open service properties file: '.$service_file.'; error reading file (corrupt?)','WARNING',"services_management");
					continue;
				}
				
				$service = json2array($service);

				if ($service_file_properties['filename'] != $service["name"]) {
					comodojo_debug('Unable to open service properties file '.$service_file.' service name is inconsistent','WARNING',"services_management");
					continue;
				}
				
				array_push($services, Array(
					"id"		=>	$service_file_properties['filename'],
					"name"		=>	$service["name"],
					"type"		=>	$service["type"],
					"enabled"	=>	$service["enabled"],
					"leaf"		=>	true
				));
				
			}
			else {

				continue;

			}

        }

		closedir($service_path);
		
		return $services;
		
	}
	
	/**
	 * Get service by service name
	 * 
	 * @param	string	$service	Service file name (without .properties or .service ext)
	 *
	 * @return	Array				{properties, service}
	 */
	public function get_service($service) {

		if (is_readable(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties') ) {
			
			$_properties = false;
			$_service = false;
			
			$properties = file_get_contents(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties');
				
			if (!$properties) throw new Exception("Unreadable service properties file", 2901);
			
			$_properties = json2array($properties);
			
			if (!$_properties['name']) throw new Exception("Unreadable service properties file", 2901);
			
			if ($_properties['type'] == 'SERVICE') {
				$_service = file_get_contents(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.service');
				if (!$_service) throw new Exception("Unreadable service file", 2907);
			}
			
			return Array(
				"properties_file"	=>	$_properties,
				"service_file"		=>	$_service
			);
			
		}
		else throw new Exception("Unreadable service properties file", 2901);

	}
	
	/**
	 * Add a new service to pool
	 * 
	 */
	public function new_service($service_name,$properties,$service=false) {
		
		if (empty($service_name)) throw new Exception("Unreadable service properties file", 2901);

		if (!isset($properties['name'])) throw new Exception("Invalid properties for a service", 2904);

		$_properties = Array();

		// Name for the service. Also the file name will have this name
		// Once created, service name COULD NOT be changed
		$_properties['name'] 			= $service_name;
		// Enable/disable service
		$_properties['enabled']			= isset($properties['enabled']) ? $properties['enabled'] : false;
		// Generic description (internal use only)
		$_properties['description']		= isset($properties['description']) ? $properties['description'] : '';
		
		// Service type. As service name, service type COULD NOT be changed.
		$_properties['is_service']		= isset($properties['is_service']) ? $properties['is_service'] : false;
		$_properties['is_alias']		= isset($properties['is_alias']) ? $properties['is_alias'] : false;
		$_properties['is_application']	= isset($properties['is_application']) ? $properties['is_application'] : false;

		// If alias, service will point to:
		$_properties['alias_for']		= isset($properties['alias_for']) ? $properties['alias_for'] : false;
		// If application, service will invoke:
		$_properties['application']		= isset($properties['application']) ? $properties['application'] : false;
		$_properties['method']			= isset($properties['method']) ? $properties['method'] : false;
		
		// Cache control
		// Cache type:
		// 'SERVER' -> cache content on server using comodojo.cache method
		// 'CLIENT' -> send to the client cache timeout but keep service fresh server-side
		// 'BOTH'   -> enable both server and client caching
		$_properties['cache']			= isset($properties['cache']) ? $properties['cache'] : false;
		// Cache time to live (in seconds)
		$_properties['ttl']				= isset($properties['ttl']) ? $properties['ttl'] : false;
		
		// Set the ACAO directive. It's a comma separated list of origins (fqdn)
		$_properties['access_control_allow_origin']	= isset($properties['access_control_allow_origin']) ? $properties['access_control_allow_origin'] : false;
		// Set supported http methods; default support to GET, POST, PUT, DELETE
		$_properties['supported_http_methods']		= isset($properties['supported_http_methods']) ? $properties['supported_http_methods'] : 'GET,POST,PUT,DELETE';
		// Force the returned content type (will ignore transport)
		$_properties['content_type']				= isset($properties['content_type']) ? $properties['content_type'] : false;
		// Array of required parameters; could be also an array of arrays as requested by func "attributes_to_parameters_match"
		$_properties['required_parameters']			= isset($properties['required_parameters']) ? $properties['required_parameters'] : Array();

		$properties_file_name = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_name.'.properties';
		$service_file_name = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_name.'.service';

		if (is_readable($properties_file_name) OR is_readable($service_file_name)) throw new Exception("Service name is used", 2905);

		$fh = fopen($properties_file_name, 'w');
		if (!fwrite($fh, array2json($_properties))) {
			fclose($fh);
			throw new Exception("Error writing service properties", 2906);
		}
		fclose($fh);
		
		if ($_properties['is_service'] == true) {
			$fh = fopen($service_file_name, 'w');
			if (!fwrite($fh, $service)) {
				fclose($fh);
				unlink($properties_file_name);
				throw new Exception("Error writing service properties", 2906);
			}
		}

		return true;

	}
	
	/**
	 * Modify exsisting service
	 * 
	 */
	public function edit_service($service_name, $properties, $service=false) {

		if (empty($service_name) OR empty($properties)) throw new Exception("Unreadable service properties file", 2901);

		try { $current = $this->get_service($service_name); } catch (Exception $e) { throw $e; }
		
		$current['properties']['enabled']						= isset($properties['enabled']) ? $properties['enabled'] : $current['properties']['enabled'];
		$current['properties']['description']					= isset($properties['description']) ? $properties['description'] : $current['properties']['description'];
		$current['properties']['alias_for']						= isset($properties['alias_for']) ? $properties['alias_for'] : $current['properties']['alias_for'];
		$current['properties']['application']					= isset($properties['application']) ? $properties['application'] : $current['properties']['application'];
		$current['properties']['method']						= isset($properties['method']) ? $properties['method'] : $current['properties']['method'];
		$current['properties']['cache']							= isset($properties['cache']) ? $properties['cache'] : $current['properties']['cache'];
		$current['properties']['ttl']							= isset($properties['ttl']) ? $properties['ttl'] : $current['properties']['ttl'];
		$current['properties']['access_control_allow_origin']	= isset($properties['access_control_allow_origin']) ? $properties['access_control_allow_origin'] : $current['properties']['access_control_allow_origin'];
		$current['properties']['supported_http_methods']		= isset($properties['supported_http_methods']) ? $properties['supported_http_methods'] : $current['properties']['supported_http_methods'];
		$current['properties']['content_type']					= isset($properties['content_type']) ? $properties['content_type'] : $current['properties']['content_type'];
		$current['properties']['required_parameters']			= isset($properties['required_parameters']) ? $properties['required_parameters'] : $current['properties']['required_parameters'];

		$properties_file_name = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_name.'.properties';
		$service_file_name = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_name.'.service';

		$fh = fopen($properties_file_name, 'w');
		if (!fwrite($fh, array2json($current['properties']))) {
			fclose($fh);
			throw new Exception("Error writing service properties", 2906);
		}
		fclose($fh);
		
		if ($current['properties']['is_service'] == true AND $service !== false) {
			$fh = fopen($service_file_name, 'w');
			if (!fwrite($fh, $service)) {
				fclose($fh);
				unlink($properties_file_name);
				throw new Exception("Error writing service properties", 2906);
			}
		}

		return true;

	}
	
	/**
	 * Delete existing service
	 * 
	 * @param	string	$service	Service file name (without .properties or .service ext)
	 *
	 * @return	bool				true on success, exception otherwise
	 */
	public function delete_service($service) {
		
		if (is_readable(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties')) {
			
			$result = @unlink(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties');
			
			$_result = @unlink(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service_file.'.service');
			
			if (!$result) throw new Exception("Cannot delete service file", 2903);
			
		}
		else throw new Exception("Cannot find service properties file", 2902);

		return true;

	}	
/********************* PUBLIC METHODS ********************/
	
}

function loadHelper_services_management() { return false; }

?>