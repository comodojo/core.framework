<?php

/**
 * cron_job.php
 * 
 * A cron job template to extend according to your needs
 * 
 * It has embedded support for multi-thread calls, worklog, logging, ...
 * via the main cron interface or scheduler-like application.  
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class cron_job {
    	
	/**
	 * The job name
	 * 
	 * @var	string
	 */
	private $job_name = 'CRON_JOB';
	
	/**
	 * Job class (the one that extend cron_job).
	 * 
	 * AUTO-RETRIEVED
	 * 
	 * @var	string
	 */
	private $job_class;
	
	/**
	 * Start timestamp
	 * 
	 * @var	int
	 */
	private $start_timestamp;
	
	/**
	 * End timestamp
	 * 
	 * @var	int
	 */
	private $end_timestamp;
	
	/**
	 * Current process PID
	 */
	private $pid = null;
	
	/**
	 * The job result (if any)
	 */
	private $job_result = null;
	
	/**
	 * The job end state
	 */
	private $job_success = false;
	
	/**
	 * Worklog ID
	 */
	private $worklog_id = null;
	
	/**
	 * 
	 */
	private $params = false;
	
	/**
	 * Job constructor.
	 * 
	 * @param	array	$params			Array of job parameters (if any)
	 * @param	int		$PID			Hob PID (if any)
	 * @param	string	$job_name		Job Name
	 * @param	int		$timestamp		Start timestamp (if null will be retrieved directly)
	 * 
	 * @return	$this 
	 */
	public final function __construct($params, $pid=null, $job_name=null, $timestamp=null) {
		
		comodojo_load_resource('database');
		
		if (!is_null($params)) $this->params = json2array($params);
		else $this->params = null;
		
		if (!is_null($job_name)) $this->job_name = $job_name;
		
		$this->pid = is_null($pid) ? getmypid() : $pid;

		if (is_null($timestamp)) $this->start_timestamp = time();
		else $this->start_timestamp = $timestamp;
		
		$this->job_class = get_class($this);

		return $this;

	}

	public final function start() {

		try{
			$job_run_info = $this->run_logic($this->params, $this->pid, $this->job_name, $this->job_class, $this->start_timestamp);
		}
		catch (Exception $e) {
			comodojo_debug("Job ".$this->job_name." terminated with error: ".$e->getMessage(),"ERROR","cron");
			throw $e;
			
		}

		return $job_run_info;

	} 
	
	/**
	 * 
	 */
	private function run_logic($params, $pid, $job_name, $job_class, $start_timestamp) {
		
		comodojo_debug('Starting new job '.$job_name.' ('.$job_class.') with PID='.$pid.' at '.date('c',$start_timestamp),'INFO','cron');
		
		$worklog_id = null;

		try{
			$worklog_id = $this->create_worklog($pid, $job_name, $job_class, $start_timestamp);
			$job_result = $this->logic($params);
			$end_timestamp = time();
			$this->close_worklog($worklog_id, true, $job_result, $end_timestamp);
		}
		catch (Exception $e) {
			$job_result = $e->getMessage();
			if (!is_null($worklog_id)) {
				$this->close_worklog($worklog_id, false, $job_result, time());
			}
			throw $e;
		}

		comodojo_debug('Job '.$job_name.' ('.$job_class.') completed at '.date('c',$end_timestamp),'INFO','cron');
		
		return Array(
			"success"	=>	true,
			"timestamp"	=>	$end_timestamp,
			"result"	=>	$job_result
		);

	}

	
	/**
	 * Create the worklog for current job
	 */
	private function create_worklog($pid, $job_name, $job_class, $start_timestamp) {
		
		try{
			$db = new database();
			$result = $db->table("cron_worklog")
				->return_id()
				->keys(Array("pid","name","job","status","start"))
				//->values(Array($this->should_get_pid ? getmypid() : $this->pid,$this->job_name,$this->job_class,'STARTED',$this->start_timestamp))
				->values(Array($pid,$job_name,$job_class,'STARTED',$start_timestamp))
				->store();
		}
		catch (Exception $e) {
			unset($db);
			comodojo_debug("Error creating worklog for job ".$this->job_name.": ".$e->getMessage(),"ERROR","cron");
			throw $e;
		}
		
		unset($db);

		return $result['transactionId'];
			
	}
	
	/**
	 * Close worklog for current job
	 */
	private function close_worklog($worklog_id, $job_success, $job_result, $end_timestamp) {
		
		try{
			$db = new database();
			$result = $db
				->table("cron_worklog")
				->keys(Array("status","success","result","end"))
				->values(Array("FINISHED",$job_success,$job_result,$end_timestamp))
				->where("id","=",$worklog_id)
				->update();
		}
		catch (Exception $e) {
			unset($db);
			comodojo_debug("Error closing worklog for job ".$this->job_name.": ".$e->getMessage(),"ERROR","cron");
			throw $e;
		}
		
		unset($db);
		
	}
	
	/**
     * Return PID from system (null if no multi-thread active)
     * 
     * @return int
     */
	public final function get_pid() {

		return $this->pid;

	}
    
	/**
	 * The job logic
	 */
	public function logic($params) {}

}

function loadHelper_cron_job() { return false; }

?>