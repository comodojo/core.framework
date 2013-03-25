<?php

/**
 * keychain.php
 * 
 * Accounts engine, the default keychains access layer.
 * 
 * There are two different types of keychain:
 * 
 * - user chain:	every user has it's own keychain (it's auto-determined by server).
 * 					Passwords are encrypted using user's random masterkey (user private identifier).
 * 
 * - system chain:	accounts in the system chain are available sitewide BUT editable by administrators only.
 * 					Passwords are encrypted using site PRIVATE_IDENTIFIER; accounts in the system chain CAN BE
 * 					USED directly from system (i.e. from dblayer). 
 * 
 * Each user account is identified by the couple account_name/keychain. The second parameter (keychain) is automatically determined
 * from username EXCEPT if differently specified.
 * 
 * Access logic is: administrator can read all except (keyUser and keyPass if not owned directly) but can delete everything.
 * 					users can read only their accounts.
 * 
 * PLEASE NOTE: to use system account directly (i.e. by cron or dblayer) there is a reserved method: KEYCHAIN::use_system_account().
 * 				It doesn't check if user has administrator role and get passwords in clear. SO... USE WITH CAUTION!
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
*/

comodojo_load_resource('database');
comodojo_load_resource('events');
comodojo_load_resource('Crypt/AES');
comodojo_load_resource('users_management');

class keychain {
		
	/**
	 * Get account details (system/user) IN CLEAR
	 * 
	 * This funciton will return an array containing account information like:
	 * 
	 * ('name','description','keyUser','keyPass','type','host','port','model'.'prefix','custom')
	 * 
	 * PLEASE NOTE: in case of administrator who query user account, fields keyUser and keyPass ARE NOT POPULATED
	 * 
	 * @param	string	$account_name	The account name
	 * @param	string	$keychain		[optional] The keychain to ue (SYSTEM will address system chain)
	 * @param	string	$userPass		[optional] The user password (in clear) only if keychain != SYSTEM AND COMODOJO_USER_NAME==$keychain
	 * 
	 * @return	array 	Array containing all account information.
	 * 	
	 */
	public final function get_account($account_name, $keychain=COMODOJO_USER_NAME, $userPass=null) {
		
		if (COMODOJO_USER_ROLE != 1 AND $keychain != COMODOJO_USER_NAME) throw new Exception("Not enough privileges to access selected account", 2404);
		
		if (is_null($account_name) /*OR ($keychain != 'SYSTEM' AND is_null($userPass))*/) throw new Exception("Missing account name or password", 2407);
		
		try {
			$db = new database();
			$db->setTable('keychains');
			$db->setKeys(Array('account_name','description','keychain','keyUser','keyPass','type','name','host','port','model','prefix','custom'));
			$db->setWhere(Array(Array("account_name","=",$account_name),"AND",Array("keychain","=",$keychain)));
			$result = $db->read();
		}
		catch (Exception $e){
			throw $e;
		}
		
		$events = new events(true);
		
		if ($result['resultLength'] == 0) {
			comodojo_debug("Undefined or invalid account",'ERROR','keychain');
			$events->record('keychain_get_account', $account_name.':'.$keychain, false);
			throw new Exception("Undefined or invalid account", 2405);
		}
		
		if ($keychain == COMODOJO_USER_NAME AND !is_null($userPass)) {
			try {
				$um = new users_management();
				$id = $um->get_private_identifier(COMODOJO_USER_NAME, $userPass);
				$aes = new Crypt_AES();
				$aes->setKey($id);
				$result['result'][0]['keyUser'] = $aes->decrypt($result['result'][0]['keyUser']);
				$result['result'][0]['keyPass'] = $aes->decrypt($result['result'][0]['keyPass']);
			}
			catch (Exception $e){
				throw $e;
			}
		}
		elseif (COMODOJO_USER_ROLE == 1 AND $keychain == "SYSTEM") {
			$aes->setKey(COMODOJO_UNIQUE_IDENTIFIER);
			$result['result'][0]['keyUser'] = $aes->decrypt($result['result'][0]['keyUser']);
			$result['result'][0]['keyPass'] = $aes->decrypt($result['result'][0]['keyPass']);
		}
		else {
			$result['result'][0]['keyUser'] = null;
			$result['result'][0]['keyPass'] = null;
		}
		
		$events->record('keychain_get_account', $account_name.':'.$keychain, true);
		
		return $result['result'][0];
		
	}
	
	/**
	 * Get all accounts (list) that belong to single keychain or (in case of administrator) the
	 * full account list.
	 * 
	 * @return	array 	Array containing a set of couples (account,keychain).
	 * 
	 */
	public final function get_accounts() {
		
		try {
			$db = new database();
			$db->setTable('keychains');
			$db->setKeys(Array('id','account_name','type','keychain'));
			if (COMODOJO_USER_ROLE != 1) $db->setWhere(Array("keychain","=",COMODOJO_USER_NAME));
			$result = $db->read();
		}
		catch (Exception $e){
			throw $e;
		}	
		
		return $result['result'];
		
	}
	
	/**
	 * Create new account or edit an existing one
	 * 
	 * @param	string	$account_name	The account name
	 * @param	string	$keyUser		UserName (will be encrypted)
	 * @param	string	$keyPass		Password (will be encrypted)
	 * @param	string	$keychain		[optional] The keychain to ue (SYSTEM will address system chain)
	 * @param	string	$userPass		[optional] The user password for encryption (set it to null if SYSTEM chain)
	 * @param	array	$parameters		[optional] Array of additional optional parameters ('description','type','host','port','model'.'prefix','custom')
	 * 
	 * @return	bool	True in case of success, exception otherwise
	 * 
	 */
	public final function set_account($account_name, $keyUser, $keyPass, $keychain=COMODOJO_USER_NAME, $userPass=null, $parameters=Array()) {
		
		if (
			(COMODOJO_USER_ROLE != 1 AND $keychain != COMODOJO_USER_NAME)
			OR 
			(COMODOJO_USER_ROLE == 1 AND 
				($keychain != COMODOJO_USER_NAME 
				AND 
				$keychain != 'SYSTEM')
			)
		) throw new Exception("Not enough privileges to create or edit account in selected keychain", 2406);
		
		if ($keychain == COMODOJO_USER_NAME AND (is_null($account_name) OR is_null($userPass))) throw new Exception("Missing account name or password", 2407);
		
		if (is_null($account_name) OR is_null($keyUser) OR is_null($keyPass)) throw new Exception("Missing account keyname or keypass", 2409);
		
		if ($keychain == COMODOJO_USER_NAME) {
			try {
				$um = new users_management();
				$id = $um->get_private_identifier(COMODOJO_USER_NAME, $userPass);
			}
			catch (Exception $e){
				throw $e;
			}
			if (is_null($id)) throw new Exception("Wrong password", 2410);
		}
		
		$events = new events(true);
		
		$description = isset($parameters['description']) ? $parameters['description'] : null;
		$type = isset($parameters['type']) ? $parameters['type'] : 'GENERIC';
		$name = isset($parameters['name']) ? $parameters['name'] : null;
		$host = isset($parameters['host']) ? $parameters['host'] : null;
		$port = isset($parameters['port']) ? $parameters['port'] : null;
		$model = isset($parameters['model']) ? $parameters['model'] : null;
		$prefix = isset($parameters['prefix']) ? $parameters['prefix'] : null;
		$custom = isset($parameters['custom']) ? $parameters['custom'] : null;
		
		$aes = new Crypt_AES();
		$aes->setKey($keychain == 'SYSTEM' ? COMODOJO_UNIQUE_IDENTIFIER : $id);
		
		$encrypted_keyUser = $aes->encrypt($keyUser);
		$encrypted_keyPass = $aes->encrypt($keyPass);
		
		//Check if account exists
		try {
			$db = new database();
			$db->setTable('keychains');
			$db->setKeys(Array('keyUser','keyPass','type','name','host','port','model','prefix','custom'));
			$db->setWhere(Array(Array("account_name","=",$account_name),"AND",Array("keychain","=",$keychain)));
			$result = $db->read();
			
			//$db->cleanup()
			if ($result['resultLength'] == 0) {
				$db->setKeys(Array('account_name','description','keyUser','keyPass','type','name','host','port','model','prefix','custom','keychain'));
				$db->setValues(Array($account_name,$description,$encrypted_keyUser,$encrypted_keyPass,$type,$name,$host,$port,$model,$prefix,is_array($custom) ? array2json($custom) : $custom,$keychain));
				$result = $db->store();
			}
			else {
				$db->setKeys(Array('description','keyUser','keyPass','type','name','host','port','model','prefix','custom'));
				$db->setValues(Array($description,$encrypted_keyUser,$encrypted_keyPass,$type,$name,$host,$port,$model,$prefix,is_array($custom) ? array2json($custom) : $custom));
				$db->setWhere(Array(Array('account_name','=',$account_name), 'AND', Array('keychain','=',$keychain)));
				$result = $db->update();
			}
			
		}
		catch (Exception $e){
			$events->record('keychain_set_account', $account_name.':'.$keychain, false);
			throw $e;
		}
		
		$events->record('keychain_set_account', $account_name.':'.$keychain, true);
		return true;
		
	}
	
	/**
	 * Delete an existing account.
	 * 
	 * PLEASE NOTE: users can delete only accounts in their keychains; administrators can delete any account (included other user
	 * accounts and system ones)
	 * 
	 * @param	string	$account_name	The account name
	 * @param	string	$keychain		[optional] The keychain to ue (SYSTEM will address system chain)
	 * 
	 * @return	bool	True in case of success, exception otherwise
	 * 
	 */
	public final function delete_account($account_name, $keychain=COMODOJO_USER_NAME) {
		
		if (COMODOJO_USER_ROLE != 1 AND $keychain != COMODOJO_USER_NAME) throw new Exception("Not enough privileges to delete selected account", 2402);
		
		if (is_null($account_name)) throw new Exception("Missing account name or password", 2407);
		
		$events = new events(true);
		
		try {
			$db = new database();
			$db->setTable('keychains');
			$db->setWhere(Array(Array("account_name","=",$account_name),"AND",Array("keychain","=",$keychain)));
			$result = $db->delete();
		}
		catch (Exception $e){
			$events->record('keychain_delete_account', $account_name.':'.$keychain, false);
			throw $e;
		}
		
		if ($result['affectedRows'] == 0) {
			$events->record('keychain_delete_account', $account_name.':'.$keychain, false);
			throw new Exception("Undefined or invalid account to delete", 2403);
		}
		
		$events->record('keychain_delete_account', $account_name.':'.$keychain, true);
		return true;
		
	}
	
	/**
	 * Get all currently defined keychains (list).
	 * 
	 * PLEASE NOTE: an user will see only it's own keychain; administrator will have a full list from this method
	 * 
	 * @return	array 	Array containing all currently defined keychains.
	 * 
	 */
	public final function get_keychains() {
		
		if (COMODOJO_USER_ROLE != 1) {
			return Array(COMODOJO_USER_NAME);
		}
		else {
			try {
				$db = new database();
				$db->setTable('keychains');
				$db->setKeys(Array("keychain"));
				$db->setGroup("keychain");
				$result = $db->read();
			}
			catch (Exception $e){
				throw $e;
			}
		}
		
	}
	
	/**
	 * Use a system account WITHOUT check if currently logged in user is administrator or not.
	 * 
	 * PLEASE NOTE: this method SHOULD NOT BE USED to get system account details but only to use system accounts
	 * in cron jobs, database auto queries, ...
	 * 
	 * @param	string	$system_account_name	The account name
	 * 
	 * @return	array 	Array containing all account information.
	 * 
	 */
	public final function use_system_account($system_account_name) {
		
		if (is_null($system_account_name)) throw new Exception("Missing system account name", 2408);
		
		$events = new events(true);
		
		try {
			$db = new database();
			$db->setTable('keychains');
			$db->setKeys(Array('keyUser','keyPass','type','name','host','port','model','prefix','custom'));
			$db->setWhere(Array(Array("account_name","=",$system_account_name),"AND",Array("keychain","=","SYSTEM")));
			$result = $db->read();
		}
		catch (Exception $e){
			$events->record('keychain_use_system_account', $account_name.':'.$keychain, false);
			throw $e;
		}
		
		if ($result['resultLength'] == 0) {
			$events->record('keychain_use_system_account', $account_name.':'.$keychain, false);
			throw new Exception("Undefined or invalid account in system keychain", 2401);
		}
		
		$aes = new Crypt_AES();
		$aes->setKey(COMODOJO_UNIQUE_IDENTIFIER);
		
		$result['result'][0]['keyUser'] = $aes->decrypt($result['result'][0]['keyUser']);
		$result['result'][0]['keyPass'] = $aes->decrypt($result['result'][0]['keyPass']);
		
		$events->record('keychain_use_system_account', $account_name.':'.$keychain, true);
		
		return $result['result'][0];
		
	}
		
}

function loadHelper_keychain() { return false; }

?>