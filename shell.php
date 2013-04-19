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

require 'comodojo/global/comodojo_basic.php';

class shell extends comodojo_basic {
	
	public $script_name = 'shell.php';
	
	public $use_session_transport = true;
	
	public $require_valid_session = false;
	
	public $do_authentication = true;
	
	public function logic($attributes) {
		
		comodojo_load_resource('events');
		$events = new events();
		
		$this->header_params['statusCode'] = 200;
		$this->header_params['contentType'] = 'text/html';
		$this->header_params['charset'] = COMODOJO_DEFAULT_ENCODING;
		
		return COMODOJO_SHELL_ENABLED ? $this->start_shell($attributes) : $this->stop_shell($attributes);
		
	}
	
	private function start_shell($attributes) {
		
		$loader = "
			<link rel=\"stylesheet\" type=\"text/css\" href=\"comodojo/javascript/dojo/resources/dojo.css\" />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"comodojo/javascript/dijit/themes/" . COMODOJO_SITE_THEME_DOJO . "/" . COMODOJO_SITE_THEME_DOJO . ".css\" />
			<script type=\"text/javascript\">
				var dojoConfig = {
					async: true,
					parseOnLoad: false,
					baseUrl: '" . COMODOJO_JS_BASE_URL . "',";
		if (COMODOJO_JS_XD_LOADING) {
		$loader .= "
					dojoBlankHtmlUrl: '" . (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . COMODOJO_JS_BASE_URL . "resources/blank.html',
					packages: [{
						name: 'comodojo',
						location: '" . (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . "comodojo/javascript/comodojo'
					}],
					waitSeconds: '" . COMODOJO_JS_XD_TIMEOUT . "',";
		}
		$loader .= "
					locale: '" . $this->locale . "',
					has: {
						'dojo-amd-factory-scan': false,
						'dojo-firebug': ".(COMODOJO_JS_DEBUG ? "true" : "false").",
						'dojo-debug-messages': ".(COMODOJO_JS_DEBUG_DEEP ? "true" : "false").",
						'popup': ".(COMODOJO_JS_DEBUG_POPUP ? "true" : "false")."
					}
				};
			</script>
			<script type=\"text/javascript\" src=\"" . (COMODOJO_JS_XD_LOADING ? COMODOJO_JS_XD_LOCATION : 'comodojo/javascript/dojo/dojo.js') . "\"></script>
			<script type=\"text/javascript\">
				require([\"dojo/ready\",\"comodojo/Shell\"], function(ready,shell){
					ready(function(){
						var s = new shell({
							shellNode: 'shell_main',
							shellLoader: 'shell_loader',
							userName: '".COMODOJO_USER_NAME."',
							userRole: '".COMODOJO_USER_ROLE."',
							siteName: '".strtolower(str_replace (" ", "", COMODOJO_SITE_TITLE))."',
							clientIP: '".$_SERVER["REMOTE_ADDR"]."'
						});
					});
				});
			</script>
		";
		
		$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/templates/web_shell.html");
		
		$index = str_replace("*_SITETITLE_*",COMODOJO_SITE_TITLE,$index);
		$index = str_replace("*_DEFAULTENCODING_*",strtolower(COMODOJO_DEFAULT_ENCODING),$index);
		$index = str_replace("*_DOJOTHEME_*",COMODOJO_SITE_THEME_DOJO,$index);
		$index = str_replace("*_DOJOLOADER_*",$loader,$index);
		
		return $index;
		
	}
	
	private function stop_shell($attributes) {
		
		$loader = "
			<link rel=\"stylesheet\" type=\"text/css\" href=\"comodojo/javascript/dojo/resources/dojo.css\" />
			<link rel=\"stylesheet\" type=\"text/css\" href=\"comodojo/javascript/dijit/themes/" . COMODOJO_SITE_THEME_DOJO . "/" . COMODOJO_SITE_THEME_DOJO . ".css\" />
			<script type=\"text/javascript\">
				var dojoConfig = {
					async: true,
					parseOnLoad: false,
					baseUrl: '" . COMODOJO_JS_BASE_URL . "',";
		if (COMODOJO_JS_XD_LOADING) {
			$loader .= "
					dojoBlankHtmlUrl: '" . (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . COMODOJO_JS_BASE_URL . "resources/blank.html',
					packages: [{
						name: 'comodojo',
						location: '" . (is_null(COMODOJO_SITE_EXTERNAL_URL) ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL) . "comodojo/javascript/comodojo'
					}],
					waitSeconds: '" . COMODOJO_JS_XD_TIMEOUT . "',";
		}
		$loader .= "
					locale: '" . $this->locale . "',
					has: {
						'dojo-amd-factory-scan': false,
						'dojo-firebug': ".(COMODOJO_JS_DEBUG ? "true" : "false").",
						'dojo-debug-messages': ".(COMODOJO_JS_DEBUG_DEEP ? "true" : "false").",
						'popup': ".(COMODOJO_JS_DEBUG_POPUP ? "true" : "false")."
					}
				};
			</script>
			<script type=\"text/javascript\" src=\"" . (COMODOJO_JS_XD_LOADING ? COMODOJO_JS_XD_LOCATION : 'comodojo/javascript/dojo/dojo.js') . "\"></script>
			<script type=\"text/javascript\">
				require([\"dojo/ready\",\"dojo/dom\"], function(ready,dom){
					ready(function(){
						dom.byId('shell_main').innerHTML = 'Shell disabled';
					});
				});
			</script>
		";
		
		$index = file_get_contents(COMODOJO_SITE_PATH . "comodojo/templates/web_shell.html");
		
		$index = str_replace("*_SITETITLE_*",COMODOJO_SITE_TITLE,$index);
		$index = str_replace("*_DEFAULTENCODING_*",strtolower(COMODOJO_DEFAULT_ENCODING),$index);
		$index = str_replace("*_DOJOTHEME_*",COMODOJO_SITE_THEME_DOJO,$index);
		$index = str_replace("*_DOJOLOADER_*",$loader,$index);
		
		return $index;
		
	}
	
}

$s = new shell();

?>