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
			if (!is_dir($jobs_items.$jobs_item) AND $job_file_properties['extension'] == 'php' AND $job_file_properties['basename'][0] != '.' ) {
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
			$result = $db->table("cron")->keys(Array("id","name","enabled","job","min","hour","day_of_month","month","day_of_week","year"))->get();
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
			throw new Exception("Unreadable job file", 2502);
		}

		$job = file_get_contents(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job_name.'.php');

		if (!$job) {
			comodojo_debug('Error retrieving job: unreadable job file','ERROR','cron_jobs_management');
			throw new Exception("Unreadable job file", 2502);
		}

		return $job;

	}

	public function record_job($job_name, $job_content) {

		if (empty($job_name) OR empty($job_content)) {
			comodojo_debug('Error recording job: Invalid job name or empty content','ERROR','cron_jobs_management');
			throw new Exception("Invalid job name or empty content", 2503);
		}

		$job = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job_name.'.php';

		if (is_readable($job)) {
			comodojo_debug('Error recording job: Job already exists','ERROR','cron_jobs_management');
			throw new Exception("Job already exists", 2504);
		}

		$fh = fopen($job, 'w');
		if (!fwrite($fh, stripcslashes($job_content))) {
			fclose($fh);
			throw new Exception("Error writing job", 2505);
		}
		fclose($fh);

		return true;

	}

	public function save_job($job_name, $job_content) {

		if (empty($job_name) OR empty($job_content)) {
			comodojo_debug('Error recording job: Invalid job name or empty content','ERROR','cron_jobs_management');
			throw new Exception("Invalid job name or empty content", 2503);
		}

		$job = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job_name.'.php';

		if (!is_readable($job)) {
			comodojo_debug('Error recording job: cannot find job','ERROR','cron_jobs_management');
			throw new Exception("Cannot find job", 2506);
		}

		$fh = fopen($job, 'w');
		if (!fwrite($fh, stripcslashes($job_content))) {
			fclose($fh);
			throw new Exception("Error writing job", 2505);
		}
		fclose($fh);

		return true;

	}

	public function delete_job($job_name) {

		if (empty($job_name)) {
			comodojo_debug('Error deleting job: Invalid job name','ERROR','cron_jobs_management');
			throw new Exception("Invalid job name or empty content", 2503);
		}

		$job = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job_name.'.php';

		if (!is_readable($job)) {
			comodojo_debug('Error deleting job: cannot find job','ERROR','cron_jobs_management');
			throw new Exception("Cannot find job", 2506);
		}

		comodojo_load_resource('database');

		try {
			$db = new database();
			$result = $db->table("cron")->keys("id")->where("job","=",$job_name)->get();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}

		if ($result["resultLength"] > 0) {
			comodojo_debug('Error deleting job: job used in one or more cron','ERROR','cron_jobs_management');
			throw new Exception("Cannot delete a job file used in one or more cron", 2508);
		}

		$result = @unlink($job);
		
		if (!$result) throw new Exception("Cannot delete job file", 2507);

	}

	public function open_cron($cron) {

		if (empty($cron)) throw new Exception("Cannot find cron", 2509);

		comodojo_load_resource('database');

		try {
			$db = new database();
			$result_ask = $db->table("cron")->keys(Array("id","job","description","min","hour","day_of_month","month","day_of_week","year","params"))->where("name","=",$cron)->get();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}

		if ($result_ask["resultLength"] != 1) {
			comodojo_debug('Cannot find cron','ERROR','cron_jobs_management');
			throw new Exception("Cannot find cron", 2506);
		}

		$re = $result_ask["result"][0];

		$expression = implode(" ",Array($re['min'],$re['hour'],$re['day_of_month'],$re['month'],$re['day_of_week'],$re['year']));

		return Array(
			"id"			=>	$re["id"],
			"name"			=>	$cron,
			"job"			=>	$re["job"],
			"description"	=>	$re["description"],
			"expression"	=>	$expression,
			"params"		=>	$re["params"]
		);

	}

	public function enable_cron($cron) {

		if (empty($cron)) throw new Exception("Cannot find cron", 2509);

		comodojo_load_resource('database');

		try {
			$db = new database();
			$result_ask = $db->table("cron")->keys("id")->where("name","=",$cron)->get();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}

		if ($result_ask["resultLength"] == 0) {
			comodojo_debug('Cannot find cron','ERROR','cron_jobs_management');
			throw new Exception("Cannot find cron", 2506);
		}

		try {
			$db->clean();
			$result = $db->table("cron")->keys("enabled")->values(true)->where("name","=",$cron)->update();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}
		
		return Array(
			"id"		=>	$result_ask["result"][0]["id"],
			"name"		=>	$cron,
			"enabled"	=>	true
		);

	}

	public function disable_cron($cron) {
		
		if (empty($cron)) throw new Exception("Cannot find cron", 2509);

		comodojo_load_resource('database');

		try {
			$db = new database();
			$result_ask = $db->table("cron")->keys("id")->where("name","=",$cron)->get();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}

		if ($result_ask["resultLength"] == 0) {
			comodojo_debug('Cannot find cron','ERROR','cron_jobs_management');
			throw new Exception("Cannot find cron", 2506);
		}

		try {
			$db->clean();
			$result = $db->table("cron")->keys("enabled")->values(false)->where("name","=",$cron)->update();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}
		
		return Array(
			"id"		=>	$result_ask["result"][0]["id"],
			"name"		=>	$cron,
			"enabled"	=>	false
		);

	}

	public function delete_cron($cron) {
		
		if (empty($cron)) throw new Exception("Cannot find cron", 2509);

		comodojo_load_resource('database');

		try {
			$db = new database();
			$result = $db->table("cron")->keys("id")->where("name","=",$cron)->get();
		}
		catch (Exception $e) {
			comodojo_debug('Error retrieving cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}

		if ($result["resultLength"] == 0) {
			comodojo_debug('Cannot find cron','ERROR','cron_jobs_management');
			throw new Exception("Cannot find cron", 2506);
		}

		try {
			$db->clean();
			$result = $db->table("cron")->where("name","=",$cron)->delete();
		}
		catch (Exception $e) {
			comodojo_debug('Error deleting cron: '.'('.$e->getCode().') '.$e->getMessage(),'ERROR','cron_jobs_management');
			throw $e;
		}
		
		if ($result['affectedRows'] != 1) {
			comodojo_debug('Error deleting cron','ERROR','cron_jobs_management');
			throw new Exception("Error deleting cron", 2510);
		}

		return true;

	}

/********************* PUBLIC METHODS ********************/
	
}

function loadHelper_cron_jobs_management() { return false; }

?>