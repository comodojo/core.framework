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
	
	private function get_properties($application) {
		
		$propertiesFile = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER . $application . "/" . $application . ".properties";
		$resourcesPath = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER . $application . "/resources/";

		if (is_readable($propertiesFile)) {
			
			include($propertiesFile);
			
			$this->currentApplicationSupportsLocalization = $properties["localizationSupport"];
			$this->currentApplicationSupportedLocales = $properties["supportedLocales"];
			$this->currentApplicationDefaultLocale = $properties["defaultLocale"];
			$this->currentApplicationShouldStartOnBoot = $properties["autoStart"];
			
			//set some values from locale
			$properties["title"] = $properties["title"][(in_array(COMODOJO_CURRENT_LOCALE,$this->currentApplicationSupportedLocales) ? COMODOJO_CURRENT_LOCALE : $this->currentApplicationDefaultLocale)];
			$properties["description"] = $properties["description"][(in_array(COMODOJO_CURRENT_LOCALE,$this->currentApplicationSupportedLocales) ? COMODOJO_CURRENT_LOCALE : $this->currentApplicationDefaultLocale)];
			
			/*
			if (!$properties["iconSrc"] AND 
				is_readable($resourcesPath.'icon_64.png') AND
				is_readable($resourcesPath.'icon_32.png') AND
				is_readable($resourcesPath.'icon_16.png')
			) $properties["iconSrc"] = 'SELF';
			else ... 
			*/
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
		
		foreach($userLevel as $key => $val) {
			
			$p = $this->get_properties($val);
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
		
		foreach($persistent as $key => $val) {
			
			$p = $this->get_properties($val);
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
		
		$this->applicationsProperties = $applications;
		
	}
	
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_role_mapper
 */
function loadHelper_role_mapper() { return false; }

?>