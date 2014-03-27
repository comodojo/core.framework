<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');
comodojo_load_resource('cron_jobs_management');

class cronmanager extends application {
	
	public function init() {
		$this->add_store_methods('cron_worklog',Array("GET","QUERY"));
		$this->add_application_method('get_cron_and_jobs', 'getCronAndJobs', Array(), 'No description yes, sorry', false);
		$this->add_application_method('open_job', 'openJob', Array("job_name"), 'No description yes, sorry', false);
		$this->add_application_method('edit_job', 'editJob', Array("job_name","job_content"), 'No description yes, sorry', false);
		$this->add_application_method('new_job', 'newJob', Array("job_name","job_content"), 'No description yes, sorry', false);
		//$this->add_application_method('get_service', 'getService', Array("name"), 'No description yes, sorry', false);
	}

	public function getCronAndJobs($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->get_cron_and_jobs();
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}
	
	public function openJob($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->open_job($params['job_name']);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function editJob($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->record_job($params['job_name'], $params['job_content']);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $params['job_name'];
	}

	public function newJob($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->save_job($params['job_name'], $params['job_content']);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $params['job_name'];
	}

}

?>