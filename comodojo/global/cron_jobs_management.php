<?php

/**
 * Basic functions to create, delete, edit, run comodojo CRON jobs
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class cron_jobs_management {

/********************** PRIVATE VARS *********************/
	/**
	 * Restrict management to administrator.
	 * 
	 * If disabled, it will not check user role (=1).
	 * 
	 * @default true;
	 */
	private $restrict_management_to_administrators = true;

	/**
	 * Reserved service names
	 */
	private $reserved_jobs = Array('services','service','cronrootnode','cron','application','method'); 
/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * List registered cron jobs and existing jobs.
	 * 
	 * @return	array	
	 */
	public function get_cron_and_jobs() {
	 	
		comodojo_load_resource('database');

		$crons = Array();
		$jobs = Array();
		
    	$cron_path = opendir(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER);

    	try {
			$db = new database();
			$result = $db->table("cron")->keys(Array("id","name","enabled"))->get();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}
		
		$crons = $result['result'];

		while(false !== ($cron_item = readdir($cron_path))) {

			$cron_file_properties = pathinfo($cron_path.$cron_item);

			if (!is_dir($cron_item) AND $cron_file_properties['extension'] == 'php' AND $cron_file_properties['basename'][0] != '.' ) {
				array_push($jobs, $cron_file_properties['filename']);
			}
			else {
				continue;
			}

        }

		closedir($cron_path);
		
		return Array(
			"cron"	=>	$crons,
			"jobs"	=>	$jobs
		);
		
	}
	
/********************* PUBLIC METHODS ********************/
	
}

function loadHelper_cron_jobs_management() { return false; }

?>