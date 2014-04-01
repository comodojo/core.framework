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

comodojo_load_resource('cron_jobs_management');

class cronmanager extends application {
	
	public function init() {
		$this->add_store_methods('cron_worklog',Array("GET","QUERY"));
		$this->add_application_method('get_cron_and_jobs', 'getCronAndJobs', Array(), 'No description yet, sorry', false);
		$this->add_application_method('open_job', 'openJob', Array("job_name"), 'No description yet, sorry', false);
		$this->add_application_method('edit_job', 'editJob', Array("job_name","job_content"), 'No description yet, sorry', false);
		$this->add_application_method('new_job', 'newJob', Array("job_name","job_content"), 'No description yet, sorry', false);
		$this->add_application_method('delete_job', 'deleteJob', Array("job_name"), 'No description yet, sorry', false);
		$this->add_application_method('exec_job', 'execJob', Array("job_name","job_params"), 'No description yet, sorry', false);
		$this->add_application_method('open_cron', 'openCron', Array("name"), 'No description yet, sorry', false);
		$this->add_application_method('enable_cron', 'enableCron', Array("name"), 'No description yet, sorry', false);
		$this->add_application_method('disable_cron', 'disableCron', Array("name"), 'No description yet, sorry', false);
		$this->add_application_method('delete_cron', 'deleteCron', Array("name"), 'No description yet, sorry', false);
		$this->add_application_method('validate_cron', 'validateCron', Array("expression"), 'No description yet, sorry', false);
		$this->add_application_method('new_cron', 'newCron', Array('name', 'job', 'expression'), 'No description yet, sorry', false);
		$this->add_application_method('save_cron', 'saveCron', Array('name', 'job', 'expression'), 'No description yet, sorry', false);
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

	public function newJob($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->record_job($params['job_name'], $params['job_content']);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $params['job_name'];
	}

	public function editJob($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->save_job($params['job_name'], $params['job_content']);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $params['job_name'];
	}

	public function deleteJob($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->delete_job($params['job_name']);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $params['job_name'];
	}

	public function execJob($params) {

		comodojo_load_resource('database');
		comodojo_load_resource('cron_job');
		require(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$params['job_name'].'.php');

		$start_timestamp = time();
		$job_name = $params['job_name'];
		$job_params = empty($params["job_params"]) ? null : $params["job_params"];
		$_job = new $job_name($job_params, null, 'SELF_RUN', $start_timestamp, false);
		
		try {

			$job_result = $_job->start();
		
		}
		catch (Exception $e) {
		
			return $e->getMessage();
		
		}

		$job_result["start"] = $start_timestamp;

		return $job_result;

	}

	public function openCron($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->open_cron($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function enableCron($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->enable_cron($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function disableCron($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->disable_cron($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function deleteCron($params) {
		$c = new cron_jobs_management();
		try {
			$s = $c->delete_cron($params["name"]);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function validateCron($params) {
		require_once 'comodojo/global/Cron/FieldInterface.php';
		require_once 'comodojo/global/Cron/AbstractField.php';
		require_once 'comodojo/global/Cron/DayOfMonthField.php';
		require_once 'comodojo/global/Cron/DayOfWeekField.php';
		require_once 'comodojo/global/Cron/HoursField.php';
		require_once 'comodojo/global/Cron/MinutesField.php';
		require_once 'comodojo/global/Cron/MonthField.php';
		require_once 'comodojo/global/Cron/YearField.php';
		require_once 'comodojo/global/Cron/FieldFactory.php';
		require_once 'comodojo/global/Cron/CronExpression.php';
		
		$cron = Cron\CronExpression::factory($params["expression"]);
		try {
			$s = $cron->getNextRunDate()->format('c');
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function newCron($params) {
		$description = isset($params["description"]) ? $params["description"] : null;
		$parameters = isset($params["params"]) ? trim($params["params"]) : null;
		$c = new cron_jobs_management();
		try {
			$s = $c->new_cron($params["name"], $params["job"], $params["expression"], $description, $parameters);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

	public function saveCron($params) {
		$description = isset($params["description"]) ? $params["description"] : null;
		$parameters = isset($params["params"]) ? trim($params["params"]) : null;
		$c = new cron_jobs_management();
		try {
			$s = $c->save_cron($params["name"], $params["job"], $params["expression"], $description, $parameters);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $s;
	}

}

?>