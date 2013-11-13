<?php

/** 
 * startup.php
 * 
 * backend class in charge of building environment & controls for a new instance of Comodojo;
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

require 'comodojo_basic.php';

class comodojo_startup extends comodojo_basic {
	
	public $use_session_transport = true;
	
	public $require_valid_session = false;
	
	public $do_authentication = true;
	
	private $meta = '';
	
	private $js_loader = false;

	private $current_message_code = null;
	private $current_message_values = null;
	
	public function logic($attributes) {
		
		comodojo_load_resource('events');
		$events = new events();
		
		//if(isset($attributes['userName']) AND isset($attributes['userPass'])) $this->auth_login($attributes['userName'], $attributes['userPass']);
		
		try {
			$events->record('site_hit', COMODOJO_USER_NAME);
		}
		catch(Exception $e) {
			comodojo_debug("There was a problem recording event 'site_hit': ".$e->getMessage(),'WARNING','startup');
		}
		
		$this->evalMeta();
		
		$this->header_params['statusCode'] = 200;
		$this->header_params['contentType'] = 'text/html';
		$this->header_params['charset'] = COMODOJO_DEFAULT_ENCODING;
		
		$suspended = $this->shouldSuspend($attributes);
		if ($suspended !== false) {
			return $suspended;
		}

		if (isset($attributes['application']) AND @$attributes['application'] == 'comodojo' AND isset($attributes['method']) 
			AND (@$attributes['method'] == "confirmRegistration" OR @$attributes['method'] == "passwordRecovery")) 
		{
			try {
				$this->app_exec = COMODOJO_SITE_PATH.'comodojo/global/comodojo_reserved.php';
				require $this->app_exec;
				$this->app_run = new comodojo_reserved();
				$method = $this->app_run->get_registered_method($attributes['method']);
				if (!attributes_to_parameters_match($attributes, $method[1])) {
					$this->current_message_code = 10026;
					$this->current_message_values = "'Conversation error.'";
				}
				else {
					$message_values = $this->app_run->$method[0]($attributes);
					if ($attributes['method'] == "confirmRegistration") {
						$this->current_message_code = 10025;
						$this->current_message_values = "'".$message_values['completeName']."','".$message_values['userName']."'";
					}
					else {
						$this->current_message_code = 10027;
						$this->current_message_values = "'".$message_values['userName']."','".$message_values['email']."'";
					}
				}
			} catch (Exception $e) {
				$this->current_message_code = 10026;
				$this->current_message_values = '"'.$e->getMessage().'"';
			}
		}

		$this->buildJsLoader($attributes);
		
		return $this->set_template();
		
	}
	
	private function evalMeta() {
		
		$metaTags = json2array(COMODOJO_SITE_TAGS);
		
		foreach ($metaTags as $metaTag) {
			if ($metaTag["content"] != "") $this->meta .= "<meta name=\"".$metaTag["name"]."\" content=\"".$metaTag["content"]."\"/>\n";
		}
		
	}
	
	private function shouldSuspend($attributes) {
		
		if (COMODOJO_SITE_SUSPENDED AND COMODOJO_USER_ROLE != 1) {
			
			$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/templates/web_suspended.html");
			
			$index = str_replace("*_SITETITLE_*",COMODOJO_SITE_TITLE,$index);
			$index = str_replace("*_META_*",$this->meta,$index);
			$index = str_replace("*_SUSPENDEDMESSAGE_*",COMODOJO_SITE_SUSPENDED_MESSAGE,$index);
			$index = str_replace("*_SITEDATE_*",date("Y",strtotime(COMODOJO_SITE_DATE)),$index);
			$index = str_replace("*_SITEAUTHOR_*",COMODOJO_SITE_AUTHOR,$index);
			
			if(isset($attributes['userName']) AND isset($attributes['userPass'])) $index = str_replace("*_DISPLAY_*",'block',$index);
			else $index = str_replace("*_DISPLAY_*",'none',$index);
			
			return $index;
			
		}
		else return false;

	}

	private function buildJsLoader($attributes) {
		
		//****** CSS ******
		$myCssLoader = "
		<link rel=\"stylesheet\" type=\"text/css\" href=\"comodojo/javascript/dojo/resources/dojo.css\" />
		<link rel=\"stylesheet\" type=\"text/css\" href=\"comodojo/javascript/dijit/themes/" . COMODOJO_SITE_THEME_DOJO . "/" . COMODOJO_SITE_THEME_DOJO . ".css\" />
		";
		//*****************
		
		//****** VAR SCRIPT ******
		$myJsLoader = "
			<script type=\"text/javascript\">
			var dojoConfig = {
				async: false,
				parseOnLoad: false,
				baseUrl: '" . COMODOJO_JS_BASE_URL . "',";
		if (COMODOJO_JS_XD_LOADING) {
		$myJsLoader .= "
				dojoBlankHtmlUrl: '" . (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . COMODOJO_JS_BASE_URL . "resources/blank.html',
				packages: [{
					name: 'comodojo',
					location: '" . (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . "comodojo/javascript/comodojo'
				}],
				waitSeconds: '" . COMODOJO_JS_XD_TIMEOUT . "',";
		}
		$myJsLoader .= "
				locale: '" . $this->locale . "',
				has: {
					'dojo-amd-factory-scan': false,
					'dojo-firebug': ".(COMODOJO_JS_DEBUG ? "true" : "false").",
					'dojo-debug-messages': ".(COMODOJO_JS_DEBUG_DEEP ? "true" : "false").",
					'popup': ".(COMODOJO_JS_DEBUG_POPUP ? "true" : "false")."
				}
			};
			
			var comodojoConfig = {
					version: '" . comodojo_version('VERSION') . "',
					debug: " . (COMODOJO_JS_DEBUG ? "true" : "false") . ",
					debugDeep: " . (COMODOJO_JS_DEBUG_DEEP ? "true" : "false") . ",
					userName: " . (is_null(COMODOJO_USER_NAME) ? "false" : "'".COMODOJO_USER_NAME."'") . ",
					userCompleteName: " . (is_null(COMODOJO_USER_COMPLETE_NAME) ? "false" : "'".COMODOJO_USER_COMPLETE_NAME."'") . ",
					userRole: " . (is_null(COMODOJO_USER_ROLE) ? 0 : COMODOJO_USER_ROLE) . ",
					registrationMode: ".COMODOJO_REGISTRATION_MODE.",
					locale: '" . $this->locale . "',
					phpLocale: '" . $this->locale . "',
					queryString: " . array2json($attributes) . ",
					dojoTheme: '" . COMODOJO_SITE_THEME_DOJO . "',
					defaultContainer: '" . COMODOJO_SITE_DEFAULT_CONTAINER . "',
					serverTimezoneOffset: '" . getServerTimezoneOffset() . "',
					siteUrl: '" . getSiteUrl() . "'";
		if ($this->unsupported_locale != false) {
			$myJsLoader .= ",
					unsupportedLocale: '" . $this->unsupported_locale . "'";
		}
		$myJsLoader .="
				}
			</script>
			<script type=\"text/javascript\" src=\"" . (COMODOJO_JS_XD_LOADING ? COMODOJO_JS_XD_LOCATION : 'comodojo/javascript/dojo/dojo.js.uncompressed.js') . "\"></script>
		";
		//************************
		
		//****** DOJO.JS ******
		/*if (COMODOJO_JS_XD_LOADING) {
			$myJsLoader .= '
			<script type="text/javascript" src="' . COMODOJO_JS_XD_LOCATION . '"></script>
			<script type="text/javascript">
				dojo.registerModulePath("custom", "../custom");
			</script>
			';
		}
		else {
			$myJsLoader .= '
			<script type="text/javascript" src="comodojo/javascript/dojo/dojo.js" ></script>
			<script type="text/javascript">
				dojo.registerModulePath("custom", "../custom");
			</script>
			';
		}*/
		//*********************
		
		//****** DOJO REQUIRES ******
		$myJsLoader .= "
			<script type=\"text/javascript\">
				//dojo.require('comodojo.Notification');
				dojo.require('comodojo.Config');
				dojo.require('comodojo.Basic');
		";
		
		$myDojoRequires = json2array(COMODOJO_JS_REQUIRES);
		
		foreach ($myDojoRequires as $dr) {
				
			$myJsLoader .= "
				dojo.require(\"" . $dr["name"] . "\");";
			
			if (!empty($dr["extraCSS"])) {
				$myCssLoader .= "\n
		<link rel=\"stylesheet\" type=\"text/css\" href=\"". $dr["extraCSS"] . "\" />
				";
			}
		}
		//<script type=\"text/javascript\" src=\"comodojo/javascript/resources/comodojo.js\" ></script>
		$myJsLoader .= "
			</script>
			<script type=\"text/javascript\">
				dojo.ready(function() {
					comodojo.startup();";
		if ($this->current_message_code != null) {
			$myJsLoader .= "
					var notifier  = new comodojo.Notification();
					notifier.notify(comodojo.getLocalizedMutableMessage('".$this->current_message_code."',[".$this->current_message_values."]));
			";
		}
		
		$myJsLoader .= "					
				});
			</script>
		";
		//***************************
		
		//****** FINALY COMPOSE $THIS ******
		$this->js_loader = $myCssLoader . $myJsLoader;
		//**********************************
		
	}
	
	private function set_template() {
		
		$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/themes/" . COMODOJO_SITE_THEME . "/theme.html");
		
		//if (!COMODOJO_GLOBAL_DEBUG_ENABLED) $index = preg_replace('/<!--(.|\s)*?-->/', '', $index);
		
		$index = str_replace("*_SITETITLE_*",COMODOJO_SITE_TITLE,$index);
		$index = str_replace("*_SITEDESCRIPTION_*",COMODOJO_SITE_DESCRIPTION,$index);
		$index = str_replace("*_SITEDATE_*",date("Y",strtotime(COMODOJO_SITE_DATE)),$index);
		$index = str_replace("*_SITEAUTHOR_*",COMODOJO_SITE_AUTHOR,$index);
		$index = str_replace("*_DEFAULTENCODING_*",strtolower(COMODOJO_DEFAULT_ENCODING),$index);
		$index = str_replace("*_META_*",$this->evalMeta(),$index);
		$index = str_replace("*_DOJOTHEME_*",COMODOJO_SITE_THEME_DOJO,$index);
		$index = str_replace("*_DOJOLOADER_*",$this->js_loader,$index);
		$index = str_replace("*_CONTENT_*",'<div id="'.COMODOJO_SITE_DEFAULT_CONTAINER.'"></div>',$index);
		
		return $index;
		
	}
	
}

?>