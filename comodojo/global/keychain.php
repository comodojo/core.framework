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
			$result = $db->table('keychains')
				->keys(Array('account_name','description','keychain','keyUser','keyPass','type','name','host','port','model','prefix','custom'))
				->where("account_name","=",$account_name)
				->and_where("keychain","=",$keychain)
				->get();
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
			try {
				$aes = new Crypt_AES();
				$aes->setKey(COMODOJO_UNIQUE_IDENTIFIER);
				$result['result'][0]['keyUser'] = $aes->decrypt($result['result'][0]['keyUser']);
				$result['result'][0]['keyPass'] = $aes->decrypt($result['result'][0]['keyPass']);
			}
			catch (Exception $e){
				throw $e;
			}
		}
		else {
			$result['result'][0]['keyUser'] = null;
			$result['result'][0]['keyPass'] = null;
		}
		
		$events->record('keychain_get_account', $account_name.':'.$keychain, true);
		
		return $result['result'][0];
		
	}

	/**
	 * Get account username and password
	 * 
	 * This funciton will return an array containing account information like:
	 * 
	 * ('keyUser','keyPass')
	 * 
	 * @param	string	$account_name	The account name
	 * @param	string	$keychain		[optional] The keychain to ue (SYSTEM will address system chain)
	 * @param	string	$userPass		[optional] The user password (in clear) only if keychain != SYSTEM AND COMODOJO_USER_NAME==$keychain
	 * 
	 * @return	array 	Array containing all account information.
	 * 	
	 */
	public final function get_account_keys($account_name, $keychain=COMODOJO_USER_NAME, $userPass=null) {
		
		if (COMODOJO_USER_ROLE != 1 AND $keychain != COMODOJO_USER_NAME) throw new Exception("Not enough privileges to access selected account", 2404);
		
		if (is_null($account_name) /*OR ($keychain != 'SYSTEM' AND is_null($userPass))*/) throw new Exception("Missing account name or password", 2407);
		
		try {
			$db = new database();
			$result = $db->table('keychains')
				->keys(Array('keyUser','keyPass','account_name','keychain'))
				->where("account_name","=",$account_name)
				->and_where("keychain","=",$keychain)
				->get();
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
		elseif ($keychain == COMODOJO_USER_NAME AND is_null($userPass)) {
			comodojo_debug("Missing password for user keychain",'ERROR','keychain');
			throw new Exception("Missing account name or password", 2407);
		}
		elseif (COMODOJO_USER_ROLE == 1 AND $keychain == "SYSTEM") {
			try {
				$aes = new Crypt_AES();
				$aes->setKey(COMODOJO_UNIQUE_IDENTIFIER);
				$result['result'][0]['keyUser'] = $aes->decrypt($result['result'][0]['keyUser']);
				$result['result'][0]['keyPass'] = $aes->decrypt($result['result'][0]['keyPass']);
			}
			catch (Exception $e){
				throw $e;
			}
		}
		else {
			comodojo_debug("Undefined or invalid account",'ERROR','keychain');
			$events->record('keychain_get_account', $account_name.':'.$keychain, false);
			throw new Exception("Undefined or invalid account", 2405);
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
			$result = $db->table('keychains')->keys(Array('id','account_name','type','keychain'));
			if (COMODOJO_USER_ROLE != 1) $result = $result->where("keychain","=",COMODOJO_USER_NAME);
			$result = $result->get();
		}
		catch (Exception $e){
			throw $e;
		}	
		
		return $result['result'];
		
	}
	
	/**
	 * Edit attributes for existing account
	 * 
	 * @param	string	$account_name	The account name
	 * @param	string	$keychain		[optional] The keychain to ue (SYSTEM will address system chain)
	 * @param	string	$description	[optional]
	 * @param	string	$type			[optional]
	 * @param	string	$name			[optional]
	 * @param	string	$host			[optional]
	 * @param	string	$port			[optional]
	 * @param	string	$model			[optional]
	 * @param	string	$prefix			[optional]
	 * @param	string	$custom			[optional]
	 * 
	 * @return	bool	True in case of success, exception otherwise
	 * 
	 */
	public final function set_account($account_name, $keychain=COMODOJO_USER_NAME,
		$description = null,
		$type = null,
		$name = null,
		$host = null,
		$port = null,
		$model = null,
		$prefix = null,
		$custom = null) {
		
		if (
			(COMODOJO_USER_ROLE != 1 AND $keychain != COMODOJO_USER_NAME)
			OR 
			(COMODOJO_USER_ROLE == 1 AND 
				($keychain != COMODOJO_USER_NAME 
				AND 
				$keychain != 'SYSTEM')
			)
		) throw new Exception("Not enough privileges to create or edit account in selected keychain", 2406);
		
		if (is_null($account_name)) throw new Exception("Missing account name or password", 2407);
		
		$events = new events(true);
		
		$description = empty($description) ? null : $description;
		$type = empty($type) ? null : $type;
		$name = empty($name) ? null : $name;
		$host = empty($host) ? null : $host;
		$port = empty($port) ? null : $port;
		$model = empty($model) ? null : $model;
		$prefix = empty($prefix) ? null : $prefix;
		$custom = empty($custom) ? null : $custom;

		//Check if account exists
		try {
			$db = new database();
			$result = $db->table('keychains')
				->keys(Array('type','name','host','port','model','prefix','custom'))
				->where("account_name","=",$account_name)
				->and_where("keychain","=",$keychain)
				->get();
			
			$db->clean();

			if ($result['resultLength'] == 0) {
				comodojo_debug("Undefined or invalid account",'ERROR','keychain');
				$events->record('keychain_set_account', $account_name.':'.$keychain, false);
				throw new Exception("Undefined or invalid account", 2405);
			}
			else {
				$result = $db->table('keychains')
					->keys(Array('description','type','name','host','port','model','prefix','custom'))
					->values(Array($description,$type,$name,$host,$port,$model,$prefix,is_array($custom) ? array2json($custom) : $custom))
					->where('account_name','=',$account_name)
					->and_where('keychain','=',$keychain)
					->update();
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
	 * Edit account credentials
	 * 
	 * @param	string	$account_name	The account name
	 * @param	string	$keyUser		UserName (will be encrypted)
	 * @param	string	$keyPass		Password (will be encrypted)
	 * @param	string	$keychain		[optional] The keychain to ue (SYSTEM will address system chain)
	 * @param	string	$userPass		[optional] The user password for encryption (set it to null if SYSTEM chain)
	 * 
	 * @return	bool	True in case of success, exception otherwise
	 * 
	 */
	public final function set_account_keys($account_name, $keyUser, $keyPass, $keychain=COMODOJO_USER_NAME, $userPass=null) {

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
		
		$aes = new Crypt_AES();
		$aes->setKey($keychain == 'SYSTEM' ? COMODOJO_UNIQUE_IDENTIFIER : $id);
		
		$encrypted_keyUser = $aes->encrypt($keyUser);
		$encrypted_keyPass = $aes->encrypt($keyPass);
		
		//Check if account exists
		try {
			$db = new database();
			$result = $db->table('keychains')
				->keys(Array('keyUser','keyPass'))
				->where("account_name","=",$account_name)
				->and_where("keychain","=",$keychain)
				->get();
			
			$db->clean();

			if ($result['resultLength'] == 0) {
				comodojo_debug("Undefined or invalid account",'ERROR','keychain');
				$events->record('keychain_set_account', $account_name.':'.$keychain, false);
				throw new Exception("Undefined or invalid account", 2405);
			}
			else {
				$result = $db->table('keychains')
					->keys(Array('keyUser','keyPass'))
					->values(Array($encrypted_keyUser,$encrypted_keyPass))
					->where('account_name','=',$account_name)
					->and_where('keychain','=',$keychain)
					->update();
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
	 * Add new account to keychain
	 * 
	 * @param	string	$account_name	The account name
	 * @param	string	$keyUser		UserName (will be encrypted)
	 * @param	string	$keyPass		Password (will be encrypted)
	 * @param	string	$keychain		[optional] The keychain to ue (SYSTEM will address system chain)
	 * @param	string	$userPass		[optional] The user password for encryption (set it to null if SYSTEM chain)
	 * @param	string	$description	[optional]
	 * @param	string	$type			[optional]
	 * @param	string	$name			[optional]
	 * @param	string	$host			[optional]
	 * @param	string	$port			[optional]
	 * @param	string	$model			[optional]
	 * @param	string	$prefix			[optional]
	 * @param	string	$custom			[optional] 
	 *
	 * @return	bool	True in case of success, exception otherwise
	 * 
	 */
	public final function add_account($account_name, $keyUser, $keyPass, $keychain=COMODOJO_USER_NAME, $userPass=null,
		$description = null,
		$type = null,
		$name = null,
		$host = null,
		$port = null,
		$model = null,
		$prefix = null,
		$custom = null) {
		
		if (
			(COMODOJO_USER_ROLE != 1 AND $keychain != COMODOJO_USER_NAME)
			OR 
			(COMODOJO_USER_ROLE == 1 AND 
				($keychain != COMODOJO_USER_NAME 
				AND 
				$keychain != 'SYSTEM')
			)
		) throw new Exception("Not enough privileges to create or edit account in selected keychain", 2406);
		
		if (is_null($account_name) OR is_null($keyUser) OR is_null($keyPass)) throw new Exception("Missing account name or password", 2407);
		
		$events = new events(true);
		
		$description = empty($description) ? null : $description;
		$type = empty($type) ? null : $type;
		$name = empty($name) ? null : $name;
		$host = empty($host) ? null : $host;
		$port = empty($port) ? null : $port;
		$model = empty($model) ? null : $model;
		$prefix = empty($prefix) ? null : $prefix;
		$custom = empty($custom) ? null : $custom;

		//Check if account exists
		try {
			$db = new database();
			$result = $db->table('keychains')
				->keys(Array('type'))
				->where("account_name","=",$account_name)
				->and_where("keychain","=",$keychain)
				->get();
			
			$db->clean();

			if ($result['resultLength'] != 0) {
				comodojo_debug("Duplicate account!",'ERROR','keychain');
				$events->record('keychain_add_account', $account_name.':'.$keychain, false);
				throw new Exception("Duplicate account!", 2411);
			}
			else {
				$result = $db->table('keychains')
					->keys(Array('account_name','keyUser','keyPass','keychain','description','type','name','host','port','model','prefix','custom'))
					->values(Array($account_name,$keyUser,$keyPass,$keychain,$description,$type,$name,$host,$port,$model,$prefix,is_array($custom) ? array2json($custom) : $custom))
					->store();
			}
			
		}
		catch (Exception $e){
			$events->record('keychain_add_account', $account_name.':'.$keychain, false);
			throw $e;
		}
		
		$events->record('keychain_add_account', $account_name.':'.$keychain, true);
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
			$result = $db->table('keychains')
				->where("account_name","=",$account_name)
				->and_where("keychain","=",$keychain)
				->delete();
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
	 * A keychain is automatically created when first account is set.
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
				$result = $db->table('keychains')->keys("keychain")->group_by("keychain")->where("keychain","!=",'SYSTEM')->and_where("keychain","!=",COMODOJO_USER_NAME)->get();
			}
			catch (Exception $e){
				throw $e;
			}
			array_push($result['result'], Array("keychain"=>"SYSTEM"));
			array_push($result['result'], Array("keychain"=>COMODOJO_USER_NAME));
			return $result['result'];
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
			$result = $db->table('keychains')
				->keys(Array('keyUser','keyPass','type','name','host','port','model','prefix','custom'))
				->where("account_name","=",$system_account_name)
				->and_where("keychain","=","SYSTEM")
				->get();
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