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
	private $reserved_services = Array('services','service','srootnode','alias','application', 'method'); 
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * List local services.
	 * 
	 * This function returns:
	 *  - service id (it should be == service name)
	 *  - service name
	 *  - type of service (application/service/alias)
	 *  - service status (enabled/disabled)
	 *
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

				if (!isset($service["name"]) OR !isset($service["type"]) OR !isset($service["enabled"])) {
					comodojo_debug('Unable to open service properties file: '.$service_file.'; error reading file (corrupt?)','WARNING',"services_management");
					continue;
				}

				if ($service_file_properties['filename'] != $service["name"]) {
					comodojo_debug('Unable to open service properties file '.$service_file.' service name is inconsistent','WARNING',"services_management");
					continue;
				}
				
				array_push($services, Array(
					"id"		=>	$service_file_properties['filename'],
					"name"		=>	$service["name"],
					"type"		=>	$service["type"],
					"enabled"	=>	$service["enabled"]
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
			
			$_properties['required_parameters'] = implode(',', $_properties['required_parameters']);

			if ($_properties['type'] == 'SERVICE') {
				$_service = file_get_contents(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.service');
				if (!$_service) throw new Exception("Unreadable service file", 2907);
			}
			
			return Array(
				"properties_file"	=>	$_properties,
				"service_file"		=>	$_service
			);
			
		}
		else throw new Exception("Cannot find service properties file", 2902);

	}
	
	/**
	 * Enable service by service name
	 * 
	 * @param	string	$service	Service file name (without .properties or .service ext)
	 *
	 * @return	bool				True if success, exception otherwise
	 */
	public function enable_service($service) {

		if (empty($service)) throw new Exception("Cannot find service properties file", 2902);

		if (is_readable(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties') ) {
			
			$properties_file_name = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties';

			$properties = file_get_contents($properties_file_name);
				
			if (!$properties) throw new Exception("Unreadable service properties file", 2901);
			
			$_properties = json2array($properties);

			if (!$_properties['name']) throw new Exception("Unreadable service properties file", 2901);

			$_properties['enabled'] = true;
			
			$fh = fopen($properties_file_name, 'w');
			if (!fwrite($fh, array2json($_properties))) {
				fclose($fh);
				throw new Exception("Error writing service properties", 2906);
			}
			fclose($fh);

			return Array(
				"name"	=>	$_properties['name'],
				"type"	=>	$_properties['type'],
				"enabled"=> $_properties['enabled']
			);
			
		}
		else throw new Exception("Cannot find service properties file", 2902);

	}

	/**
	 * Disable service by service name
	 * 
	 * @param	string	$service	Service file name (without .properties or .service ext)
	 *
	 * @return	bool				True if success, exception otherwise
	 */
	public function disable_service($service) {
		
		if (empty($service)) throw new Exception("Cannot find service properties file", 2902);

		if (is_readable(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties') ) {
			
			$properties_file_name = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.properties';

			$properties = file_get_contents($properties_file_name);
				
			if (!$properties) throw new Exception("Unreadable service properties file", 2901);
			
			$_properties = json2array($properties);

			if (!$_properties['name']) throw new Exception("Unreadable service properties file", 2901);

			$_properties['enabled'] = false;
			
			$fh = fopen($properties_file_name, 'w');
			if (!fwrite($fh, array2json($_properties))) {
				fclose($fh);
				throw new Exception("Error writing service properties", 2906);
			}
			fclose($fh);

			return Array(
				"name"	=>	$_properties['name'],
				"type"	=>	$_properties['type'],
				"enabled"=> $_properties['enabled']
			);
			
		}
		else throw new Exception("Cannot find service properties file", 2902);

	}

	/**
	 * Add a new service to pool
	 * 
	 */
	public function new_service($properties) {
		
		if (empty($properties)) throw new Exception("Cannot find service properties file", 2902);

		if (!isset($properties['name']) OR !isset($properties['type']) OR !isset($properties['supported_http_methods'])) throw new Exception("Invalid properties for a service", 2904);

		if (in_array($properties['name'], $this->reserved_services)) throw new Exception("Service name is used", 2905);

		$http_methods = explode(',', $properties['supported_http_methods']);

		foreach ($http_methods as $method) {
			if (!in_array($method, Array('GET','POST','PUT','DELETE'))) {
				throw new Exception("Invalid properties for a service", 2904);
			}
		}

		switch ($properties['type']) {
			case 'SERVICE':
				if (empty($properties['service_file'])) throw new Exception("Invalid properties for a service", 2904);
			break;
			case 'APPLICATION':
				if (!isset($properties['service_application']) OR !isset($properties['service_method'])) throw new Exception("Invalid properties for a service", 2904);
			break;
			case 'ALIAS':
				if (!isset($properties['alias_for'])) throw new Exception("Invalid properties for a service", 2904);
			break;
			default:
				throw new Exception("Invalid properties for a service", 2904);
			break;
		}

		$properties_file_name	= COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$properties['name'].'.properties';
		$service_file_name		= COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$properties['name'].'.service';

		if (is_readable($properties_file_name) OR is_readable($service_file_name)) throw new Exception("Service name is used", 2905);

		$_properties = Array();

		// Name for the service. Also the file name will have this name
		// Once created, service name COULD NOT be changed
		$_properties['name'] 			= $properties['name'];

		// Enable/disable service
		$_properties['enabled']			= isset($properties['enabled']) ? filter_var($properties['enabled'], FILTER_VALIDATE_BOOLEAN) : false;

		// Service type (SERVICE, APPLICATION, ALIAS)
		$_properties['type']			= strtoupper($properties['type']);

		// Set supported http methods;
		$_properties['supported_http_methods']	= $properties['supported_http_methods'];

		// Content type (will ignore transport)
		$_properties['content_type']	= isset($properties['content_type']) ? $properties['content_type'] : '';

		// Generic description (internal use only)
		$_properties['description']		= isset($properties['description']) ? $properties['description'] : '';
		
		// If alias, service will point to:
		$_properties['alias_for']		= isset($properties['alias_for']) ? $properties['alias_for'] : '';
		
		// If application, service will invoke:
		$_properties['service_application']		= isset($properties['service_application']) ? $properties['service_application'] : '';
		$_properties['service_method']			= isset($properties['service_method']) ? $properties['service_method'] : '';
		
		// Cache control
		// Cache type:
		// 'SERVER' -> cache content on server using comodojo.cache method
		// 'CLIENT' -> send to the client cache timeout but keep service fresh server-side
		// 'BOTH'   -> enable both server and client caching
		// 'NONE'   -> disable both server and client caching
		$_properties['cache']			= isset($properties['cache']) ? $properties['cache'] : 'NONE';
		// Cache time to live (in seconds)
		$_properties['ttl']				= isset($properties['ttl']) ? filter_var($properties['ttl'], FILTER_VALIDATE_INT) : 0;
		
		// Set the ACAO directive. It's a comma separated list of origins (fqdn)
		$_properties['access_control_allow_origin']	= isset($properties['access_control_allow_origin']) ? $properties['access_control_allow_origin'] : '';
		
		// Array of required parameters; could be also an array of arrays as requested by func "attributes_to_parameters_match"
		$_properties['required_parameters']			= isset($properties['required_parameters']) ? (empty($properties['required_parameters']) ? Array() : explode(',',$properties['required_parameters'])) : Array();

		$fh = fopen($properties_file_name, 'w');
		if (!fwrite($fh, array2json($_properties))) {
			fclose($fh);
			throw new Exception("Error writing service properties", 2906);
		}
		fclose($fh);
		
		if ($_properties['type'] == 'SERVICE') {
			$fh = fopen($service_file_name, 'w');
			if (!fwrite($fh, stripcslashes($properties['service_file']))) {
				fclose($fh);
				unlink($properties_file_name);
				throw new Exception("Error writing service properties", 2906);
			}
		}

		return Array(
			"id"		=>	$_properties['name'],
			"name"		=>	$_properties['name'],
			"type"		=>	$_properties['type'],
			"enabled"	=>	$_properties['enabled']
		);

	}
	
	/**
	 * Modify exsisting service
	 * 
	 */
	public function edit_service($properties, $service=false) {

		if (empty($properties)) throw new Exception("Cannot find service properties file", 2902);

		if (!isset($properties['name'])) throw new Exception("Invalid properties for a service", 2904);

		try { $current = $this->get_service($properties['name']); } catch (Exception $e) { throw $e; }
		
		$current['old_type'] = $current['properties_file']['type'];

		$current['properties'] = Array();

		$current['properties']['name'] = $current['properties_file']['name'];

		$current['properties']['enabled']						= isset($properties['enabled']) ? filter_var($properties['enabled'], FILTER_VALIDATE_BOOLEAN) : $current['properties_file']['enabled'];
		$current['properties']['type']							= isset($properties['type']) ? strtoupper($properties['type']) : $current['properties_file']['type'];
		$current['properties']['supported_http_methods']		= isset($properties['supported_http_methods']) ? $properties['supported_http_methods'] : $current['properties_file']['supported_http_methods'];
		$current['properties']['content_type']					= isset($properties['content_type']) ? $properties['content_type'] : $current['properties_file']['content_type'];
		$current['properties']['description']					= isset($properties['description']) ? $properties['description'] : $current['properties_file']['description'];
		$current['properties']['alias_for']						= isset($properties['alias_for']) ? $properties['alias_for'] : $current['properties_file']['alias_for'];
		$current['properties']['service_application']			= isset($properties['service_application']) ? $properties['service_application'] : $current['properties_file']['service_application'];
		$current['properties']['service_method']				= isset($properties['service_method']) ? $properties['service_method'] : $current['properties_file']['service_method'];
		$current['properties']['cache']							= isset($properties['cache']) ? $properties['cache'] : $current['properties']['cache'];
		$current['properties']['ttl']							= isset($properties['ttl']) ? filter_var($properties['ttl'], FILTER_VALIDATE_INT) : $current['properties_file']['ttl'];
		$current['properties']['access_control_allow_origin']	= isset($properties['access_control_allow_origin']) ? $properties['access_control_allow_origin'] : $current['properties_file']['access_control_allow_origin'];
		$current['properties']['required_parameters']			= isset($properties['required_parameters']) ? (empty($properties['required_parameters']) ? $current['properties_file']['required_parameters'] : explode(',',$properties['required_parameters'])) : $current['properties_file']['required_parameters'];

		$current['file'] = ($current['properties']['type'] == "SERVICE" AND !isset($properties['service_file'])) ? $current['service_file'] : stripcslashes($properties['service_file']);

		$properties_file_name	= COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$properties['name'].'.properties';
		$service_file_name		= COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$properties['name'].'.service';

		$fh = fopen($properties_file_name, 'w');
		if (!fwrite($fh, array2json($current['properties']))) {
			fclose($fh);
			throw new Exception("Error writing service properties", 2906);
		}
		fclose($fh);
		
		if ($current['old_type'] == 'SERVICE' AND in_array($current['properties']['type'], Array('ALIAS','APPLICATION'))) {
			$_result = @unlink($service_file_name);
		}

		if ($properties['type'] == 'SERVICE') {
			$fh = fopen($service_file_name, 'w');
			if (!fwrite($fh, $current['file'])) {
				fclose($fh);
				unlink($properties_file_name);
				throw new Exception("Error writing service properties", 2906);
			}
		}

		return Array(
			"id"		=>	$current['properties']["name"],
			"name"		=>	$current['properties']["name"],
			"type"		=>	$current['properties']["type"],
			"enabled"	=>	$current['properties']["enabled"]
		);

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
			
			$_result = @unlink(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_SERVICE_FOLDER.$service.'.service');
			
			if (!$result) throw new Exception("Cannot delete service file", 2903);
			
		}
		else throw new Exception("Cannot find service properties file", 2902);

		return true;

	}	
/********************* PUBLIC METHODS ********************/
	
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_services_management
 */
function loadHelper_services_management() { return false; }

?>