<?php

/**
 * application.php
 * 
 * Standard extendable method for comodojo apps.
 * 
 * Every new application SHOULD extend this class.
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class application {
	
	/**
	 * Array of declared methods; kernel will process only requests to those methods
	 */
	private $application_declared_methods = Array();

	/**
	 * ID key for kernel store requests
	 */
	private $id_key = 'id';

	/**
	 * Table to manage with kernel store
	 */
	private $table;

	/**
	 * Table fields to manage with kernel store
	 */
	//private $fields;
	
	/**
	 * Registered methods will be something like:
	 * $[METHOD] = Array(0->RELATIVE_CLASS_METHOD,1->REQUESTED_PARAMETERS,2->DESCRIPTION,3->CACHE)
	 */
	private $application_registered_methods = Array();
	
	public final function add_application_method($request_method,$class_method,$required_parameters,$description=null,$cache=false) {
		array_push($this->application_declared_methods,Array('RM'=>$request_method,'CM'=>$class_method,'RP'=>$required_parameters,'DE'=>$description,'CH'=>$cache));
	}

	//public final function add_store_methods($methods, $table, $id_key='id', $fields=Array(), 
	public final function add_store_methods($table, $methods=Array("DELETE","STORE","UPDATE","GET","QUERY"), $id_key='id', 
		$dbHost=COMODOJO_DB_HOST,
		$dbDataModel=COMODOJO_DB_DATA_MODEL,
		$dbName=COMODOJO_DB_NAME,
		$dbPort=COMODOJO_DB_PORT,
		$dbPrefix=COMODOJO_DB_PREFIX,
		$dbUserName=COMODOJO_DB_USER,
		$dbUserPass=COMODOJO_DB_PASSWORD
	) {
		$this->table = $table;
		foreach ($methods as $method) {
			$m = strtoupper($method);
			switch ($m) {
				case 'DELETE':
					$this->add_application_method('kernel_delete','kernel_delete',Array('id'));
				break;
				case 'STORE':
					$this->add_application_method('kernel_store','kernel_store',Array('data','overwrite'));
				break;
				case 'UPDATE':
					$this->add_application_method('kernel_update','kernel_update',Array('id','data','overwrite','idProperty'));
				break;
				case 'GET':
					$this->add_application_method('kernel_get','kernel_get',Array('id'));
				break;
				case 'QUERY':
					$this->add_application_method('kernel_query','kernel_query',Array('query','idProperty'));
				break;
			}
		}
	}
	
	public final function get_registered_method($request_method) {
		if (!isset($this->application_registered_methods[$request_method])) return false;
		else return $this->application_registered_methods[$request_method];
	}
	
	public final function get_registered_methods() {
		$methods = Array();
		foreach ($this->application_registered_methods as $key => $value) {
			if ($key == 'methods') continue;
			$methods[$key] = Array(
				"description"=>$value[2],
				"parameters" =>!count($value[1]) ? '-' : $value[1]
			);
		}
		return $methods;
		//return $this->application_registered_methods;
	}
	
	public final function __construct() {
		$this->init();
		foreach ($this->application_declared_methods as $declared_method) {
			if (!method_exists($this, $declared_method['CM'])) {
				comodojo_debug('Error adding application method '.$declared_method['RM'].': relative method '.$declared_method['CM'].' does not exists.','ERROR','applications');
				continue;
			}
			else {
				$this->application_registered_methods[$declared_method['RM']] = Array($declared_method['CM'],$declared_method['RP'],$declared_method['DE'],$declared_method['CH']);
				comodojo_debug('Added application method '.$declared_method['RM'].' with relative method '.$declared_method['CM'],'INFO','applications');
			}
		}
		$this->application_registered_methods['methods'] = Array('get_registered_methods',Array(),'',false);
	}
	
	public function kernel_get($params) {
		comodojo_load_resource('database');
		try {
			$db = new database();
			$result = $db->table($this->table)->keys('*')->where($this->id_key,"=",$params['id'])->get();
		}
		catch (Exception $e){
			throw $e;
		}
		return Array("data"=>$result,"total"=>null);
	}

	public function kernel_query($params) {
		comodojo_load_resource('database');
		$off_limit = isset($params['range']) ? explode('-', $params['range']) : Array(0,0);
		$offset = !is_null($off_limit[0]) ? intval($off_limit[0]) : 0;
		$limit = !is_null($off_limit[1]) ? intval($off_limit[1]) : 0;
		//list($offset,$limit) = isset($params['range']) ? explode('-', $params['range']) : Array(0,0);
		$where_done = false;
		try {
			$db = new database();
			$result = $db->table($this->table)->keys('*');
			//error_log("######".$params['query']);
			if ($params['query'] != "0") {
				parse_str($params['query'],$query);
				//error_log("######".$query);
				foreach ($query as $key => $value) {
					if ($value == "**") {
						continue;
					}
					elseif (!$where_done) {
						$result = $result->where($key,'=',$value);
						$where_done = true;
					}
					else {
						$result = $result->and_where($key,'=',$value);
					}
				}
			}
			if (isset($params['sort'])) {
				foreach (explode(',',$params['sort']) as $sort) {
					$direction = substr($sort, 0, 1) == '+' ? 'ASC' : 'DESC';
					$value = substr($sort, 1);
					$result = $result->order_by($value,$direction);
				}
			}
			$result_data = $result->get($limit,$offset);
			$result_total = $result->keys('COUNT::'.$params['idProperty'].'=>count')->get();
		}
		catch (Exception $e){
			throw $e;
		}

		$count = $result_total['result'][0]['count'];
		
		$total = $offset.'-'.($limit<$count && $limit !== 0 ? $limit-1 : $count-1).'/'.$count;

		//$total = (isset($params['range']) ? $offset.'-'.($limit<$count ? $limit-1 : $count-1) : '0-'.($count-1)).'/'.$count;

		//return $result;
		return Array("data"=>$result_data,"total"=>$total);
	}

	public function kernel_store($params) {
		comodojo_load_resource('database');
		parse_str($params['data'],$data);
		$data_keys = array_keys($data);
		$data_values = array_values($data);
		try {
			$db = new database();
			$result = $db->table($this->table)->keys($data_keys)->values($data_values)->store();
		}
		catch (Exception $e){
			throw $e;
		}
		//return $result;
		return Array("data"=>$result,"total"=>null);
	}

	public function kernel_update($params) {
		comodojo_load_resource('database');
		parse_str($params['data'],$data);

		if (isset($data[$params['idProperty']])) {
			unset($data[$params['idProperty']]);
		}

		$data_keys = array_keys($data);
		$data_values = array_values($data);

		try {
			$db = new database();
			if ($params['overwrite'] == 1) {
				$db->table($this->table)->where($this->id_key,"=",$params['id'])->delete();
				$db->clean();
				array_push($data_keys, $this->id_key);
				array_push($data_values, $params['id']);
				$result = $db->table($this->table)->keys($data_keys)->values($data_values)->store();
			}
			else {
				$result = $db->table($this->table)->keys($data_keys)->values($data_values)->where($this->id_key,"=",$params['id'])->update();
			}
		}
		catch (Exception $e){
			throw $e;
		}
		//return $result;
		return Array("data"=>$result,"total"=>null);
	}

	public function kernel_delete($params) {
		comodojo_load_resource('database');
		try {
			$db = new database();
			$result = $db->table($this->table)->where($this->id_key,"=",$params['id'])->delete();
		}
		catch (Exception $e){
			throw $e;
		}
		//return $result;
		return Array("data"=>$result,"total"=>null);
	}

	public function init() {}
}

?>