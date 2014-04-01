<?php

/**
 * kernel.php
 * 
 * Default interface to call every single comodojo application from web frontend.
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

require 'comodojo/global/comodojo_basic.php';

class kernel extends comodojo_basic {
	
	public $script_name = 'kernel.php';
	
	public $use_session_transport = true;
	
	public $require_valid_session = true;
	
	public $do_authentication = true;
	
	private $application = false;
	
	private $method = false;
	
	
	/**
	 * If it's a DATASTORE request, setup kernel to handle a datastore
	 */
	private $is_datastore_request = false;
	
	private $datastore_label = 'name'; 
	
	private $datastore_identifier = 'resource';
	

	/**
	 * If it's a STORE request, setup kernel to handle a KernelStore (ObjectStore)
	 */
	private $is_store_request = false;
	
	
	private $encoded_content = false;
	
	private $transport = 'JSON';
	
	private $app_exec = false;
	
	private $app_run = false;
	
	public function logic($attributes) {
		
		comodojo_load_resource('cache');
		comodojo_load_resource('application');
		
		if (!isset($attributes['application']) OR !isset($attributes['method'])) {
			comodojo_debug('Inconsistent request: application or method not specified','ERROR','kernel');
			throw new Exception("Inconsistent request: application or method not specified", 2106);
		} 

		$this->application	=	$attributes['application'];
		$this->method		=	$attributes['method'];
		
		if (isset($attributes['contentEncoded']) AND @$attributes['contentEncoded'] == true) {
			$this->encoded_content = true;
			foreach (json2array(stripslashes($attributes['content'])) as $param => $value) $attributes[$param] = $value;
		}
		
		if (isset($attributes['transport'])) $this->transport = strtoupper($attributes['transport']);
		
		if (isset($attributes['datastore']) AND @$attributes['datastore'] == true) {
			$this->is_datastore_request = true;
			if (isset($attributes['datastoreLabel'])) $this->datastore_label = $attributes['datastoreLabel'];
			if (isset($attributes['datastoreIdentifier'])) $this->datastore_identifier = $attributes['datastoreIdentifier'];
		}

		if (isset($attributes['store']) AND @$attributes['store'] == true) {
			$this->is_store_request = true;
		}
		
		if ($this->application == 'comodojo') {
			
			try {
				$this->app_exec = COMODOJO_SITE_PATH.'comodojo/global/comodojo_reserved.php';		
			
				require $this->app_exec;
			
				$this->app_run = new comodojo_reserved();
				
				$method = $this->eval_request_sustainability($attributes);
				
				$to_return = $this->app_run->$method[0]($attributes);
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		else {
			
			$this->app_exec = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER.$this->application.'/'.$this->application.'.php';
			
			try {
				comodojo_load_resource('role_mapper');
				$mapper = new role_mapper();
				$this->eval_request_consistence($mapper->get_allowed_applications());
				
				require $this->app_exec;
				
				$this->eval_application_consistence();
				
				$this->app_run = new $this->application;
				
				$method = $this->eval_request_sustainability($attributes);
				$request = 'COMODOJO_KERNEL-'.var_export($attributes,true);
				if ($method[3]) {
					$c = new cache();
					$cache = $c->get_cache($request, 'JSON', true);
					if ($cache !== false) {
						comodojo_debug('Data for request: '.$request.' loaded from cache','INFO','kernel');
						$to_return = $cache[2]['cache_content'];
					}
					else {
						$to_return = $this->app_run->$method[0]($attributes);
						$c->set_cache(Array('cache_content'=>$to_return), $request, 'JSON', true);
					}
				}
				else {
					$to_return = $this->app_run->$method[0]($attributes);
				}
			}
			catch (Exception $e) {
				throw $e;
			}
		}
		
		comodojo_debug('Serving content for request to '.$this->application.'->'.$this->method,'INFO','kernel');
		
		if ($this->is_datastore_request) {
			$to_encode_and_return = $this->compose_return_datastore($this->datastore_label, $this->datastore_identifier, $to_return);
			$total = null;
		}
		elseif ($this->is_store_request) {
			list($to_encode_and_return, $total) = $this->compose_return_store($to_return);
		}
		else {
			$to_encode_and_return = $this->compose_return_data($to_return);
			$total = null;
		}
		return $this->encode_return_data($to_encode_and_return, $total);
		
	}
	
	public function error($error_code, $error_name) {
		
		if ($this->is_store_request) {

			$to_return = $error_code.'-'.$error_name;

			set_header(Array(
				'statusCode'	=>	500
			), strlen($to_return));

		}
		else {
			$error = Array('success'=>false,'result'=>Array('code'=>$error_code,'name'=>$error_name));
		
			switch ($this->transport) {
				case 'JSON': $to_return = array2json($error); $contentType = 'application/json'; break;
				case 'XML': $to_return = array2xml($error); $contentType = 'application/xml'; break;
				case 'YAML': $to_return = array2yaml($error); $contentType = ''; break;
				default: $to_return = array2json($error); $contentType = 'application/json'; break;
			}
			
			set_header(Array(
				'statusCode'	=>	200,
				'ttl'			=> 	0,
				'contentType'	=> $contentType,
				'charset'		=>	COMODOJO_DEFAULT_ENCODING
			), strlen($to_return));
		}
		
		return $to_return;
		
	}
	
	private function eval_request_consistence($allowed_applications) {
		if (!in_array($this->application,$allowed_applications) OR !is_readable($this->app_exec)) {
				comodojo_debug("Application ".$this->application." not registered or not allowed",'ERROR','kernel');
				throw new Exception("Inconsistent request: application not registered or not allowed", 2101);
			}
		else return true;
	}
	
	private function eval_application_consistence() {
		if (!class_exists($this->application)) {
			comodojo_debug("Wrong class implentation for application: ".$this->application,'ERROR','kernel');
			throw new Exception("Inconsistent application: wrong class implementation.", 2102);
		}
		else return true;
	}
	
	private function eval_request_sustainability($attributes) {
		$method = $this->app_run->get_registered_method($this->method);
		if (!$method) {
			comodojo_debug("Unsustainable request: method ".$this->method." not registered correctly",'ERROR','kernel');
			throw new Exception("Unsustainable request: method not registered", 2103);
		}
		/*
		if (method_exists($this->app_run, $method[0])) {
			comodojo_debug("Unsustainable request: relative method ".$method[0]." not declared",'ERROR','kernel');
			throw new Exception("Unsustainable request: relative method not declared", 2104);
		}
		*/
		if (!attributes_to_parameters_match($attributes, $method[1])) {
			comodojo_debug("Unsustainable request: parameters mismatch",'ERROR','kernel');
			throw new Exception("Unsustainable request: parameters mismatch", 2105);
		}
		return $method;
	}
	
	private function compose_return_data($result) {
		return array('success'=>true,'result'=>$result);
	}
	
	private function compose_return_datastore($label, $identifier, $data) {
		return Array("label"=>$label,"identifier"=>$identifier,"items"=>is_null($data) ? Array() : $data);
	}

	private function compose_return_store($data) {
		return Array(is_null($data['data']['result']) ? Array() : $data['data']['result'], is_null($data['total']) ? null : $data['total']);
	}
	
	private function encode_return_data($data, $total=null) {
		
		if ($this->is_store_request) {
			switch($this->method) {
				case 'kernel_get':
					if(empty($data)) {
						$to_return = null;
						set_header(Array(
							'statusCode'	=>	404
						), 0);
					}
					else {
						$to_return = array2json($data);
						set_header(Array(
							'statusCode'	=>	200,
							'ttl'			=> 	0,
							'contentType'	=>	'application/json',
							'charset'		=>	COMODOJO_DEFAULT_ENCODING,
							'contentRange'	=>	is_null($total) ? false : 'items '.$total
						), strlen($to_return));
					}
				break;
				case 'kernel_query':
					$to_return = array2json($data);
					set_header(Array(
						'statusCode'	=>	200,
						'ttl'			=> 	0,
						'contentType'	=>	'application/json',
						'charset'		=>	COMODOJO_DEFAULT_ENCODING,
						'contentRange'	=>	is_null($total) ? false : 'items '.$total
					), strlen($to_return));
				break;
				case 'kernel_delete':
					$to_return = null;
					if(!$data) {
						set_header(Array(
							'statusCode'	=>	404
						), 0);
					}
					else {
						set_header(Array(
							'statusCode'	=>	204
						), 0);
					}
				break;
				case 'kernel_store':
				case 'kernel_update':
					$to_return = array2json($data);
					set_header(Array(
						'statusCode'	=>	200,
						'ttl'			=> 	0,
						'contentType'	=>	'application/json',
						'charset'		=>	COMODOJO_DEFAULT_ENCODING
					), strlen($to_return));
				break;
			}

		}
		else {
			switch ($this->transport) {
				case 'JSON': $to_return = array2json($data); $contentType = 'application/json'; break;
				case 'XML': $to_return = array2xml($data); $contentType = 'application/xml'; break;
				case 'YAML': $to_return = array2yaml($data); $contentType = ''; break;
				default: $to_return = array2json($data); $contentType = 'application/json'; break;
			}
			
			set_header(Array(
				'statusCode'	=>	200,
				'ttl'			=> 	0,
				'contentType'	=>	$contentType,
				'charset'		=>	COMODOJO_DEFAULT_ENCODING
			), strlen($to_return));
		}
		
		return $to_return;
	}
	
}

$kernel = new kernel();

?>