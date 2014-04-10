<?php

/**
 * rpc.php
 * 
 * Multiformat RPC server for the comodojo environment.
 * 
 * It currently support:
 *  - JSON-RPC 2.0 single/batch requests (no 1.0 compatibility!)
 *  - XML-RPC requests as described in http://xmlrpc.scripting.com/spec.html
 *  - YAML-RPC according to NON STANDARD comodojo way (see http://www.comodojo.org/XX for
 *    more informations) will be included in future releases.
 *  - common fault codes as http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php
 * 
 * Additionally RPC-Server supports the Comodojo shared-key protected transport, and it's
 * completely transparent to rpc.
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

/*
 * -------------------------------------------------------------------------------------
 * 					****** Anatomy of a comodojo RPC request ******
 * -------------------------------------------------------------------------------------
 * 
 * Comodojo RPC request MUST have always this schema:
 * 
 * '<application>.<method>','<userName>','<userPass>','[attributes]'
 * 
 * Only [attributes] param is optional. The only case in which userName and userPass could be
 * omitted is in a JSON-RPC with object-param. 
 * 
 * Depending on transport, attributes could be both placed in a specific position or named.
 * 
 * In JSON-RPC, parameters are included in the "params" array/object, in XML params SHOULD
 * be inserted in a specific STRUCT object.
 * 
 * -> AUTHENTICATED REQUESTS <-
 * 
 * The login request for the user 'comodojo' with password 'comodojo' should be
 * something like (in JSON-RPC):
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.login", "params": ['comodojo','comodojo'], "id": 1}
 * 
 * That means a request with no parameters, just userName and userPass; same request could be
 * written as (in case of params-object):
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.login", "params": {'userName': 'comodojo', 'userPass': 'comodojo'}, "id": 1}
 * 
 * According to the standard, two format are equivalent BUT If you can choose, consider to PREFER THE SECOND.
 * For the particular organization of comodojo framework, in case of array of unnamed, positional values,
 * RPC-Server will associate values to <application.method>.<requested parameters> according to position.
 * Optional parameters ARE NOT considered.
 * 
 * Using named object avoid this, because RPC-Server will forward all parameters except the
 * userName/userPass couple to method as $attribute variable (same behaviour of xhr call in kernel).
 * 
 * For the XML-RPC this is not valid, because each request SHOULD respect the comodojo schema 
 * described above, using a STRUCT element for [attributes].
 * 
 * So the same request in XML-RPC SHOULD be something like:
 * 
 * <?xml version="1.0"?>
 * <methodCall>
 * <methodName>comodojo.login</methodName>
 * <params>
 * 	<param>
 * 		<value><string>comodojo</string></value>
 * 	</param>
 * 	<param>
 * 		<value><string>comodojo</string></value>
 * 	</param>
 * </params>
 * </methodCall>
 * 
 * or in YAML:
 * 
 * [TBW]
 * 
 * An authenticated comodojo.version request with v=PRODUCT variable will be:
 * 
 * - JSON-RPC
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.version", "params": {'userName': 'comodojo', 'userPass': 'comodojo', 'v':'PRODUCT'}, "id": 1}
 * 
 * Please note that, for reasons described above, the same request:
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.version", "params": ["comodojo","comodojo","PRODUCT"], "id": 1}
 * 
 * will be served as:
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.version", "params": ["comodojo","comodojo"], "id": 1}
 * 
 * because server will not consider the third parameter.
 * 
 * - XML-RPC
 * 
 * <?xml version="1.0"?>
 * <methodCall>
 * <methodName>comodojo.version</methodName>
 * <params>
 * 	<param>
 * 		<value><string>comodojo</string></value>
 * 	</param>
 * 	<param>
 * 		<value><string>comodojo</string></value>
 * 	</param>
 * 	<struct>
 * 		<member>
 * 			<name>v</name>
 * 			<value><string>PRODUCT</string></value>
 * 		</member>
 * 	</struct>
 * </params>
 * </methodCall>
 * 
 * - YAML-RPC
 * 
 * [TBW]
 * 
 * **************************************  <- This is a WARNING :)
 * ****** A NOTE OF BATCH REQUESTS ******
 * **************************************************************************************************
 * Comodojo uses a complex named-constants schema to handle jobs, system parameters, authentication.
 * That's the reason why, in case of a burst request, IT IS NOT POSSIBLE to handle different 
 * userName/userPass credentials at the same time! (i.e. multiple rpc-jobs of multiple users)
 * 
 * This means that, in case of burst requests, ONLY THE FIRST AUTHENTICATION will be analyzed, so
 * EVERY OTHER JOB WILL BE PROCESSED WITH THE SAME USER (or no user in case of null authentication).
 * **************************************************************************************************
 * 
 * -> Unauthenticated requests <-
 * 
 * In unauthenticated requests userName/userPass couple COULD be omitted if:
 *  - params is a object (so values are named) -> will be null/null
 *  - params is not defined -> will be null/null
 *  - params is an empty object or array -> will be null/null
 * 
 * If params is an array, first two values will be used as userName/userPass IN ANY CASE.
 * 
 * So the version request (unauthenticated case) could be something like:
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.version", "params": [false, false], "id": 1}
 * 
 * or:
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.version", "params": {'userName':false, 'userPass':false}, "id": 1}
 * 
 * or even:
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.version", "params": {}, "id": 1}
 * 
 * but also:
 * 
 * {"jsonrpc": "2.0", "method": "comodojo.version", "id": 1}
 * 
 * In XML-RPC:
 * 
 * <?xml version="1.0"?>
 * <methodCall>
 * <methodName>comodojo.login</methodName>
 * <params>
 * 	<param>
 * 		<value><boolean>0</boolean></value>
 * 	</param>
 * 	<param>
 * 		<value><boolean>0</boolean></value>
 * 	</param>
 * </params>
 * </methodCall>
 * 
 * In YAML-RPC:
 * 
 * [TBW]
 * 
 * -> Reserver methods <-
 * 
 * - comodojo.login
 * - comodojo.logout
 * - comodojo.applications
 * - comodojo.version
 * - system.getCapabilities (see http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php)
 * - rpc.getCapabilities (same as system.getCapabilities)
 * 
 * -------------------------------------------------------------------------------------
 * 
 */

require 'comodojo_basic.php';

class rpc_server extends comodojo_basic {
	
	public $script_name = 'rpc_server.php';

	public $use_session_transport = false; //rpc cannot use session
	
	public $require_valid_session = false; //rpc cannot use session
	
	public $do_authentication = false; //rpc could be authenticated, but with embedded directives

	public $clean_auth_constants = false;
	
	public $raw_attributes = true; //RPC server SHOULD receive RAW post data
	
	private $application = false; 
	
	private $method = false;
	
	private $transport = NULL;
	
	private $app_exec = false;
	
	private $app_run = false;
	
	private $is_native_rpc = false;
	
	private $json_rpc_has_id = false;
	
	private $json_rpc_id = null;
	
	private $json_rpc_auth_runs_once = false;
	
	private $aes = null;

	private $loaded_applications = Array();
	
	public function __construct($transport) {
		$this->transport = strtoupper($transport);
		parent::__construct();
	}
	
	public function logic(/*attributes will NOT be parsed, JUST RAW DATA HERE*/$attributes) {
		
		if (!defined('COMODOJO_RPC_ENABLED') OR @!COMODOJO_RPC_ENABLED) throw new Exception("RPC closed", -32098);
		
		if (!in_array($this->transport, explode(',',COMODOJO_RPC_ALLOWED_TRANSPORT))) throw new Exception("Unallowed Transport", -32097);
		
		$payload_is_encrypted = substr($attributes,0,27) == 'comodojo_encrypted_envelope' ? true : false;
		
		switch(COMODOJO_RPC_MODE) {
			
			case 'plain':
				if ($payload_is_encrypted) throw new Exception('Encrypted transport not available', -32096);
			break;
			
			case 'shared':
				if ($payload_is_encrypted) {
					try { $attributes = $this->decryptData(substr($attributes,28)); }
					catch (Exception $e) { throw $e; }
				}
				else {
					throw new Exception('PlainText transport not available', -32095);
				}
			break;
				
			case 'any':
				if ($payload_is_encrypted) {
					try { $attributes = $this->decryptData(substr($attributes,28)); }
					catch (Exception $e) { throw $e; }
				}
			break;
				
			default:
				throw new Exception("Transport error", -32300);
			break;
			
		}
		
		if ($this->transport == 'JSON') {
				
			$decoded_data = json2array($attributes, true /*DO RAW conversion*/);
			
			if (is_null($decoded_data)) throw new Exception(0, -32700);
			
			elseif (is_array($decoded_data)) {
				/* -> IT IS A BATCH REQUEST <-
				 * Requests will be served using foreach loop. Auth will be performed ONLY for the first request.
				 * Errors will be caught, stored and served in the burst return array.
				 */
				$to_return = Array();
				
				foreach ($decoded_data as $request_data) {
					try {
						list($method, $data, $map_to_attributes) = $this->preprocess_json_request($request_data);
						$result = $this->process_request($method, $data, $map_to_attributes);
						$request_response = !$this->json_rpc_has_id ? NULL : Array("jsonrpc" => "2.0", "result" => $result, "id" => $this->json_rpc_id);
						if (!is_null($request_response)) array_push($to_return, $request_response);
					}
					catch (Exception $e) {
						array_push($to_return, $this->generate_error($e->getCode(), $e->getMessage()));
					}
				}
				
				// IT WAS A NOTIFICATIONS' BATCH REQUEST
				if (count($to_return) == 0) $to_return = NULL;
				
			}
			elseif (is_object($decoded_data)) {
				/* -> IT IS A SINGLE REQUEST <- 
				 * Every error will be caught and redirected to single error 
				 */
				try {
					list($method,$data,$map_to_attributes) = $this->preprocess_json_request($decoded_data);
					$result = $this->process_request($method, $data, $map_to_attributes);
					$to_return = !$this->json_rpc_has_id ? NULL : Array("jsonrpc" => "2.0", "result" => $result, "id" => $this->json_rpc_id);
				}
				catch (Exception $e) {
					throw $e;
				}
				
			}
			else {
				/* IT IS A SYSTEM ERROR :( */
				throw new Exception(0, -32400);
			}

		}
		
		elseif ($this->transport == 'XML'){
			
			if (!function_exists('xmlrpc_encode_request')) {

				comodojo_debug("Using xmlRpcEncoder","DEBUG","rpc_server");
				comodojo_load_resource('xmlRpcEncoder');
				comodojo_load_resource('xmlRpcDecoder');
				$this->is_native_rpc = false;
				$decoder = new xmlRpcDecoder();
				list($method, $data) = $decoder->decode_call($attributes);

			}
			else {

				comodojo_debug("Using xmlrpc_encode_request","DEBUG","rpc_client");
				$this->is_native_rpc = true;
				$method = null;
				$data = xmlrpc_decode_request($attributes, $method);

			}
			
			if (is_null($method)) throw new Exception("Invalid Request", -32600);
			
			$userName = isset($data[0]) ? $data[0] : NULL;
			$userPass = isset($data[1]) ? $data[1] : NULL;
			$parameters = isset($data[2]) ? $data[2] : NULL;
			
			$this->auth_login($userName, $userPass, false);
			
			try {
				$to_return = $this->process_request($method, $parameters);
			}
			catch (Exception $e) {
				throw $e;
			}
			
		}
		
		else throw new Exception("Transport error", -32300);
		
		return $this->encode_return_data($to_return);
		
	}
	
	private function preprocess_json_request($decoded_data) {
		
		if (!isset($decoded_data->jsonrpc) OR @$decoded_data->jsonrpc != "2.0" OR !isset($decoded_data->method) OR !(@is_scalar($decoded_data->method))) throw new Exception(0, -32600);

		if (isset($decoded_data->id)) {
			$this->json_rpc_has_id = true;
			$this->json_rpc_id = is_scalar($decoded_data->id) ? $decoded_data->id : NULL;
		}
		else {
			$this->json_rpc_has_id = true;
			$this->json_rpc_id = NULL;
		}
		
		$method = $decoded_data->method;
				
		if (is_object($decoded_data->params)) {
			$map_to_attributes = false;
			$data = stdObj2array($decoded_data->params);
			if (isset($data['userName'])) {
				$userName = $data['userName'];
				unset($data['userName']);
			}
			else {
				$userName = NULL;
			}
			if (isset($data['userPass'])) {
				$userPass = $data['userPass'];
				unset($data['userPass']);
			}
			else {
				$userPass = NULL;
			}
			if (count($data) == 0) { $data = NULL; }
		}
		elseif (is_array($decoded_data->params)) {
			$map_to_attributes = true;
			$count = count($decoded_data->params);
			if ($count <= 1) {
				$userName = NULL;
				$userPass = NULL;
				$data = NULL;
			}
			elseif ($count == 2) {
				$userName = $decoded_data->params[0];
				$userPass = $decoded_data->params[1];
				$data = NULL;
			}
			else {
				$userName = $decoded_data->params[0];
				$userPass = $decoded_data->params[1];
				$data = $decoded_data->params;
				array_shift($data);
				array_shift($data);
			}
		}
		else throw new Exception("Not structured param", -32600);
		
		if (!$this->json_rpc_auth_runs_once) {
			$this->auth_login($userName, $userPass, false);
			$this->json_rpc_auth_runs_once = true;
		}
		
		return Array($method,$data,$map_to_attributes);
				
	}
	
	private function process_request($method, $params, $mapToAttributes=false) {
		
		comodojo_load_resource('events');
		comodojo_load_resource('application');
		
		list($_application, $_method) = explode('.',$method);
		
		if ($_application == 'comodojo') {
			
			$this->app_exec = COMODOJO_SITE_PATH.'comodojo/global/comodojo_reserved.php';
			
			if (!in_array($this->app_exec, $this->loaded_applications)) {
				require $this->app_exec;
				array_push($this->loaded_applications, $this->app_exec);
			}

			try {
				$this->app_run = new comodojo_reserved();
				
				list($runnable, $attributes) = $this->eval_request_sustainability($_method, $params, $mapToAttributes);
				
				$to_return = $this->app_run->$runnable($attributes);
			}
			catch (Exception $e) {
				throw $e;
			}
			
		}
		elseif ($_application == 'system' OR $_application == 'rpc') {
			
			$this->app_exec = COMODOJO_SITE_PATH.'comodojo/global/system_reserved.php';
			
			if (!in_array($this->app_exec, $this->loaded_applications)) {
				require $this->app_exec;
				array_push($this->loaded_applications, $this->app_exec);
			}

			try {
				$this->app_run = new system_reserved();
				
				list($runnable, $attributes) = $this->eval_request_sustainability($_method, $params, $mapToAttributes);
				
				$to_return = $this->app_run->$runnable($attributes);
			}
			catch (Exception $e) {
				throw $e;
			}
			
		}
		else {
			
			comodojo_load_resource('role_mapper');

			$this->app_exec = COMODOJO_SITE_PATH.COMODOJO_APPLICATION_FOLDER.$_application.'/'.$_application.'.php';

			try {
				
				$mapper = new role_mapper();
				
				$this->eval_request_consistence($_application, $mapper->get_allowed_applications());
				
				if (!in_array($this->app_exec, $this->loaded_applications)) {
					require $this->app_exec;
					array_push($this->loaded_applications, $this->app_exec);
				}

				$this->eval_application_consistence($_application);
				
				$this->app_run = new $_application;
				
				list($runnable, $attributes) = $this->eval_request_sustainability($_method, $params, $mapToAttributes);
				
				
				/*
				 * CACHE ON RPC IS DISABLED BY DEFAULT.
				 * 
				 * To enable server caching (at your risk) uncomment code below.
				 *
				comodojo_load_resource('cache');
				$request = 'COMODOJO_RPC-'.var_export($attributes,true);
				$method_definition = $this->app_run->get_registered_method($_method);
				
				if ($method_definition[3]) {
					$c = new cache();
					$cache = $c->get_cache($request, 'JSON', true);
					if ($cache !== false) {
						comodojo_debug('Data for request: '.$request.' loaded from cache','INFO','rpc_server');
						$to_return = $cache[2]['cache_content'];
					}
					else {
						$to_return = $this->app_run->$runnable($attributes);
						$c->set_cache(Array('cache_content'=>$to_return), $request, 'JSON', true);
					}
				}
				else {
				 */
					$to_return = $this->app_run->$runnable($attributes);
				/*
				}
				 */
			}
			catch (Exception $e) {
				throw $e;
			}
			
		}

		comodojo_debug('Serving content for request to '.$_application.'->'.$_method,'INFO','rpc_server');

		return $to_return;
		
	}
	
	private function eval_request_consistence($application, $allowed_applications) {
		if (!in_array($application,$allowed_applications) OR !is_readable($this->app_exec)) {
			comodojo_debug("Application ".$application." not registered or not allowed",'ERROR','rpc_server');
			throw new Exception(0, -32601);
		}
		else return true;
	}
	
	private function eval_application_consistence($application) {
		if (!class_exists($application)) {
			comodojo_debug("Wrong class implentation for application: ".$application,'ERROR','rpc_server');
			throw new Exception("wrong class implementation", -32500);
		}
		else return true;
	}
	
	private function eval_request_sustainability($method, $params, $mapToAttributes) {
		$method_definition = $this->app_run->get_registered_method($method);
		if (!$method) {
			comodojo_debug("Unsustainable request: method ".$this->method." not registered correctly",'ERROR','rpc_server');
			throw new Exception(0, -32601);
		}
		
		if ($mapToAttributes) {
			$attributes = Array();
			if (count($params) < count($method_definition[1])) {
				comodojo_debug("Unsustainable request: not enough parameters passed",'ERROR','rpc_server');
				throw new Exception(0, -32602);
			}
			foreach ($method_definition[1] as $key=>$param) {
				$attributes[$param] = $params[$key];
			}
		}
		else {
			$attributes = $params;
		}
		
		if (!attributes_to_parameters_match($attributes, $method_definition[1])) {
			comodojo_debug("Unsustainable request: parameters mismatch",'ERROR','rpc_server');
			throw new Exception(0, -32602);
		}
		return Array($method_definition[0],$attributes);
	}
	
	private function encode_return_data($data) {
		
		if ($this->transport == 'JSON') {
			$return_data = !is_null($data) ? array2json($data/*for JSON data mapping is direct (from logic)*/) : NULL;
			$contentType = 'application/json';
		}
		else {
			if ($this->is_native_rpc) {

				$return_data = xmlrpc_encode_request(NULL, $data, array('encoding' => COMODOJO_DEFAULT_ENCODING));

			}
			else {

				$encoder = new xmlRpcEncoder();

				$return_data = $encoder->encode_response($data);

			}
			$contentType = 'application/xml';
		}
		
		if (!is_null($this->aes)) {
			$return_data = $this->aes->encrypt($return_data);
		}
		
		set_header(Array(
			'statusCode'	=>	200,
			'ttl'			=> 	0,
			'contentType'	=>	$contentType,
			'charset'		=>	COMODOJO_DEFAULT_ENCODING
		), strlen($return_data));
		
		return $return_data;
		
	}
	
	private function generate_error($error_code, $error_description) {
		switch ($error_code) {
			case -32700:
				$_error_description = 'Parse error';
			break;
			case -32701:
				$_error_description = 'Parse error - Unsupported encoding';
			break;
			case -32702:
				$_error_description = 'Parse error - Invalid character for encoding';
			break;
			case -32600:
				$_error_description = 'Invalid Request';
			break;
			case -32601:
				$_error_description = 'Method not found';
			break;
			case -32602:
				$_error_description = 'Invalid params';
			break;
			case -32603:
				$_error_description = 'Internal error';
			break;
			case -32500:
				$_error_description = 'Application error';
			break;
			case -32400:
				$_error_description = 'System error';
			break;
			case -32300:
				$_error_description = 'Transport error';
			break;
			default:
				// This kind of errors should be between -32000 to -32099 to be declared as Server Error.
				// Error codes outside this interval will have no "Server Error" tag but "Comodojo Error" instead.
				if ($error_code >= -32099 AND  $error_code <= -32000) $_error_description = 'Server Error';
				else $_error_description = 'Comodojo Error: '.$error_description;
			break; 	
		}
		
		if ($this->transport == 'JSON') {
			$error = !$error_description ? Array('jsonrpc'=>'2.0','error'=> Array( 'code' => $error_code, 'message' => $_error_description), 'id' => $this->json_rpc_id) : Array('jsonrpc'=>'2.0','error'=> Array( 'code' => $error_code, 'message' => $_error_description, 'data' => $error_description), 'id' => $this->json_rpc_id);
		}
		else {
			$error = !$error_description ? Array("faultCode"=>$error_code,"faultString"=>$_error_description) : Array("faultCode"=>$error_code,"faultString"=>$_error_description." - ".$error_description);
		}
		return $error;
	}
	
	public function error($error_code, $error_description) {
		
		$message = $this->generate_error($error_code, $error_description);
		
		if ($this->transport == 'JSON') {
			$error = array2json($message);
			$contentType = 'application/json';
		}
		else {
			if ($this->is_native_rpc) {
				$error = xmlrpc_encode($message);
			}
			else {
				comodojo_load_resource('xmlRpcEncoder');
				$encoder = new xmlRpcEncoder();
				$error = $encoder->getError($message["faultCode"], $message["faultString"]);
			}
			$contentType = 'application/xml';
		}
		
		if (!is_null($this->aes)) {
			$error = $this->aes->encrypt($error);
		}
		
		set_header(Array(
			'statusCode'	=>	200,
			'ttl'			=> 	0,
			'contentType'	=>	$contentType,
			'charset'		=>	COMODOJO_DEFAULT_ENCODING
		), strlen($error));
		
		return $error;
		
	}
	
	private function decryptData ($envelope) {
		
		//if (COMODOJO_RPC_MODE != 1) throw new Exception('Encrypted transport not available', -32300);
		
		comodojo_load_resource('Crypt/AES');
		
		$this->aes = new Crypt_AES();
		$this->aes->setKey(COMODOJO_RPC_KEY);
		
		return $this->aes->decrypt($envelope);
		
	}
	
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_rpc_server
 */
 function loadHelper_rpc_server() { return false; }

?>