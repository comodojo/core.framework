<?php

class stage extends stage_base {

	public $out = Array();

	private function _createFile($name, $content) {
		$fCreate = fopen($name, "w");
		$result = fwrite($fCreate, $content);
		fclose($fCreate);
		return !$result ? false : true;
	}

	private function _unlinkFolder($folder) {
		//variation of lixlpixel "recursive_remove_directory" function
		$_folder = substr($folder,-1) == '/' ? substr($folder,0,-1) : $folder;
		if(!file_exists($_folder) || !is_dir($_folder)) $toReturn = true;
		elseif(!is_readable($_folder)) $toReturn = false;
		else {
			$handle = opendir($_folder);
			while (false !== ($item = readdir($handle))) {
				if($item != '.' && $item != '..') {
					$path = $_folder.'/'.$item;
					if(is_dir($path)) $this->_unlinkFolder($path);
					else unlink($path);
				}
			}
			closedir($handle);
			$toReturn = !rmdir($_folder) ? false : true;
		}
		return $toReturn;
	}

	public function create_tables($drop,$create,$fill) {

		try {
			
			$db = new database(
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_HOST'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_DATA_MODEL'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_NAME'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PORT'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PREFIX'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_USER'],
				$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PASSWORD']
			);
			
			foreach ($drop as $table) {
				$db->table($table)->drop_table(true);
				$db->clean();
			}

			foreach ($create as $table => $values) {
				$db->table($table);
				foreach ($values['columns'] as $column) {
					$db->column($column[0],$column[1],$column[2]);
				}
				$qry = $db->create_table($table);
				$db->clean();
			}

			foreach ($fill as $values) {
				$db->table($values[0])->values($values[1])->store();
				$db->clean();
			}
			
		} catch (Exception $e) {
			return $e->getMessage();
		}
		
		return true;

	}

	public function create_home() {

		$root = COMODOJO_SITE_PATH . $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['HOME_FOLDER'];
	
		if (
			!$this->_unlinkFolder($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER']) OR
			!$this->_unlinkFolder($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['TEMP_FOLDER']) OR
			!$this->_unlinkFolder($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['FILESTORE_FOLDER']) OR
			!$this->_unlinkFolder($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CACHE_FOLDER']) OR
			!$this->_unlinkFolder($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['THUMBNAILS_FOLDER']) OR
			!$this->_unlinkFolder($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SERVICE_FOLDER']) OR
			!$this->_unlinkFolder($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_FOLDER'])
		) return false;
		
		if (
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER']) OR 
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['TEMP_FOLDER']) OR 
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['FILESTORE_FOLDER']) OR 
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CACHE_FOLDER']) OR 
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['THUMBNAILS_FOLDER']) OR 
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SERVICE_FOLDER']) OR 
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_FOLDER']) OR
			
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER'].$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER']) OR
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER'].'guest') OR
			!mkdir($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER'].'shared')
		) return false;
			
		if (
			!$this->_createFile($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER']."._.acl",
				'{"owners":["nobody"],"readers":["everybody"],"writers":["'.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'].'"]}') OR
			!$this->_createFile($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER']."._".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'].".acl",
				'{"owners":["nobody"],"readers":["'.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'].'"],"writers":["'.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'].'"]}') OR
			!$this->_createFile($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER']."._guest.acl",
				'{"owners":["nobody"],"readers":["everybody"],"writers":["everybody"]}') OR
			!$this->_createFile($root.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER'].
				"._shared.acl", '{"owners":["nobody"],"readers":["everybody"],"writers":["everybody"]}')
		) return false;
		
		return true;
	}

	public function create_configuration() {
	
		global $comodojoCustomization;
		$_locale = false;
		foreach($comodojoCustomization['supportedLocales'] as $locale) {
			if (!$_locale) {
				$_locale = $locale['value']."";
			}
			else {
				$_locale .= ",".$locale['value'];
			}
		}
		$myFileData = "<?php

/**
 * static_configuration.php
 * 
 * Basic static configuration parameters for comodojo
 * 
 * WARNING: changing something here could hang comodojo installation;
 * 			please modify values ONLY IF you know what you're doing. 
 * 
 * @package		Comodojo Configuration
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

/**
 * Identifiers
 */
define('COMODOJO_UNIQUE_IDENTIFIER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['UNIQUE_IDENTIFIER']."');
define('COMODOJO_PUBLIC_IDENTIFIER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['PUBLIC_IDENTIFIER']."');
define('COMODOJO_SESSION_IDENTIFIER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SESSION_IDENTIFIER']."');

/**
 * Folders
 */
define('COMODOJO_CONFIGURATION_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CONFIGURATION_FOLDER']."');
define('COMODOJO_HOME_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['HOME_FOLDER']."');
define('COMODOJO_USERS_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['USERS_FOLDER']."');
define('COMODOJO_TEMP_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['TEMP_FOLDER']."');
define('COMODOJO_FILESTORE_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['FILESTORE_FOLDER']."');
define('COMODOJO_CACHE_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CACHE_FOLDER']."');
define('COMODOJO_THUMBNAILS_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['THUMBNAILS_FOLDER']."');
define('COMODOJO_APPLICATION_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['APPLICATION_FOLDER']."');
define('COMODOJO_SERVICE_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SERVICE_FOLDER']."');
define('COMODOJO_CRON_FOLDER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CRON_FOLDER']."');

/**
 * Basic database configuration
 */
define('COMODOJO_DB_HOST','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_HOST']."');
define('COMODOJO_DB_PORT',".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PORT'].");
define('COMODOJO_DB_NAME','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_NAME']."');
define('COMODOJO_DB_USER','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_USER']."');
define('COMODOJO_DB_PASSWORD','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PASSWORD']."');
define('COMODOJO_DB_PREFIX','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_PREFIX']."');

/**
 * Database Data Model
 */
define('COMODOJO_DB_DATA_MODEL','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['DB_DATA_MODEL']."');

/**
 * Supported Locales
 */
define('COMODOJO_SUPPORTED_LOCALES','".$_locale."');

/**
 * Enable/disable startup cache
 */
define('COMODOJO_STARTUP_CACHE_ENABLED',".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['STARTUP_CACHE_ENABLED'].");

/**
 * Enable global debug
 * True will produce large amount of data in error_log! 
 */	
define('COMODOJO_GLOBAL_DEBUG_ENABLED',".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GLOBAL_DEBUG_ENABLED'].");
define('COMODOJO_GLOBAL_DEBUG_LEVEL','".$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['GLOBAL_DEBUG_LEVEL']."');
define('COMODOJO_GLOBAL_DEBUG_FILE',null);

?>";

		if (!$this->_createFile(COMODOJO_SITE_PATH.$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['CONFIGURATION_FOLDER']."static_configuration.php", $myFileData)) return false;
		
		return true;
			
	}

	public function report_result($condition, $success, $failure, $block=false) {
		if (!$condition) {
			array_push($this->out, array("type"=>!$block ? 'warning' : 'error',"content"=>$failure));
			if ($block) {
				$this->next_button_disabled = true;
				$this->next_button_label = $this->i18n['0120'];
			}
		}
		else {
			array_push($this->out, array("type"=>'success',"content"=>$success));
			if ($block) {
				$this->next_button_disabled = false;
				$this->next_button_label = $this->i18n['0122'];
				array_push($this->out, array(
					"type"			=>	"Button",
					"label"			=>	$this->i18n["0122"],
					"onClick"		=>	"installer._goToPortal('".COMODOJO_SITE_URL."');",
					"disabled"		=>	false
				));
			}
		}
	}

	public function output() {

		define('COMODOJO_HOME_FOLDER',$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['HOME_FOLDER']);
		define('COMODOJO_FILESTORE_FOLDER',$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['FILESTORE_FOLDER']);
	
		require(COMODOJO_SITE_PATH . "comodojo/installer/tables.php");

		$this->back_button_disabled = true;

		$tbls = $this->create_tables($drop,$create,$fill);
		$home = $this->create_home();
		$conf = $this->create_configuration();

		$this->report_result($tbls===true,$this->i18n["0113"],$this->i18n["0114"].$tbls,false);
		$this->report_result($home===true,$this->i18n["0115"],$this->i18n["0116"],false);
		$this->report_result($conf===true,$this->i18n["0117"],$this->i18n["0118"],false);

		$this->report_result(($tbls AND $home AND $conf),
			$this->i18n["0121"] . "<div style=\"text-align: center; color: red; padding: 4px; font-size: x-large; font-weight: bold;\">" . $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'] . "/" . $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_PASSWORD'] . "</div>",
			$this->i18n["0119"],true);

		return $this->out;
	}			

}

?>