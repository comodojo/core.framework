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
	 * Force a script to be executed in single-thread mode.
	 * 
	 * @var	bool
	 */
	public $force_no_multi_thread = false;
	
	/**
	 * Force a script to be executed in single-thread mode.
	 * 
	 * @var	bool
	 */
	public $max_result_in_multi_thread = 1024;

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
	 * Is multi-thread active?
	 * 
	 * @var	bool
	 */
	private $multi_thread;
	
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
	private $job_success = true;
	
	/**
	 * Worklog ID
	 */
	private $worklog_id = null;
	
	/**
	 * When executed with _fork, a job should retrieve 
	 * it's pid be it's own
	 */
	private $should_get_pid = false;
	
	/**
	 * Job constructor.
	 * 
	 * It will init a thread (if $multi_thread) or launch the job directly.
	 * 
	 * Constructor returns only the PID of the job or NULL if no mt enabled/available
	 * 
	 * @param	array	$params			Array of job parameters (if any)
	 * @param	bool	$multi_thread	If true, job will be raised using pcntl_fork
	 * @param	int		$timestamp		Start timestamp (if null will be retrieved directly)
	 * 
	 * @return	int|null	PID|NULL in case of thread|direct-call 
	 */
	public final function __construct($params, $job_name=null, $multi_thread=false, $timestamp=null) {
		
		comodojo_load_resource('database');
		
		if (!is_null($params)) $_params = json2array($params);
		else $_params = null;
		
		if (!is_null($job_name)) $this->job_name = $job_name;
		
		$this->multi_thread = ($multi_thread AND !$this->force_no_multi_thread);
		
		if (is_null($timestamp)) $this->start_timestamp = time();
		else $this->start_timestamp = $timestamp;
		
		$this->job_class = get_class($this);
		
		comodojo_debug("Executing job ".$job_name." in ".($multi_thread ? 'multi-thread' : 'single-thread')." mode","INFO","cron");
		
		try{
			if ($multi_thread) {
				$this->run_as_thread($_params);
			}
			else {
				$this->run_logic($_params);
			}
		}
		catch (Exception $e) {
			comodojo_debug('Job error: '.$e->getMessage(),'ERROR','cron');
		}
		
	}
	
	/**
	 * 
	 */
	private function run_logic($params) {
		
		comodojo_debug('Starting new job '.$this->job_name.' ('.$this->job_class.') with PID='.($this->should_get_pid ? getmypid() : $this->pid).' at '.date(DATE_RFC822,$this->start_timestamp),'INFO','cron');
		
		if (!$this->create_worklog()) {
			comodojo_debug('Execution of job '.$this->job_name.' interrupted due to error creating worklog','ERROR','cron');
			throw new Exception("Could not create work log", 2502);
		}
		
		try{
			$this->job_result = $this->logic($params);
		}
		catch (Exception $e) {
			$this->job_success = false;
			$this->job_result = $e->getMessage();
			$this->close_worklog();
			throw $e;
		}
		
		$this->end_timestamp = time();
		
		comodojo_debug('Job '.$this->job_name.' ('.$this->job_class.') completed at '.date('c',$this->end_timestamp),'INFO','cron');
		
		if (!$this->close_worklog(true)) {
			comodojo_debug('Error closing worklog for job '.$this->job_name,'ERROR','cron');
			throw new Exception("Could not close work log", 2503);
		}
		
	}

	/**
	 * Run the job as a thread.
	 * 
	 * @return	void
	 */
	private function run_as_thread($params) {

		$ipc_array = Array();

		if (socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $ipc_array) === false) {
			comodojo_debug('No IPC communication, exiting - '.socket_strerror(socket_last_error()),'ERROR','cron');
			throw new Exception('No IPC communication, exiting - '.socket_strerror(socket_last_error()), 2506);
		}

		$pid = @pcntl_fork();
		
		if( $pid == -1 ) {
			throw new Exception('Could not fok job', 2501);
		}
		elseif ($pid) {
			$this->pid = $pid;
			socket_close($ipc_array[0]);
			$this->result = socket_read($ipc_array[1], $this->max_result_in_multi_thread, PHP_BINARY_READ);
			socket_close($ipc_array[1]);
			pcntl_wait($status);
		}
		else {
			pcntl_signal( SIGTERM, function($signo) { exit(0); } );
			$this->should_get_pid = true;
			socket_close($ipc_array[1]);
			try{
				$result = $this->run_logic($params);
				socket_write($ipc_array[0], $result);
				socket_close($ipc_array[0]);
			}
			catch (Exception $e) {
				socket_write($ipc_array[0], $e->getMessage());
				socket_close($ipc_array[0]);
				exit(1);
			}
			exit(0);
			
		}
		
	}
	
	/**
	 * Create the worklog for current job
	 */
	private function create_worklog() {
		
		try{
			$db = new database();
			$result = $db->table("cron_worklog")
				->return_id()
				->keys(Array("pid","name","job","status","start"))
				->values(Array($this->should_get_pid ? getmypid() : $this->pid,$this->job_name,$this->job_class,'STARTED',$this->start_timestamp))
				->store();
		}
		catch (Exception $e) {
			unset($db);
			comodojo_debug("Error creating worklog for job ".$this->job_name.": ".$e->getMessage(),"ERROR","cron");
			return false;
		}
		
		unset($db);
		$this->worklog_id = $result['transactionId'];
		
		return true;
			
	}
	
	/**
	 * Close worklog for current job
	 */
	private function close_worklog() {
		
		try{
			$db = new database();
			$result = $db
				->table("cron_worklog")
				->keys(Array("status","success","result","end"))
				->values(Array("FINISHED",$this->job_success,$this->job_result,$this->end_timestamp))
				->where("id","=",$this->worklog_id)
				->update();
		}
		catch (Exception $e) {
			unset($db);
			comodojo_debug("Error closing worklog for job ".$this->job_name.": ".$e->getMessage(),"ERROR","cron");
			return false;
		}
		
		unset($db);
		
		return true;
		
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
	 * Return true if process is still running, false otherwise
	 * 
	 * @return	bool
	 */
	public final function is_running() {

		return (pcntl_waitpid($this->pid, $this->status, WNOHANG) === 0);

	}
	
	/**
	 * Stop a running thread
	 * 
	 * @return	void
	 */
    public final function stop_thread() {

    	if ($this->is_running()) posix_kill($this->pid, SIGKILL);

    }
	
	/**
	 * Get job results
	 */
	public final function get_job_results() {
		
		return Array($this->job_name, $this->job_success, $this->start_timestamp, $this->end_timestamp, $this->job_result);
		
	}
	
	/**
	 * The job logic
	 */
	public function logic($params) {}

}

?>