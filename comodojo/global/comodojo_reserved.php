<?php

/**
 * Reserved "comodojo" application and mehtods.
 * 
 * It serves requests in the reserver comodojo application space, such as comodojo.login or comodojo.version
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class comodojo_reserved extends application {
	
	public function init() {
		$this->add_application_method('login', 'login', Array('userName','userPass'), '',false);
		$this->add_application_method('logout', 'logout', Array(), '',false);
		$this->add_application_method('confirmRegistration', 'confirm_registration', Array('id','code'), '',false);
		$this->add_application_method('passwordRecovery', 'password_recovery', Array('email','code'), '',false);
		$this->add_application_method('applications', 'applications', Array(), '',false);
		$this->add_application_method('version', 'version', Array(), '',false);
		if (COMODOJO_RPC_PROXY_ENABLED) {
			$this->add_application_method('rpcproxy', 'rpcProxy', Array("server","rpc_method"), '',false);
		}
	}
	
	public function login($params) {
		if (is_null(COMODOJO_USER_ID)) throw new Exception("Unknown user or password mismatch", 2308);
		return Array(
			"userId" => COMODOJO_USER_ID,
			"userName" => COMODOJO_USER_NAME,
			"userRole" => COMODOJO_USER_ROLE,
			"completeName" => COMODOJO_USER_COMPLETE_NAME,
			"gravatar" => COMODOJO_USER_GRAVATAR,
			"email" => COMODOJO_USER_EMAIL,
			"birthday" => COMODOJO_USER_BIRTHDAY,
			"gender" => COMODOJO_USER_GENDER,
			"url" => COMODOJO_USER_URL
		);
	}
	
	public function confirm_registration($params) {
		comodojo_load_resource("registration");
		try {
			$re = new registration();
			$result = $re->confirm_request($params['id'],$params['code']);
		} catch (Exception $e) {
			throw $e;
		}
		return $result;
	}

	public function password_recovery($params) {
		comodojo_load_resource("users_management");
		try {
			$um = new users_management();
			$result = $um->user_recovery_confirm($params['email'],$params['code']);
		} catch (Exception $e) {
			throw $e;
		}
		return $result;
	}

	public function logout($params) {
		return true;
	}
	
	public function applications($params) {
		comodojo_load_resource('role_mapper');
		$mapper = new role_mapper();
		return $mapper->get_allowed_applications();
	}
	
	public function version($params) {
		return comodojo_version(isset($params['v']) ? $params['v'] : 'ALL');
	}

	public function rpcProxy($params) {

		comodojo_load_resource("rpc_client");

		$rpc_transport	= isset($params["rpc_transport"])	? strtoupper($params["rpc_transport"]) : 'XML';
		$key 			= isset($params["key"])				? $params["key"] : null;
		$port			= isset($params["port"])			? filter_var($params['port'], FILTER_VALIDATE_INT) : 80;
		$http_method	= isset($params["http_method"])		? strtoupper($params["http_method"]) : 'POST';
		$id				= isset($params["id"])				? filter_var($params['id'], FILTER_VALIDATE_BOOLEAN) : true;
		$parameters		= isset($params["params"])			? json2array($params["params"]) : Array();

		try {
			$rpc = new rpc_client($params["server"], $rpc_transport, $key, $port, $http_method);
			$result = $rpc->send($params["rpc_method"], $parameters, $id);
		} catch (Exception $e) {
			throw $e;
		}
		return $result;

	}
	
}

?>