<?php

/**
 * role_mapper.php
 * 
 * Return enabled applications list and info starting from user role (COMODOJO_ROLE_ID).
 * 
 * Applications' list is retrieved from local constant (COMODOJO_BOOTSTRAP) loaded from DB or cache.
 *
 * To override role_mapper and force a static roles configuration,
 * just fill the file roles_override.php in config folder.
 * 
 * There is a sample roles_override.php.sample in config folder; renaming it,
 * comodojo will start with default roles options.
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class role_mapper {
	
	/**
	 * Internal pointers to single app properties in cycle 
	 */
	private $currentApplicationSupportsLocalization = false;
	private $currentApplicationDefaultLocale = false;
	private $currentApplicationSupportedLocales = false;
	private $currentApplicationShouldStartOnBoot = false;

	private $override = false;	
	
	/**
	 * Array of applications that require autostart
	 */
	private $autoStart = Array();
	
	/**
	 * Array of application properties
	 */
	private $applicationsAllowed = Array();
	
	/**
	 * Array of user role's allowed application
	 */
	private $applicationsProperties = Array(); 
	
	/**
	 * Constructor: bootstrap from file or database and scan apps
	 * 
	 * @param	bool	$registerApplication	[optional] If true, role_mapper will register application allowed widely
	 */
	public final function __construct($registerApplications=true) {

		if (COMODOJO_STARTUP_CACHE_ENABLED) { 
			comodojo_load_resource('cache');
		}
		
		if ($this->bootstrap_from_override_file() !== false) {
			comodojo_debug('Starting comodojo role mapping (bootstrap) from override file','INFO','role_mapper');
			$this->bootstrap($this->override);
		}
		elseif (defined('COMODOJO_BOOTSTRAP')) {
			comodojo_debug('Starting comodojo role mapping (bootstrap) from startup','INFO','role_mapper');
			$this->bootstrap(json2array(COMODOJO_BOOTSTRAP));
		}
		else {
			comodojo_debug('Error mapping roles (bootstrapping)','ERROR','role_mapper');
			throw new Exception("Error mapping roles (bootstrapping)", 1301);
		}
		//if ($registerApplications) define('COMODOJO_APPLICATION_ALLOWED',$this->get_allowed_applications());
		if ($registerApplications) $GLOBALS['COMODOJO_APPLICATION_ALLOWED'] = $this->get_allowed_applications();
		
	}
	
	public final function get_allowed_applications() {
		return $this->applicationsAllowed;
	}
	
	public final function get_autostart() {
		return $this->autoStart;
	}
	
	public final function get_applications_properties() {
		return $this->applicationsProperties;
	}
	
	private function get_properties($application, $custom_properties) {
		
		$propertiesFile = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER . $application . "/" . $application . ".properties";
		$resourcesPath = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER . $application . "/resources/";

		if (is_readable($propertiesFile)) {
			
			include($propertiesFile);
			
			$this->currentApplicationSupportsLocalization = $properties["localizationSupport"];
			$this->currentApplicationSupportedLocales = $properties["supportedLocales"];
			$this->currentApplicationDefaultLocale = $properties["defaultLocale"];
			//set some values from locale
			$properties["title"] = $properties["title"][(in_array(COMODOJO_CURRENT_LOCALE,$this->currentApplicationSupportedLocales) ? COMODOJO_CURRENT_LOCALE : $this->currentApplicationDefaultLocale)];
			$properties["description"] = $properties["description"][(in_array(COMODOJO_CURRENT_LOCALE,$this->currentApplicationSupportedLocales) ? COMODOJO_CURRENT_LOCALE : $this->currentApplicationDefaultLocale)];

			if (is_null($custom_properties)) {
				$this->currentApplicationShouldStartOnBoot = $properties["autoStart"];
			}
			else {
				$this->currentApplicationShouldStartOnBoot = isset($custom_properties['autoStart']) ? filter_var($custom_properties['autoStart'], FILTER_VALIDATE_BOOLEAN) : $properties["autoStart"];
				$properties["type"] = isset($custom_properties['type']) ? $custom_properties['type'] : $properties["type"];
				$properties["attachNode"] = isset($custom_properties['attachNode']) ? $custom_properties['attachNode'] : $properties["attachNode"];
				$properties["requestSpecialNode"] = isset($custom_properties['requestSpecialNode']) ? $custom_properties['requestSpecialNode'] : $properties["requestSpecialNode"];
				$properties["placeAt"] = isset($custom_properties['placeAt']) ? $custom_properties['placeAt'] : $properties["placeAt"];
				$properties["width"] = isset($custom_properties['width']) ? $custom_properties['width'] : $properties["width"];
				$properties["height"] = isset($custom_properties['height']) ? $custom_properties['height'] : $properties["height"];
				$properties["resizable"] = isset($custom_properties['resizable']) ? filter_var($custom_properties['resizable'], FILTER_VALIDATE_BOOLEAN) : $properties["resizable"];
				$properties["maxable"] = isset($custom_properties['maxable']) ? filter_var($custom_properties['maxable'], FILTER_VALIDATE_BOOLEAN) : $properties["maxable"];
			}
			
			return $properties;
			
		}
		
		else return false;
		
	}
	
	private function get_localization($application) {
		
		$localeFile = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER . $application . "/i18n/" . (in_array(COMODOJO_CURRENT_LOCALE,$this->currentApplicationSupportedLocales) ? COMODOJO_CURRENT_LOCALE : $this->currentApplicationDefaultLocale) . ".lang";
		
		if (is_readable($localeFile)) {
			
			include($localeFile);
			
			return $lang;
			
		}
		elseif (is_readable(COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER . $application . "/i18n/" . $this->currentApplicationDefaultLocale . ".lang")) {
			
			include($localeFile);
			
			return $lang;
			
		}
		else return false;
		
	}
	
	private function bootstrap_from_override_file() {
		
		if (is_readable(COMODOJO_CONFIGURATION_FOLDER.'bootstrap_override.php')) {
			require(COMODOJO_CONFIGURATION_FOLDER.'bootstrap_override.php');
			$this->override = $bootstrapParameters;
			return true;
		}
		else return false;
		
	}
	
	private function bootstrap($from) {
		
		$userRole = is_null(COMODOJO_USER_ROLE) ? 0 : COMODOJO_USER_ROLE;
		
		$userLevel = isset($from[$userRole]) ? $from[$userRole] : Array();
		$persistent = isset($from['persistent']) ? $from['persistent'] : Array();
		
		$applications = Array();
		
		if (COMODOJO_STARTUP_CACHE_ENABLED) { 
		
			$c = new cache();	
			$request = md5(COMODOJO_UNIQUE_IDENTIFIER).'_BOOTSTRAP_ROLE_'.COMODOJO_USER_ROLE;
			$cache = $c->get_cache($request, 'JSON', false);

			if ($cache !== false) {
				comodojo_debug('Comodojo role mapping (bootstrap) will use startup cache','INFO','role_mapper');
				$this->autoStart = $cache[2]['auto_start'];
				$this->applicationsAllowed = $cache[2]['applications_allowed'];
				$this->applicationsProperties = $cache[2]['applications_properties'];
				return;
			}

		}
		
		foreach($userLevel as $key => $val) {
			
			if (is_array($val)) {

				if ( !isset($val["name"]) OR !isset($val["properties"]) ) {
					comodojo_debug('Error loading level '.$userRole.' application '.$val.'. Skipping.','ERROR','role_mapper');
					continue;
				}

				$p = $this->get_properties($val["name"], $val["properties"]);
				if ($p === false){
					comodojo_debug('Error loading level '.$userRole.' application '.$val.'. Skipping.','ERROR','role_mapper');
					continue;
				}

				$applications[$val["name"]]["properties"] = $p;
			
				if ($this->currentApplicationSupportsLocalization) {
					$l = $this->get_localization($val["name"]);
					$applications[$val["name"]]["i18n"] = $l;
				}
				
				if ($this->currentApplicationShouldStartOnBoot) {
					array_push($this->autoStart, $val["name"]);
				}
				
				array_push($this->applicationsAllowed, $val["name"]);

			}
			else {

				$p = $this->get_properties($val, null);
				if ($p === false){
					comodojo_debug('Error loading level '.$userRole.' application '.$val.'. Skipping.','ERROR','role_mapper');
					continue;
				}
				
				$applications[$val]["properties"] = $p;
				
				if ($this->currentApplicationSupportsLocalization) {
					$l = $this->get_localization($val);
					$applications[$val]["i18n"] = $l;
				}
				
				if ($this->currentApplicationShouldStartOnBoot) {
					array_push($this->autoStart, $val);
				}
				
				array_push($this->applicationsAllowed, $val);

			}
			
		}
		
		foreach($persistent as $key => $val) {
			
			if (is_array($val)) {

				if ( !isset($val["name"]) OR !isset($val["properties"]) ) {
					comodojo_debug('Error loading persistent application '.$val.'. Skipping.','ERROR','role_mapper');
					continue;
				}

				$p = $this->get_properties($val["name"], $val["properties"]);
				if ($p === false){
					comodojo_debug('Error loading persistent application '.$val.'. Skipping.','ERROR','role_mapper');
					continue;
				}

				$applications[$val["name"]]["properties"] = $p;
			
				if ($this->currentApplicationSupportsLocalization) {
					$l = $this->get_localization($val["name"]);
					$applications[$val["name"]]["i18n"] = $l;
				}
				
				if ($this->currentApplicationShouldStartOnBoot) {
					array_push($this->autoStart, $val["name"]);
				}
				
				array_push($this->applicationsAllowed, $val["name"]);

			}
			else {

				$p = $this->get_properties($val, null);
				if ($p === false){
					comodojo_debug('Error loading persistent application '.$val.'. Skipping.','ERROR','role_mapper');
					continue;
				}
				
				$applications[$val]["properties"] = $p;
				
				if ($this->currentApplicationSupportsLocalization) {
					$l = $this->get_localization($val);
					$applications[$val]["i18n"] = $l;
				}
				
				if ($this->currentApplicationShouldStartOnBoot) {
					array_push($this->autoStart, $val);
				}
				
				array_push($this->applicationsAllowed, $val);

			}
			
		}
		
		$this->applicationsProperties = $applications;

		if (COMODOJO_STARTUP_CACHE_ENABLED) {

			$c->set_cache(Array(
				'auto_start'			 =>$this->autoStart,
				'applications_allowed'	 =>$this->applicationsAllowed,
				'applications_properties'=>$this->applicationsProperties
			), $request, 'JSON', false);

		}
		
	}
	
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_role_mapper
 */
function loadHelper_role_mapper() { return false; }

?>