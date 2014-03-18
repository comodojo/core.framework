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

class keychainmanager extends application {
	
	public function init() {
		$this->add_application_method('get_keychains_and_accounts', 'getKeychainsAndAccounts', Array(), 'No description yes, sorry', false);
		$this->add_application_method('get_account', 'getAccount', Array("account_name","keychain"), 'No description yes, sorry', false);
		$this->add_application_method('set_account', 'setAccount', Array("account_name","keychain"), 'No description yes, sorry', false);
		$this->add_application_method('get_account_keys', 'getAccountKeys', Array("account_name","keychain"), 'No description yes, sorry', false);
		$this->add_application_method('set_account_keys', 'setAccountKeys', Array("account_name","keyUser","keyPass","keychain"), 'No description yes, sorry', false);
		$this->add_application_method('add_account', 'addAccount', Array("account_name","keyUser","keyPass","keychain"), 'No description yes, sorry', false);
	}

	public function getKeychainsAndAccounts($params) {

		comodojo_load_resource('keychain');

		$result = Array();

		try {
			$k = new keychain();
			$kchains = $k->get_keychains();
			$accounts = $k->get_accounts();
		}
		catch (Exception $e){
			throw $e;
		}

		foreach ($kchains as $key => $value) {
			array_push($result, Array("id" => $value['keychain'], "name" => $value['keychain'], "keychain" => "krootnode", "type" => "keychain", "leaf" => false));
		}

		foreach ($accounts as $key => $value) {
			array_push($result, Array("id" => $value['id'], "name" => $value['account_name'], "keychain" => $value['keychain'], "type" => $value['type'], "leaf" => true));
		}		

		return $result;

	}

	public function getAccount($params) {

		comodojo_load_resource('keychain');

		try {
			$k = new keychain();
			$account = $k->get_account($params["account_name"],$params["keychain"]);
			comodojo_debug($account);
		}
		catch (Exception $e){
			throw $e;
		}

		return $account;

	}

	public function setAccount($params) {

		comodojo_load_resource('keychain');

		try {
			$k = new keychain();
			$account = $k->set_account($params["account_name"],$params["keychain"],
				isset($params["description"]) ? $params["description"] : null,
				isset($params["type"]) ? $params["type"] : null,
				isset($params["name"]) ? $params["name"] : null,
				isset($params["host"]) ? $params["host"] : null,
				isset($params["port"]) ? $params["port"] : null,
				isset($params["model"]) ? $params["model"] : null,
				isset($params["prefix"]) ? $params["prefix"] : null,
				isset($params["custom"]) ? $params["custom"] : null);
		}
		catch (Exception $e){
			throw $e;
		}

		return $account;

	}

	public function getAccountKeys($params) {

		comodojo_load_resource('keychain');

		try {
			$k = new keychain();
			$account = $k->get_account_keys($params["account_name"],$params["keychain"],isset($params["userPass"]) ? $params["userPass"] : null);
		}
		catch (Exception $e){
			throw $e;
		}

		return $account;

	}

	public function setAccountKeys($params) {

		comodojo_load_resource('keychain');

		try {
			$k = new keychain();
			$account = $k->set_account_keys($params["account_name"],$params["keyUser"],$params["keyPass"],$params["keychain"],isset($params["userPass"]) ? $params["userPass"] : null);
		}
		catch (Exception $e){
			throw $e;
		}

		return $account;

	}

	public function addAccount($params) {

		comodojo_load_resource('keychain');

		try {
			$k = new keychain();
			$account = $k->add_account($params["account_name"],$params["keyUser"],$params["keyPass"],$params["keychain"],
				isset($params["userPass"]) ? $params["userPass"] : null,
				isset($params["description"]) ? $params["description"] : null,
				isset($params["type"]) ? $params["type"] : null,
				isset($params["name"]) ? $params["name"] : null,
				isset($params["host"]) ? $params["host"] : null,
				isset($params["port"]) ? $params["port"] : null,
				isset($params["model"]) ? $params["model"] : null,
				isset($params["prefix"]) ? $params["prefix"] : null,
				isset($params["custom"]) ? $params["custom"] : null);
		}
		catch (Exception $e){
			throw $e;
		}

		return $account;
		
	}
	
}

?>
