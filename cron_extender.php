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

require_once 'comodojo/global/comodojo_basic.php';
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

class cron_extender extends comodojo_basic {

	/**
	 * Max result lenght retrieved from parent in miltithread mode
	 * (will be included in controlpanel in next release)
	 */
	public $max_result_in_multi_thread = 2048;

	/**
	 * Show result in command line when executing
	 * (will be included in controlpanel in next release)
	 */
	public $echo_results = true;

	/**
	 * Maximum time (in seconds) the parent will wait childs in miltithread mode prior to start killing
	 * (will be included in controlpanel in next release)
	 */
	public $max_childs_run_time = 300;	

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

	private $ipc_array = Array();
	
	public function logic($attributes) {
		
		comodojo_load_resource('database');
		comodojo_load_resource('cron_job');

		if (php_sapi_name() !== 'cli') {
			die('Cron extender runs only in php-cli');
		}
		
		if (!defined('COMODOJO_CRON_ENABLED') OR @!COMODOJO_CRON_ENABLED) throw new Exception("Extender administratively disabled", 2501);
		
		$multithread_support = function_exists('pcntl_fork');

		if (COMODOJO_CRON_MULTI_THREAD_ENABLED AND $multithread_support) {
			comodojo_debug('Cron will work in multithread mode','INFO','cron');
			$this->multi_thread_enabled = true;
		}
		else {
			comodojo_debug('Cron will work in singlethread mode ('.(COMODOJO_CRON_MULTI_THREAD_ENABLED ? 'no pcntl support' : 'administratively disabled').')','INFO','cron');
			$this->multi_thread_enabled = false;
		}

		$this->timestamp = strtotime('now');
		
		try{

			$jobs = $this->get_jobs();

			if (empty($jobs)) {
				comodojo_debug('no jobs to process, exiting','INFO','cron');
				return;
			}

			comodojo_debug("Current timestamp: ".$this->timestamp." - ".date('c',$this->timestamp),'INFO','cron');
			
			foreach ($jobs as $id => $job) {
				if ($this->should_run_job($job)) {
					if (!class_exists($job['job'])) {
						$job_file = include(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CRON_FOLDER.$job['job'].'.php');
						if (!$job_file) {
							comodojo_debug('Cron '.$job['name'].' will not be executed due to a compile error (it was impossible to include job '.$job['job'].')','ERROR','cron');
						}
						else {
							array_push($this->jobs,$job);
						}
					}
					else {
						array_push($this->jobs,$job);
					}
				}
			}

		}
		catch (Exception $e) {

			comodojo_debug('There was an error processing job list; cron execution aborted','ERROR','cron');
			throw $e;

		}
		
		$forked = Array();

		foreach ($this->jobs as $key => $job) {
			
			if (!$this->multi_thread_enabled) {
				$pid = null;
				array_push($this->completed_processes,$this->run_singlethread($job));
			}
			else {
				
				$multithread_status = $this->run_multithread($job);
				
				if (!is_null($multithread_status["pid"])) {
					$this->running_processes[$multithread_status["pid"]] = Array($multithread_status["name"], $multithread_status["uid"], $multithread_status["timestamp"],$multithread_status["id"]);
					array_push($forked, $multithread_status["pid"]);
				}

			}

		}
		
		//pcntl_wait($this->status);

		comodojo_debug("Extender forked ".sizeof($forked)." process(es) in the running queue: ".implode(',', $forked),"INFO","cron");

		$exec_time = time();

		while(!empty($this->running_processes)) {

			foreach($this->running_processes as $pid => $job) {

				//$job[0] is name
				//$job[1] is uid
				//$job[2] is start timestamp
				//$job[3] is job id

				if(!$this->is_running($pid)) {

					list($reader,$writer) = $this->ipc_array[$job[1]];

					socket_close($writer);
					
					$parent_result = socket_read($reader, $this->max_result_in_multi_thread, PHP_BINARY_READ);
					if ($parent_result === false) {
						comodojo_debug("socket_read() failed. Reason: ".socket_strerror(socket_last_error($reader)),'ERROR','cron');
						array_push($this->completed_processes,Array(
							null,
							$job[0],//$job_name,
							false,
							$job[2],//$start_timestamp,
							null,
							"socket_read() failed. Reason: ".socket_strerror(socket_last_error($reader)),
							$job[3]
						));
					}
					else {
						$result = unserialize(rtrim($parent_result));
					}
					//error_log('parent receive: '.$this->result);
					socket_close($reader);
					
					array_push($this->completed_processes,Array(
						$pid,
						$job[0],//$job_name,
						$result["success"],
						$job[2],//$start_timestamp,
						$result["timestamp"],
						$result["result"],
						$job[3]
					));
					
					unset($this->running_processes[$pid]);

					comodojo_debug("Removed pid ".$pid." from the running queue, job terminated","INFO","cron");

				}
				else {
					if (time() > $startTime + $timeout) {
						comodojo_debug("Killing pid ".$pid." due to maximum exec time reached (>".$this->max_childs_run_time.")","INFO","cron");
						$kill = $this->kill($pid);
						comodojo_debug("Pid ".$pid." ".($kill ? "killed" : "could not be killed"),"INFO","cron");
					}
				}

			}

		}

		$this->update_jobs_info();

		$this->send_notification();
		
		if ($this->echo_results) return $this->show_results();

	}
	
	public function error($error_code, $error_name) {

		comodojo_debug($error_code.' - '.$error_name,'ERROR','cron');

	}
	
	private function get_jobs() {
		
		try{
			$db = new database();
			$result = $db->table("cron")->keys("*")->where("enabled","=",true)->get();
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

		$expression = implode(" ",Array($job['min'],$job['hour'],$job['day_of_month'],$job['month'],$job['day_of_week'],$job['year'])); 
		
		$last_date = date_create();

		date_timestamp_set($last_date, $job['last_run']);

		try {
			$cron = Cron\CronExpression::factory($expression);
			$next_calculated_run = $cron->getNextRunDate($last_date)->format('U');
		}
		catch (Exception $e) {
			comodojo_debug("Job ".$job['name']." will NOT be executed due to CRON PARSING ERROR",'INFO','cron');
		}

		comodojo_debug("Job ".$job['name']." declared cron expression: ".$expression,'INFO','cron');
		comodojo_debug("Job ".$job['name']." last run date: ".$job['last_run']." - ".date('c',$job['last_run']),'INFO','cron');
		comodojo_debug("Job ".$job['name']." next run date: ".$next_calculated_run." - ".date('c',$next_calculated_run),'INFO','cron');

		if ($next_calculated_run <= $this->timestamp) {
		//if ($cron->isDue($last_date)) {
			comodojo_debug("Job ".$job['name']." will be executed",'INFO','cron');
			return true;
		}
		else {
			comodojo_debug("Job ".$job['name']." will NOT be executed",'INFO','cron');
			return false;
		}

	}

	private function run_singlethread($job) {

		$start_timestamp = time();
		$job_name = $job['name'];
		$job_id = $job['id'];
		$_job = new $job['job']($job['params'], null, $job['name'], $start_timestamp);
		$pid = $_job->get_pid();
		
		try {

			$job_result = $_job->start();
		
		}
		catch (Exception $e) {
		
			return Array($pid, $job_name, false, $start_timestamp, null, $e->getMessage());
		
		}

		return Array($pid, $job_name, $job_result["success"], $start_timestamp, $job_result["timestamp"], $job_result["result"],$job_id);

	}

	private function run_multithread($job) {
		
		$start_timestamp = time();
		$job_name = $job['name'];
		$job_id = $job['id'];
		$job_uid = random();

		$this->ipc_array[$job_uid] = Array();
		
		if (socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $this->ipc_array[$job_uid]) === false) {
			comodojo_debug('No IPC communication, exiting - '.socket_strerror(socket_last_error()),'ERROR','cron');
			array_push($this->completed_processes,Array(
				null,
				$job_name,
				false,
				$start_timestamp,
				time(),
				'No IPC communication, exiting - '.socket_strerror(socket_last_error()),
				$job_id
			));
			return Array(
				"pid"		=>	null,
				"name"		=>	$job_name,
				"uid"		=>	$job_uid,
				"timestamp"	=>	$start_timestamp,
				"id"		=>	$job_id
			);
		}
		
		list($reader,$writer) = $this->ipc_array[$job_uid];

		$pid = @pcntl_fork();

		if( $pid == -1 ) {

			comodojo_debug('Could not fok job','ERROR','cron');
			array_push($this->completed_processes,Array(
				null,
				$job_name,
				false,
				$start_timestamp,
				time(),
				'Could not fok job',
				$job_id
			));

		}
		elseif ($pid) {

			//PARENT will take actions on processes later

		}
		else {
			
			socket_close($reader);
			pcntl_signal( SIGTERM, function($signo) { exit(0); } );
			
			$_job = new $job['job']($job['params'], null, $job['name'], $start_timestamp);

			try{

				$job_result = $_job->start();

				$job_result = serialize(Array(
					"success"	=>	$job_result["success"],
					"result"	=>	$job_result["result"],
					"timestamp"	=>	$job_result["timestamp"]
				));

			}
			catch (Exception $e) {

				$message = $e->getMessage();
				
				$job_result = serialize(Array(
					"success"	=>	false,
					"result"	=>	$message,
					"timestamp"	=>	time()
				));
				
				if (socket_write($writer, $job_result, strlen($job_result)) === false) {
					comodojo_debug("socket_write() failed. Reason: ".socket_strerror(socket_last_error($writer)),'ERROR','cron');
				}
				socket_close($writer);
				
				exit(1);

			}

			if (socket_write($writer, $job_result, strlen($job_result)) === false) {
				comodojo_debug("socket_write() failed. Reason: ".socket_strerror(socket_last_error($writer)),'ERROR','cron');
			}
			socket_close($writer);

			exit(0);

		}

		return Array(
			"pid"		=>	$pid == -1 ? null : $pid,
			"name"		=>	$job_name,
			"uid"		=>	$job_uid,
			"id"		=>	$job_id,
			"timestamp"	=>	$start_timestamp
		);

	}

	/**
     * Return PID from system (null if no multi-thread active)
     * 
     * @return int
     */
	private final function get_pid() {

		return $this->pid;

	}

	/**
	 * Return true if process is still running, false otherwise
	 * 
	 * @return	bool
	 */
	private final function is_running($pid) {

		return (pcntl_waitpid($pid, $this->status, WNOHANG) === 0);
		//return posix_kill($pid, 0);

	}

	private final function kill($pid) {

		return posix_kill($pid, SIGKILL);

	}

	private function update_jobs_info() {
		
		if (empty($this->completed_processes)) return;
		
		try{
			$db = new database();
			foreach ($this->completed_processes as $process) {
				$db->table("cron")->keys("last_run")->values($process[3])->where('id','=',$process[6])->update();
				$db->clean();
			}
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
			return;
		}
		
		$message = "";
		
		foreach ($cron_event_to_report as $event) {
			$message .= '<tr><td align="center" valign="middle">'.$event[0].'</td><td align="center" valign="middle">'.$event[1].'</td><td align="center" valign="middle">'.(!$event[2] ? 'FAILURE' : 'SUCCESS').'</td><td align="center" valign="middle">'.($event[4]-$event[3]).'</td></tr>';
		}
		
		try {
			$mail = new mail();
			$mail->template('mail_cron.html')
				 ->to(COMODOJO_CRON_NOTIFICATION_ADDRESSES)
				 ->subject("Cron Extender Jobs Report")
				 ->message($message)
				 ->embed(COMODOJO_SITE_PATH."comodojo/images/logo.png","COMODOJO_LOGO","logo")
				 ->send();
		}
		catch (Exception $e) {
			comodojo_debug("Error notifying jobs: ".$e->getCode().'-'.$e->getMessage(),'ERROR','cron');
		}
		
	}
	
	private function show_results() {
		
		$mask = "|%10.10s|%-40.40s|%3.3s|%11.11s|%-80.80s|\n";
		
		$output_string = "\n\n --- Cron Extender Job resume --- ".date('c',$this->timestamp)."\n\n";
		
		$output_string .= sprintf($mask, '-----------', '----------------------------------------', '---', '-----------','--------------------------------------------------------------------------------');
		$output_string .= sprintf($mask, 'PID', 'Name', 'Su', 'Time (secs)', 'Result (truncated)');
		$output_string .= sprintf($mask, '-----------', '----------------------------------------', '---', '-----------','--------------------------------------------------------------------------------');
		
		foreach ($this->completed_processes as $key => $completed_process) {
			$output_string .= sprintf($mask, $completed_process[0], $completed_process[1], $completed_process[2] ? 'YES' : 'NO', $completed_process[2] ? ($completed_process[4]-$completed_process[3]) : "-",str_replace(array("\r", "\n"), "", $completed_process[5]));
		}
		$output_string .= sprintf($mask, '-----------', '----------------------------------------', '---', '-----------','--------------------------------------------------------------------------------');

		$output_string .= "\n\n";
		$output_string .= "Total script runtime: ".(strtotime('now')-$this->timestamp)." seconds";
		$output_string .= "\n\n";
		
		return $output_string;
		
	}

}

$extender = new cron_extender();

?>