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
		
		$jobs_path = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER;

		$jobs_items = scandir($jobs_path);

		foreach ($jobs_items as $jobs_item) {
			$job_file_properties = pathinfo($jobs_path.$jobs_item);
			if (!is_dir($cron_path.$cron_item) AND $job_file_properties['extension'] == 'php' AND $job_file_properties['basename'][0] != '.' ) {
				array_push($jobs, Array(
					"id"	=> $job_file_properties['filename'],
					"name"	=> $job_file_properties['filename']
				));
			}
			else {
				continue;
			}
		}

		try {
			$db = new database();
			$result = $db->table("cron")->keys(Array("id","name","enabled"))->get();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}
		
		$crons = $result['result'];

		return Array(
			"cron"	=>	$crons,
			"jobs"	=>	$jobs
		);
		
	}
	
	public function open_job($job_name) {

		if (empty($job_name)) {
			comodojo_debug('Error retrieving job: empty job file','ERROR','cron_jobs_management');
			throw new Exception("Unreadable job file", 2901);
		}

		$job = file_get_contents(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job_name.'.php');

		if (!$job) {
			comodojo_debug('Error retrieving job: unreadable job file','ERROR','cron_jobs_management');
			throw new Exception("Unreadable job file", 2901);
		}

		return $job;

	}

	public function record_job($job_name, $job_content) {

		if (empty($job_name) OR empty($job_content)) {
			comodojo_debug('Error recording job: Invalid job name or empty content','ERROR','cron_jobs_management');
			throw new Exception("Invalid job name or empty content", 2901);
		}

		$job = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job_name.'.php';

		if (is_readable($job)) {
			comodojo_debug('Error recording job: Job already exists','ERROR','cron_jobs_management');
			throw new Exception("Job already exists", 2905);
		}

		$fh = fopen($job, 'w');
		if (!fwrite($fh, stripcslashes($job_content))) {
			fclose($fh);
			throw new Exception("Error writing job", 2906);
		}
		fclose($fh);

		return true;

	}

	public function save_job($job_name, $job_content) {

		if (empty($job_name) OR empty($job_content)) {
			comodojo_debug('Error recording job: Invalid job name or empty content','ERROR','cron_jobs_management');
			throw new Exception("Invalid job name or empty content", 2901);
		}

		$job = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job_name.'.php';

		if (!is_readable($job)) {
			comodojo_debug('Error recording job: cannot find job','ERROR','cron_jobs_management');
			throw new Exception("Cannot find job", 2905);
		}

		$fh = fopen($job, 'w');
		if (!fwrite($fh, stripcslashes($job_content))) {
			fclose($fh);
			throw new Exception("Error writing job", 2906);
		}
		fclose($fh);

		return true;

	}	

/********************* PUBLIC METHODS ********************/
	
}

function loadHelper_cron_jobs_management() { return false; }

?>