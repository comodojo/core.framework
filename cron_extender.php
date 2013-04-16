<?php

/**
 * cron.php
 * 
 * A cron extender, multi-thread-enabled, to use VIA COMMAND LINE ONLY.
 * 
 * It enables access to all framework functions via script. Each cron script can be scheduled independently
 * using database (see scheduler.php for interaction with db).  
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

require 'comodojo/global/comodojo_basic.php';
require 'comodojo/global/cron.phar';

date_default_timezone_set('Europe/Berlin');

class cron_extender extends comodojo_basic {

	public $script_name = 'cron_extender.php';
	
	public $use_session_transport = false; //CRON cannot use session
	
	public $require_valid_session = false; //CRON cannot use session
	
	public $do_authentication = false; //CRON could not be authenticated
	
	public $raw_attributes = false; //CRON server SHOULD NOT receive RAW post data, or any post data at all
	
	public $auto_set_header = false; //CRON JOBS SHOULD NOT init any header
	
	private $multi_thread_enabled = false;
	
	private $jobs = Array();
	
	private $running_processes = Array();
	
	private $completed_processes = Array();
	
	private $timestamp;
	
	private $start_timestamp;
	
	private $echo_results = true;
	
	public function logic($attributes) {
		
		if (php_sapi_name() !== 'cli') {
			die('Cron extender runs only in php-cli');
			//throw new Exception("Cron extender runs only in php-cli", 2506);
		}
		
		if (!defined('COMODOJO_CRON_ENABLED') OR @!COMODOJO_CRON_ENABLED) throw new Exception("cron disabled", 2504);
		
		$this->multi_thread_enabled = COMODOJO_CRON_MULTI_THREAD_ENABLED;
		
		$this->timestamp = strtotime('now');
		
		comodojo_load_resource('database');
		
		try{
			$jobs = $this->get_jobs();
			if (empty($jobs)) {
				comodojo_debug('no jobs to process, exiting','INFO','cron');
				//throw new Exception("no jobs to process, exiting", 2505);
				return;
			}
			foreach ($jobs as $id => $job) {
				if ($this->should_run_job($job)) {
					array_push($this->jobs,$job);
					if (!class_exists($job['job'])) require(COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job['job'].'.php');
				}
			}
			$this->update_jobs_info();
		}
		catch (Exception $e) {
			comodojo_debug('There was an error processing job list; cron execution aborted','ERROR','cron');
			throw $e;
		}
		
		foreach ($this->jobs as $key => $job) {
			
			$this->start_timestamp = time();
			
			$_job = new $job['job']($job['params'], $job['name'], $this->multi_thread_enabled, $this->start_timestamp);
			
			$pid = $_job->get_pid();
			
			if (is_null($pid)) {
				list($job_name, $job_success, $job_start, $job_end) = $_job->get_job_results();
				array_push($this->completed_processes,Array($pid,$job_name,$job_success,$job_start, $job_end));
			}
			else {
				$this->running_processes[$pid] = $_job;
			}
		}
		
		while(!empty($this->running_processes)) {
			foreach($this->running_processes as $pid=>$process) {
				if(!$process->is_running()) {
					//list($job_name, $job_success, $job_result, $job_start, $job_end) = $process->get_job_results();
					
					list($job_name, $job_success, $job_start, $job_end) = $process->get_job_results();
					$job_success = !pcntl_wexitstatus($process->status);
					
					//is a fake time, but it return end timestamp with a precision of ~1 sec
					$job_end = time();
					
					array_push($this->completed_processes,Array($pid, $job_name, $job_success, $job_start, $job_end));
					unset($this->running_processes[$pid]);
				}
				//error_log('waiting for '.$pid);
			}
    		sleep(1);
		}

		$this->send_notification();
		
		if ($this->echo_results) return $this->show_results();

	}
	
	public function error($error_code, $error_name) {
		comodojo_debug($error_code.' - '.$error_name,'ERROR','cron');
		//echo $error_code.' - '.$error_name;
	}
	
	private function get_jobs() {
		
		try{
			$db = new database();
			$db->table("cron")->keys("*")->where("enabled","=",true)->get();
		}
		catch (Exception $e) {
			comodojo_debug($e->getMessage(),'ERROR','cron');
			unset($db);
			throw $e;
		}
		
		unset($db);
		return $result['result'];
		
	}
	
	private function should_run_job($job) {
		
		return true;
		
		$expression = implode(" ",Array($job['min'],$job['hour'],$job['day_of_month'],$job['month'],$job['day_of_week'],$job['year'])); 
		
		$cron = Cron\CronExpression::factory($expression);
		
		$last_calculated_run = $cron->getPreviousRunDate()->format('U');
		$next_calculated_run = $cron->getNextRunDate()->format('U');
		
		if ($job['last_run'] < $last_calculated_run OR $next_calculated_run <= strtotime('now')) {
			comodojo_debug("Job ".$job['name']." will be executed",'INFO','cron');
			return true;
		}
		else {
			comodojo_debug("Job ".$job['name']." will NOT be executed",'INFO','cron');
			return false;
		}
		
	}
	
	private function update_jobs_info() {
		
		if (empty($this->jobs)) return;
		
		$run = false;

		try{
			$db = new database();
			$result = $db->table("cron")->keys("last_run")->values($this->timestamp);
			foreach ($this->jobs as $job) {
				if (!$run) {
					$result = $result->where('id','=',$job['id']);
					$run = true;
				}
				else $result = $result->or_where('id','=',$job['id']);
			}
			$result = $result->update();
		}
		catch (Exception $e) {
			unset($db);
			throw $e;
		}
		unset($db);
	}
	
	private function send_notification() {
		
		comodojo_load_resource("mail");
		
		$cron_event_to_report = Array();
		
		switch(strtoupper(COMODOJO_CRON_NOTIFICATION_MODE)) {
			/*	
			case "DISABLED":
				comodojo_debug('cron notifications disabled, skypping','INFO','cron');
				return;
			break;
			*/
			case "ALWAYS":
				$cron_event_to_report = $this->completed_processes;
			break;
			
			case "FAILURES":
				foreach ($this->completed_processes as $process) {
					if (!$process[2]) array_push($cron_event_to_report,$process);
				}
			break;
			
			default:
				comodojo_debug('cron notifications disabled, exiting','INFO','cron');
				return;
			break;
		}
		
		if (!count($cron_event_to_report)) {
			comodojo_debug('no jobs to report, exiting','INFO','cron');
		}
		
		$message = "";
		
		foreach ($cron_event_to_report as $event) {
			$message .= '<tr><td align="center" valign="middle">'.$event[0].'</td><td align="center" valign="middle">'.$event[1].'</td><td align="center" valign="middle">'.(!$event[2] ? 'FAILURE' : 'SUCCESS').'</td><td align="center" valign="middle">'.($event[4]-$event[3]).'</td></tr>';
		}
		
		try {
			$mail = new mail;
			$mail->htmlTemplate = 'mail_cron.html';
			$mail->to = COMODOJO_CRON_NOTIFICATION_ADDRESSES;
			$mail->subject = COMODOJO_SITE_TITLE." - Cron Jobs Report";
			$mail->message = $message;
			$mail->sendHtmlMail();
		}
		catch (Exception $e) {
			comodojo_debug("Error notifying jobs: ".$e->getCode().'-'.$e->getMessage(),'ERROR','cron');
		}
		
	}
	
	private function show_results() {
		
		$mask = "|%10.10s|%-40.40s|%3.3s|%11.11s|\n";
		
		$output_string = "\n\n --- Cron Extender Job resume --- \n\n";
		
		$output_string .= sprintf($mask, '-----------', '----------------------------------------', '---', '-----------');
		$output_string .= sprintf($mask, 'PID  ', 'Name', 'Su', 'Time (secs)');
		$output_string .= sprintf($mask, '-----------', '----------------------------------------', '---', '-----------');
		
		foreach ($this->completed_processes as $key => $completed_process) {
			$output_string .= sprintf($mask, $completed_process[0], $completed_process[1], $completed_process[2] ? 'YES' : 'NO', $completed_process[2] ? ($completed_process[4]-$completed_process[3]) : "-");
			//$output_string .= "\n".$completed_process[5]." - ".$completed_process[4]."\n";
		}
		$output_string .= sprintf($mask, '-----------', '----------------------------------------', '---', '-----------');

		$output_string .= "\n\n";
		$output_string .= "Total script runtime: ".(strtotime('now')-$this->timestamp)." seconds";
		$output_string .= "\n\n";
		
		return $output_string;
		
	}

}

$extender = new cron_extender();

?>