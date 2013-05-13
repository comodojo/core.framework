<?php

/**
 * database.php
 * 
 * The comodojo database abstraction layer.
 * 
 * This class lets you interact with database like:
 * - MYSQL (MYSLQ, MYSQLI, PDO)
 * - SQLITE (PDO)
 * - POSTGRESQL (pg)
 * - MSSQL* (dblib)
 * - DB2*
 * - ORACLE*
 * - INFORMIX*
 * 
 * * = support is limited because of difficulties in testing on those
 *     databases. Any contribution is welcome!
 * 
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class database {


/*********************** PUBLIC VARS *********************/
	
/*********************** PUBLIC VARS *********************/

/********************** PRIVATE VARS *********************/
	/**
	 * Echo each query in php error log
	 * 
	 * This is a last-level debug option, use with caution.
	 * 
	 * @var	bool
	 */
	private $log_whole_query = true;

	/**
	 * The hostname of the database to connect to
	 * @var	string	contains an IP address, a hostname, ...
	 */
	private $dbHost = false;
	
	/**
	 * The port of the database to connect to
	 * @var	integer	contains a valid TCP port number (up to 65535)
	 */
	private $dbPort = false;
	
	/**
	 * Database name
	 * @var	string
	 */
	private $dbName = false;
	
	/**
	 * Database table prefix (if any)
	 * @var	string
	 */
	private $dbPrefix = "";
	
	/**
	 * Username for database auth
	 * @var	string
	 */
	private $dbUserName = false;
	
	/**
	 * Queries' limit conditvar	string
	 */
	private $dbUserPass = false;
	
	/**
	 * Database type (datamodel)
	 * @var	string
	 */
	private $dbDataModel = false;

	/**
	 * Fetch results as:
	 * - ASSOC: associative array
	 * - NUM: positional array
	 * - BOTH: both associative and positional array
	 */
	private $fetch = 'ASSOC';

	/**
	 * Table to manipulate
	 * @var	string
	 */
	private $table = null;

	/**
	 * Enable transaction id return
	 * @var	bool
	 */
	private $return_id = false;

	/**
	 * Use SELECT DISTINCT instead of SELECT
	 * @var	bool
	 */
	private $distinct = false;

	/**
	 * Return raw data from query instead of an array
	 * @var bool
	 */
	private $return_raw = false;

	/**
	 * Queries' keys
	 * @var	string
	 */
	private $keys = null;
	private $keys_array = Array();

	private $values = null;
	private $values_array = Array();

	private $group_by = null;

	private $order_by = null;

	private $having = null;

	private $where = null;

	private $join = null;

	private $using = null;

	private $on = null;

	private $transform = Array();

	private $columns = Array();


/********************** PRIVATE VARS *********************/

/********************* PUBLIC METHODS ********************/
	/**
	 * Constructor class;
	 * it will prepare database environment (with custom parameters, if specified) and connect to the database.
	 */
	public function __construct($dbHost=false, $dbDataModel=false, $dbName=false, $dbPort=false, $dbPrefix=false, $dbUserName=false, $dbUserPass=false) {
			
		$this->dbHost = !$dbHost ? COMODOJO_DB_HOST : $dbHost;
		$this->dbDataModel = strtoupper(!$dbDataModel ? COMODOJO_DB_DATA_MODEL : $dbDataModel);
		$this->dbName = !$dbName ? COMODOJO_DB_NAME : $dbName;
		$this->dbPort = !$dbPort ? COMODOJO_DB_PORT : $dbPort;
		$this->dbPrefix = !$dbPrefix ? COMODOJO_DB_PREFIX : $dbPrefix;
		$this->dbUserName = !$dbUserName ? COMODOJO_DB_USER : $dbUserName;
		$this->dbUserPass = !$dbUserPass ? COMODOJO_DB_PASSWORD : $dbUserPass;
		
		comodojo_debug('Connecting to database '.$this->dbName.'@'.$this->dbHost.':'.$this->dbPort.' as '.$this->dbUserName.' with '.$this->dbDataModel.' datamodel','INFO','database');
		
		try {
			$this->connect();
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	/**
	 * Destructor (disconnect db handlers)
	 */
	public function __destruct() {
		comodojo_debug('Disconnecting from database '.$this->dbName.'@'.$this->dbHost.':'.$this->dbPort,'INFO','database');
		$this->disconnect();
	}

	public final function return_id($value=true) {

		$this->return_id = !$value ? false : true;

		return $this;

	}

	public final function distinct($value=true) {

		$this->distinct = !$value ? false : true;

		return $this;

	}

	public final function return_raw($value=true) {

		$this->return_raw = !$value ? false : true;

		return $this;

	}

	public final function table($table_name_or_array) {

		$table_pattern = in_array($this->dbDataModel, Array('MYSQLI','MYSQL','MYSQL_PDO')) ? "`*_DBPREFIX_*%s`" : "*_DBPREFIX_*%s";

		if (empty($table_name_or_array)) {
			comodojo_debug('Invalid table name','ERROR','database');
			throw new Exception('Invalid table name',1010);
		}
		elseif (is_array($table_name_or_array)) {
			$this->table = Array();
			foreach ($table_name_or_array as $table_value) {
				$_table = array();
				array_push($_table, sprintf($table_pattern,$table_value));
				$this->table = implode(',', $_table);
			}
		}
		else {
			$this->table = sprintf($table_pattern,trim($table_name_or_array));
		}

		comodojo_debug('Using table: '.$this->table,'INFO','database');

		return $this;

	}

	public function keys($key_name_or_array) {

		$keys_pattern = in_array($this->dbDataModel, Array('MYSQLI','MYSQL','MYSQL_PDO')) ? "`%s`" : "%s";
		
		if (empty($key_name_or_array)) {
			comodojo_debug('Invalid key/s','ERROR','database');
			throw new Exception('Invalid key/s',1011);
		}
		elseif (is_array($key_name_or_array)) {
			foreach ($key_name_or_array as $key=>$key_val) {
				$_key_val = sprintf($keys_pattern,$key_val);
				$key_name_or_array[$key] = $_key_val;
				array_push($this->keys_array,$_key_val);
			}
			$this->keys = implode(',',$key_name_or_array);
		}
		else {
			$k = trim($key_name_or_array);
			$this->keys = $k == "*" ? "*" : sprintf($keys_pattern,$k);
			array_push($this->keys_array,$this->keys);
		}

		comodojo_debug('Keys are now: '.$this->keys,'INFO','database');

		return $this;
		
	}

	public function values($value_or_array) {

		$value_string_pattern = "'%s'";
		$value_null_pattern = 'NULL';

		if (is_null($value_or_array) OR !isset($value_or_array)) {
			comodojo_debug('Invalid value (empty)','ERROR','database');
			throw new Exception('Invalid value',1014);
		}
		
		if (is_null($this->values)) {
			$this->values = array();
		}

		if (is_array($value_or_array)) {
			foreach ($value_or_array as $key=>$key_val) {
				if (is_array($key_val)) {
					$value_or_array[$key] = sprintf($value_string_pattern,array2json($key_val));
				}
				elseif (is_bool($key_val) === true) {
					switch ($this->dbDataModel) {
						case 'MYSQL':
						case 'MYSQLI':
						case 'MYSQL_PDO':
						case 'INFORMIX_PDO':
						case 'POSTGRESQL':
						case 'DB2':
							$value_or_array[$key] = $key_val;
							break;
						case 'DBLIB_PDO':
						case 'ORACLE_PDO':
						case 'SQLITE_PDO':
						default:
							$value_or_array[$key] = !$key_val ? 0 : 1;
							break;
					}
				}
				elseif	(is_numeric($key_val))	$value_or_array[$key] = $key_val;
				elseif	(is_null($key_val))		$value_or_array[$key] = $value_null_pattern;
				else {
					switch ($this->dbDataModel) {
						case 'MYSQL':
							$value_or_array[$key] = sprintf($value_string_pattern,mysql_escape_string($key_val));
							break;
						case 'MYSQLI':
							$value_or_array[$key] = sprintf($value_string_pattern,$this->dbHandler->escape_string($key_val));
							break;
						case 'POSTGRESQL':
							$value_or_array[$key] = sprintf($value_string_pattern,pg_escape_string($key_val));
							break;
						case 'DB2':
							$value_or_array[$key] = sprintf($value_string_pattern,db2_escape_string($key_val));
							break;
						case 'MYSQL_PDO':
						case 'ORACLE_PDO':
						case 'SQLITE_PDO':
						case 'INFORMIX_PDO':
						case 'DBLIB_PDO':
							$_value = $this->dbHandler->quote($key_val);
							$value_or_array[$key] = !$_value ? sprintf($value_string_pattern,$key_val) : $_value;
							break;
						default:
							$value_or_array[$key] = sprintf($value_string_pattern,$key_val);
							break;
					}
				}
			}
			$_values = '('.implode(',',$value_or_array).')';
			array_push($this->values, $_values);
			array_push($this->values_array, $value_or_array);
		}
		else {
			if	(is_bool($value_or_array) === true) {
					switch ($this->dbDataModel) {
						case 'MYSQL':
						case 'MYSQLI':
						case 'MYSQL_PDO':
						case 'INFORMIX_PDO':
						case 'POSTGRESQL':
						case 'DB2':
							$k = $value_or_array;
							break;
						case 'DBLIB_PDO':
						case 'ORACLE_PDO':
						case 'SQLITE_PDO':
						default:
							$k = !$value_or_array ? 0 : 1;
							break;
					}
				}
			elseif	(is_numeric($value_or_array))	$k = $value_or_array;
			elseif	(is_null($value_or_array))		$k = $value_null_pattern;
			else {
				switch ($this->dbDataModel) {
					case 'MYSQL':
						$k = sprintf($value_string_pattern,mysql_escape_string($value_or_array));
						break;
					case 'MYSQLI':
						$k = sprintf($value_string_pattern,$this->dbHandler->escape_string($value_or_array));
						break;
					case 'POSTGRESQL':
						$k = sprintf($value_string_pattern,pg_escape_string($value_or_array));
						break;
					case 'DB2':
						$k = sprintf($value_string_pattern,db2_escape_string($value_or_array));
						break;
					case 'MYSQL_PDO':
					case 'ORACLE_PDO':
					case 'SQLITE_PDO':
					case 'INFORMIX_PDO':
					case 'DBLIB_PDO':
						$_value = $this->dbHandler->quote($value_or_array);
						$k = !$_value ? sprintf($value_string_pattern,$value_or_array) : $_value;
						break;
					default:
						$k = sprintf($value_string_pattern,$value_or_array);
						break;

				}
			}
			$_values = '('.$k.')';
			array_push($this->values, $_values);
			array_push($this->values_array, $k);
		}

		comodojo_debug('Added values: '.$_values,'INFO','database');

		return $this;

	}

	public function where($column, $operator, $value) {
		
		try {
			$this->where = "WHERE ".$this->add_where_clause($column, $operator, $value);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $this;

	}

	public function and_where($column, $operator, $value) {
		
		try {
			$this->where .= " AND ".$this->add_where_clause($column, $operator, $value);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $this;

	}

	public function or_where($column, $operator, $value) {
		
		try {
			$this->where .= " OR ".$this->add_where_clause($column, $operator, $value);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $this;

	}

	/**
	 * Add a join clause to the query.
	 *
	 * WARNING: not all databases support joins like RIGHT, NATURAL or FULL.
	 * This method WILL NOT alert or throw exception in case of unsupported join,
	 * this kind of check will be implemented in next versions.
	 *
	 * @param string $join_type
	 * @param string $table
	 */
	public function join($join_type, $table) {
		
		$join = strtoupper($join_type);

		$join_pattern = "%sJOIN %s";

		$join_type_list = Array('INNER','NATURAL','CROSS','LEFT','RIGHT','LEFT OUTER','RIGHT OUTER','FULL OUTER');

		if (!in_array($join, $join_type_list) OR empty($table)) {
			comodojo_debug('Invalid parameters for database::join','ERROR','database');
			throw new Exception('Invalid parameters for database::join',1019);
		}

		if (is_null($this->join)) $this->join = sprintf($join_pattern,$join." ",$table);
		else $this->join .= " ".sprintf($join_pattern,$join." ",$table);
		
		return $this;

	}

	public function using($column_name_or_array) {

		$using_pattern = "USING (%s)";

		if (empty($column_name_or_array)) {
			comodojo_debug('Invalid parameters for database::using','ERROR','database');
			throw new Exception('Invalid parameters for database::using',1020);
		}
		
		$this->using = sprintf($using_pattern,is_array($column_name_or_array) ? implode(',', $column_name_or_array) : $column_name_or_array);
		
		comodojo_debug('Join using:: '.$this->using,'INFO','database');

		return $this;

	}

	public function on($column_one, $operator, $column_two) {

		try {
			$this->where = "ON ".$this->add_on_clause($column_one, $operator, $column_two);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $this;

	}

	public function and_on($column_one, $operator, $column_two) {

		try {
			$this->where = "AND ".$this->add_on_clause($column_one, $operator, $column_two);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $this;

	}

	public function or_on($column_one, $operator, $column_two) {

		try {
			$this->where = "OR ".$this->add_on_clause($column_one, $operator, $column_two);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $this;

	}

	public function order_by($column, $direction=null) {

		switch ($this->dbDataModel) {
			//case ("MYSQL"):
			//case ("MYSQLI"):
			//case ("MYSQL_PDO"):
			//case ("POSTGRESQL"):
			//case ("DB2"):
			//case ("DBLIB_PDO"):
			//case ("ORACLE_PDO"):
			//case ("INFORMIX_PDO"):
			case ("SQLITE_PDO"):
				$order_column_pattern = "%s COLLATE NOCASE%s";
				break;
			default:
				$order_column_pattern = "%s%s";
				break;
    	}

		if (empty($column)) {
			comodojo_debug('Invalid order by column','ERROR','database');
			throw new Exception('Invalid order by column',1012);
		}

		$c = trim($column);
		if (!is_null($direction)) $d = strtoupper($direction);
		else $d = null;
		
		if (is_null($this->order_by)) $this->order_by = "ORDER BY ".sprintf($order_column_pattern,$c,$d);
		else $this->order_by = ",".sprintf($order_column_pattern,$c,$d);

		/*
		elseif (is_array($order_column_or_array)) {
			foreach ($order_column_or_array as $key=>$key_val) {
				$order_column_or_array[$key] = sprintf($order_column_pattern,$key_val);
			}
			$this->order_by = "ORDER BY ".implode(',',$order_column_or_array);
		}
		else {
			$k = trim($order_column_or_array);
			$this->order_by = "ORDER BY ".sprintf($order_column_pattern,$k);
		}
		*/

		comodojo_debug('Result will be ordered by: '.$this->order_by,'INFO','database');

		return $this;

	}

	public function group_by($group_column_or_array) {

		$group_column_pattern = "%s";

		if (empty($group_column_or_array)) {
			comodojo_debug('Invalid group by column','ERROR','database');
			throw new Exception('Invalid group by column',1013);
		}
		elseif (is_array($group_column_or_array)) {
			foreach ($group_column_or_array as $key=>$key_val) {
				$group_column_or_array[$key] = sprintf($group_column_pattern,$key_val);
			}
			$this->group_by = "GROUP BY ".implode(',',$group_column_or_array);
		}
		else {
			$k = trim($group_column_or_array);
			$this->group_by = "ORDER BY ".sprintf($group_column_pattern,$k);
		}

		comodojo_debug('Result will be grouped by: '.$this->group_by,'INFO','database');

		return $this;

	}

	/**
	 * Set the having clause in a sql statement.
	 * Differently from other methods, $having_clause_or_array should
	 * contain the FULL CLAUSE.
	 *
	 * @param string|array $having_clause_or_array ...
	 */
	public function having($having_clause_or_array) {

		$having_column_pattern = "%s";

		if (empty($having_clause_or_array)) {
			comodojo_debug('Invalid having clause','ERROR','database');
			throw new Exception('Invalid having clause',1028);
		}
		elseif (is_array($having_clause_or_array)) {
			foreach ($having_clause_or_array as $key=>$key_val) {
				$having_clause_or_array[$key] = sprintf($having_column_pattern,$key_val);
			}
			$this->having = "HAVING ".implode(' AND ',$having_clause_or_array);
		}
		else {
			$k = trim($having_clause_or_array);
			$this->having = "HAVING ".sprintf($having_clause_or_array,$k);
		}

		comodojo_debug('Having set to: '.$this->having,'INFO','database');

		return $this;

	}

	public function column($name, $type, $extra_params=Array()) {

		$type = strtoupper($type);

		$length = isset($extra_params['length']) ? $extra_params['length'] : null;
		$unsigned = isset($extra_params['unsigned']) ? $extra_params['unsigned'] : null;
		$zerofill = isset($extra_params['zerofill']) ? $extra_params['zerofill'] : null;
		$charset = isset($extra_params['charset']) ? $extra_params['charset'] : null;
		$collate = isset($extra_params['collate']) ? $extra_params['collate'] : null;
		$null = isset($extra_params['null']) ? $extra_params['null'] : null;
		$default = isset($extra_params['default']) ? $extra_params['default'] : null;
		$autoincrement = isset($extra_params['autoincrement']) ? $extra_params['autoincrement'] : null;
		$unique = isset($extra_params['unique']) ? $extra_params['unique'] : null;
		$primary_key = isset($extra_params['primary']) ? $extra_params['primary'] : null;

		$supported_types = Array('STRING','INTEGER','FLOAT','DECIMAL','BOOL','TIME','DATE','TIMESTAMP','TEXT','BLOB');

		if (empty($name) OR !in_array($type, $supported_types)) {
			comodojo_debug('Invalid syntax for column definition','ERROR','database');
			throw new Exception('Invalid syntax for column definition',1026);
		}

		switch ($this->dbDataModel) {
			case ("MYSQL"):
			case ("MYSQLI"):
			case ("MYSQL_PDO"):
				$query_pattern = "`%s` %s%s%s%s %s%s%s%s%s";
				switch($type) {
					case 'STRING':	$_type = "VARCHAR"; break;
					case 'INTEGER':	$_type = "INTEGER"; break;
					case 'FLOAT':	$_type = "FLOAT"; break;
					case 'BOOL':	$_type = "BOOL"; break;
					case 'TIME':	$_type = "TIME"; break;
					case 'DATETIME':$_type = "DATETIME"; break;
					case 'DATE':	$_type = "DATE"; break;
					case 'TIMESTAMP':$_type= "TIMESTAMP"; break;
					case 'TEXT':	$_type = "TEXT"; break;
					case 'BLOB':	$_type = "BLOB"; break;
				}
				$_length = is_null($length) ? null : "(".intval($length).")";
				$_attr_1 = is_null($unsigned) ? (is_null($charset) ? null : " ".$charset) : ' UNSIGNED';
				$_attr_2 = is_null($zerofill) ? (is_null($collate) ? null : " ".$collate) : ' ZEROFILL';
				$_null = is_null($null) ? null : ($null === false ? ' NOT NULL' : ' NULL');
				$_default = is_null($default) ? null : ' DEFAULT '.$default;
				$_autoincrement = is_null($autoincrement) ? null : ' AUTO_INCREMENT';
				$_unique = is_null($unique) ? null : ' UNIQUE';
				$_primarykey = is_null($primary_key) ? null : ' PRIMARY KEY';
				array_push($this->columns,sprintf($query_pattern,$name,$_type,$_length,$_attr_1,$_attr_2,$_null,$_default,$_autoincrement,$_unique,$_primarykey));
			break;
			case ("POSTGRESQL"):
				$query_pattern = "%s %s%s%s%s %s%s%s%s%s";
				switch($type) {
					case 'STRING':	$_type = "VARCHAR"; break;
					case 'INTEGER':	$_type = "INTEGER"; break;
					case 'FLOAT':	$_type = "FLOAT4"; break;
					case 'BOOL':	$_type = "BOOL"; break;
					case 'TIME':	$_type = "TIME"; break;
					case 'DATETIME':$_type = "DATETIME"; break;
					case 'DATE':	$_type = "DATE"; break;
					case 'TIMESTAMP':$_type= "TIMESTAMP"; break;
					case 'TEXT':	$_type = "TEXT"; break;
					case 'BLOB':	$_type = "BYTEA"; break;
				}
				$_length = is_null($length) ? null : "(".intval($length).")";
				$_attr_1 = is_null($unsigned) ? (is_null($charset) ? null : " ".$charset) : ' UNSIGNED';
				$_attr_2 = is_null($zerofill) ? (is_null($collate) ? null : " ".$collate) : ' ZEROFILL';
				$_null = is_null($null) ? null : ($null === false ? ' NOT NULL' : ' NULL');
				$_default = is_null($default) ? null : ' DEFAULT '.$default;
				$_autoincrement = is_null($autoincrement) ? null : ' AUTO_INCREMENT';
				$_unique = is_null($unique) ? null : ' UNIQUE';
				$_primarykey = is_null($primary_key) ? null : ' PRIMARY KEY';
				array_push($this->columns,sprintf($query_pattern,$name,$_type,$_length,$_attr_1,$_attr_2,$_null,$_default,$_autoincrement,$_unique,$_primarykey));
			break;
			case ("ORACLE_PDO"):
				$query_pattern = "%s %s%s%s%s %s%s%s%s%s";
				switch($type) {
					case 'STRING':	$_type = "VARCHAR"; break;
					case 'INTEGER':	$_type = "NUMBER"; break;
					case 'FLOAT':	$_type = "FLOAT"; break;
					case 'BOOL':	$_type = "NUMBER(1)"; break;
					case 'TIME':	$_type = "DATE"; break;
					case 'DATETIME':$_type = "DATE"; break;
					case 'DATE':	$_type = "DATE"; break;
					case 'TIMESTAMP':$_type= "DATE"; break;
					case 'TEXT':	$_type = "CLOB"; break;
					case 'BLOB':	$_type = "BLOB"; break;
				}
				$_length = is_null($length) ? null : "(".intval($length).")";
				$_attr_1 = is_null($unsigned) ? (is_null($charset) ? null : " ".$charset) : ' UNSIGNED';
				$_attr_2 = is_null($zerofill) ? (is_null($collate) ? null : " ".$collate) : ' ZEROFILL';
				$_null = is_null($null) ? null : ($null === false ? ' NOT NULL' : ' NULL');
				$_default = is_null($default) ? null : ' DEFAULT '.$default;
				$_autoincrement = is_null($autoincrement) ? null : ' AUTO_INCREMENT';
				$_unique = is_null($unique) ? null : ' UNIQUE';
				$_primarykey = is_null($primary_key) ? null : ' PRIMARY KEY';
				array_push($this->columns,sprintf($query_pattern,$name,$_type,$_length,$_attr_1,$_attr_2,$_null,$_default,$_autoincrement,$_unique,$_primarykey));
			break;
			case ("DB2"):
				$query_pattern = "%s %s%s%s%s %s%s%s%s%s";
				switch($type) {
					case 'STRING':	$_type = "VARCHAR"; break;
					case 'INTEGER':	$_type = "INTEGER"; break;
					case 'FLOAT':	$_type = "REAL"; break;
					case 'BOOL':	$_type = "INTEGER(1)"; break;
					case 'TIME':	$_type = "TIME"; break;
					case 'DATETIME':$_type = "DATE"; break;
					case 'DATE':	$_type = "DATE"; break;
					case 'TIMESTAMP':$_type= "TIMESTAMP"; break;
					case 'TEXT':	$_type = "CLOB"; break;
					case 'BLOB':	$_type = "BLOB"; break;
				}
				$_length = is_null($length) ? null : "(".intval($length).")";
				$_attr_1 = is_null($unsigned) ? (is_null($charset) ? null : " ".$charset) : ' UNSIGNED';
				$_attr_2 = is_null($zerofill) ? (is_null($collate) ? null : " ".$collate) : ' ZEROFILL';
				$_null = is_null($null) ? null : ($null === false ? ' NOT NULL' : ' NULL');
				$_default = is_null($default) ? null : ' DEFAULT '.$default;
				$_autoincrement = is_null($autoincrement) ? null : ' AUTO_INCREMENT';
				$_unique = is_null($unique) ? null : ' UNIQUE';
				$_primarykey = is_null($primary_key) ? null : ' PRIMARY KEY';
				array_push($this->columns,sprintf($query_pattern,$name,$_type,$_length,$_attr_1,$_attr_2,$_null,$_default,$_autoincrement,$_unique,$_primarykey));
			break;
			case ("DBLIB_PDO"):
				$query_pattern = "%s %s%s%s%s %s%s%s%s%s";
				switch($type) {
					case 'STRING':	$_type = "NVARCHAR"; break;
					case 'INTEGER':	$_type = "INTEGER"; break;
					case 'FLOAT':	$_type = "FLOAT"; break;
					case 'BOOL':	$_type = "BIT"; break;
					case 'TIME':	$_type = "TIME"; break;
					case 'DATETIME':$_type = "DATETIME"; break;
					case 'DATE':	$_type = "DATE"; break;
					case 'TIMESTAMP':$_type= "TIMESTAMP"; break;
					case 'TEXT':	$_type = "NVARCHAR"; break;
					case 'BLOB':	$_type = "BLOB"; break;
				}
				$_length = is_null($length) ? null : "(".intval($length).")";
				$_attr_1 = is_null($unsigned) ? (is_null($charset) ? null : " ".$charset) : ' UNSIGNED';
				$_attr_2 = is_null($zerofill) ? (is_null($collate) ? null : " ".$collate) : ' ZEROFILL';
				$_null = is_null($null) ? null : ($null === false ? ' NOT NULL' : ' NULL');
				$_default = is_null($default) ? null : ' DEFAULT '.$default;
				$_autoincrement = is_null($autoincrement) ? null : ' AUTO_INCREMENT';
				$_unique = is_null($unique) ? null : ' UNIQUE';
				$_primarykey = is_null($primary_key) ? null : ' PRIMARY KEY';
				array_push($this->columns,sprintf($query_pattern,$name,$_type,$_length,$_attr_1,$_attr_2,$_null,$_default,$_autoincrement,$_unique,$_primarykey));
			break;
			case ("SQLITE_PDO"):
				$query_pattern = "`%s` %s%s %s%s%s%s";
				switch($type) {
					case 'STRING':	$_type = "TEXT"; break;
					case 'INTEGER':	$_type = "INTEGER"; break;
					case 'FLOAT':	$_type = "REAL"; break;
					case 'BOOL':	$_type = "INTEGER"; break;
					case 'TIME':	$_type = "TEXT"; break;
					case 'DATETIME':$_type = "TEXT"; break;
					case 'DATE':	$_type = "TEXT"; break;
					case 'TIMESTAMP':$_type= "TEXT"; break;
					case 'TEXT':	$_type = "TEXT"; break;
					case 'BLOB':	$_type = "BLOB"; break;
				}
				$_attr_1 = is_null($unsigned) ? (is_null($collate) ? null : " COLLATE ".$collate) : ' UNSIGNED';
				$_null = is_null($null) ? null : ($null === false ? ' NOT NULL' : ' NULL');
				$_default = is_null($default) ? null : ' DEFAULT '.$default;
				//$_autoincrement = is_null($autoincrement) ? null : ' AUTO_INCREMENT';
				$_unique = is_null($unique) ? null : ' UNIQUE';
				$_primarykey = is_null($primary_key) ? null : ' PRIMARY KEY';
				array_push($this->columns,sprintf($query_pattern,$_type,$_attr_1,$_null,$_default,$_unique,$_primarykey));
				break;
			case ("INFORMIX_PDO"):
			break;
			default: 
				break;
    	}

	}

	public function transform($column_name_or_array) {

		if (empty($column_name_or_array)) {
			comodojo_debug('Invalid parameters for database::transform','ERROR','database');
			throw new Exception('Invalid parameters for database::transform',1022);
		}
		
		if (is_array($column_name_or_array)) $this->transform = $column_name_or_array;
		else array_push($this->transform, $column_name_or_array);
		
		comodojo_debug('Values to transform: '.implode(',',$this->transform),'INFO','database');

		return $this;

	}

	public function clean($deep=false) {
		
		if ($deep) {
			$this->fetch = 'ASSOC';
			$this->return_id = false;
			$thisò->distinct = false;
			$this->return_raw = false;
		}
		
		$this->table = null;
		$this->keys = null;
		$this->keys_array = Array();
		$this->values = null;
		$this->values_array = Array();
		$this->group_by = null;
		$this->order_by = null;
		$this->having = null;
		$this->where = null;
		$this->join = null;
 		$this->using = null;
 		$this->on = null;
 		$this->transform = Array();
 		$this->columns = Array();

	}

	/**
	 * Shot a query (raw format) to database.
	 * 
	 * db::query will remap keyword *_DBPREFIX_* in $this->dbPrefix param, just in case...
	 * 
	 * @param	string	$query	The query, a plain SQL expression
	 * 
	 * @return	array			An array containing:
	 * 							- result
	 * 							- resultLength
	 * 							- transactionId (if any)
	 * 							- affectedRows
	 */
	public final function query($query) {
    		
		$query = str_replace("*_DBPREFIX_*",$this->dbPrefix,$query);
	
		if ($this->log_whole_query) comodojo_debug('Ready to shot query: '.$query,'INFO','database');
		else comodojo_debug('Ready to shot query...','INFO','database');	
		
		switch ($this->dbDataModel){
			
			case 'MYSQL':
				$response = @mysql_query($query, $this->dbHandler);
				if (!$response) {
					$error_no = mysql_errno();
					$error = mysql_error();
					comodojo_debug('Cannot perform query!','ERROR','database');
					comodojo_debug('ERROR_NO: '.$error_no,'ERROR','database');
					comodojo_debug('ERROR: '.$error,'ERROR','database');
					throw new Exception($error,$error_no);
				}
				$result = $this->return_raw ? $response : $this->resource_to_array($response, $this->return_id ? mysql_insert_id($this->dbHandler) : false, @mysql_affected_rows($this->dbHandler));
			break;

			case 'MYSQLI':
				$response = $this->dbHandler->query($query);
				if (!$response) {
					$error_no = $this->dbHandler->errno;
					$error = $this->dbHandler->error;
					comodojo_debug('Cannot perform query!','ERROR','database');
					comodojo_debug('ERROR_NO: '.$error_no,'ERROR','database');
					comodojo_debug('ERROR: '.$error,'ERROR','database');
					throw new Exception($error,$error_no);
				}
				$result = $this->return_raw ? $response : $this->resource_to_array($response, $this->return_id ? $this->dbHandler->insert_id : false, $this->dbHandler->affected_rows);
			break;
			
			case "MYSQL_PDO":
			case "ORACLE_PDO":
		 	case "SQLITE_PDO":
			case "INFORMIX_PDO":
			case "DBLIB_PDO":
				switch (strtoupper($this->fetch)) {
					case 'NUM': $fetch = PDO::FETCH_NUM; break;
					case 'ASSOC': $fetch = PDO::FETCH_ASSOC; break;
					default: $fetch = PDO::FETCH_BOTH; break;
				}
				try {
					$response = $this->dbHandler->query($query,$fetch);
				}
				catch (PDOException $e) {
					$error_no = $e->getCode();
					$error = $e->getMessage();
					comodojo_debug('Cannot perform query!','ERROR','database');
					comodojo_debug('ERROR_NO: '.$error_no,'ERROR','database');
					comodojo_debug('ERROR: '.$error,'ERROR','database');
					throw new Exception($error,$error_no);
				}
				$result = $this->return_raw ? $response : $this->resource_to_array($response, $this->return_id ? $this->dbHandler->lastInsertId() : false, @$response->rowCount());
			break;
			
			case 'DB2':
				$response = db2_exec($dbHandler,$query);
				if (!$response) {
					$error_no = false;
					$error = db2_stmt_error();
					comodojo_debug('Cannot perform query!','ERROR','database');
					comodojo_debug('ERROR_NO: '.$error_no,'ERROR','database');
					comodojo_debug('ERROR: '.$error,'ERROR','database');
					throw new Exception($error,$error_no);
				}
				$result = $this->return_raw ? $response : $this->resource_to_array($response, $this->return_id ? db2_last_insert_id($this->dbHandler) : false, @db2_num_rows($data));
			break;

			case 'POSTGRESQL':
				$response = @pg_query($this->dbHandler,$query);
				if (!$response) {
					$error_no = 0;
					$error = pg_last_error();
					comodojo_debug('Cannot perform query!','ERROR','database');
					comodojo_debug('ERROR: '.$error,'ERROR','database');
					throw new Exception($error,$error_no);
				}
				$result = $this->buildResultSet($response, $this->return_id ? pg_last_oid($response) : false, @pg_affected_rows($response));
			break;
			
			default:
				comodojo_debug('Cannot perform query!','ERROR','database');
				comodojo_debug('ERROR_NO: 1001','ERROR','database');
				comodojo_debug('ERROR: Unknown database datamodel','ERROR','database');
				throw new Exception('Unknown database datamodel',1001);
			break;
			
		}

    	// update query counter (if any)
		if (isset($_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['QUERIES'])) $_SESSION[COMODOJO_PUBLIC_IDENTIFIER]['QUERIES']++;
		
		return $result;

	}

	
	public function get($limit=0, $offset=0) {

		if (is_null($this->table) OR empty($this->keys)) {
			comodojo_debug('Invalid parameters for database::get','ERROR','database');
			throw new Exception('Invalid parameters for database::get',1004);
		}

		$query_pattern = "%s %s FROM %s%s%s%s%s%s";

		/*
		switch ($this->dbDataModel) {
			case ("MYSQL"):
			case ("MYSQLI"):
			case ("MYSQL_PDO"):
			case ("POSTGRESQL"):
			case ("ORACLE_PDO"):
		*/
				$_select = $this->distinct ? "SELECT DISTINCT" : "SELECT";
				$_keys = $this->keys;
				$_table = $this->table;

				if (!is_null($this->join)) {
					if (!is_null($this->using)) $_join = $this->join." ".$this->using;
					elseif (!is_null($this->on)) $_join = $this->join." ".$this->on;
					else $_join = $this->join;
				}
				else $_join = null;
				
				$_where = is_null($this->where) ? null : " ".$this->where;
				$_group_by = is_null($this->group_by) ? null : " ".$this->group_by;
				$_having = is_null($this->having) ? null : " ".$this->having;
				$_order_by = is_null($this->order_by) ? null : " ".$this->order_by;

				$_limit = $limit === 0 ? null : (' LIMIT '.($offset === 0 ? intval($limit) : intval($offset).",".intval($limit)));
		
		/*		
				break;
			case ("DB2"):
			case ("DBLIB_PDO"):
				break;
			case ("SQLITE_PDO"):
				break;
			case ("INFORMIX_PDO"):
				break;
			default: 
				break;
    	}
    	*/

	    $query = sprintf($query_pattern,$_select,$_keys,$_table,$_join,$_where,$_group_by,$_having,$_order_by,$_limit);

	    try {
			$queryResult = $this->query($query);
		}
		catch (Exception $e) {
			throw $e;
        }
		
		return $queryResult;

	}

	public function store() {

		if (is_null($this->table) OR empty($this->values)) {
			comodojo_debug('Invalid parameters for database::store','ERROR','database');
			throw new Exception('Invalid parameters for database::store',1002);
		}

		if (sizeof($this->values) == 1) {
			$query_pattern = "INSERT INTO %s%s VALUES %s";
			$_keys = ( $this->keys == "*" OR is_null($this->keys) ) ? NULL : "(".$this->keys.")";
			$query = sprintf($query_pattern,$this->table," ".$_keys,($this->values[0]));
		}
		else {
			switch ($this->dbDataModel) {
				case ("MYSQL"):
				case ("MYSQLI"):
				case ("MYSQL_PDO"):
				case ("POSTGRESQL"):
				case ("DB2"):
				case ("DBLIB_PDO"):
					$query_pattern = "INSERT INTO %s%s VALUES%s";
					$_keys = ( $this->keys == "*" OR is_null($this->keys) ) ? NULL : "(".$this->keys.")";
					$query = sprintf($query_pattern,$this->table," ".$_keys," ".implode(',', $this->values));
				break;
				
				case ("SQLITE_PDO"):
					$query_pattern = "INSERT INTO %s%s SELECT %s";
					$_keys = ( $this->keys == "*" OR is_null($this->keys) ) ? NULL : "(".$this->keys.")";
					$_values = implode(' UNION SELECT ', $this->values);
					$query = sprintf($query_pattern,$this->table," ".$_keys,$_values);
				break;
					
				case ("ORACLE_PDO"):
					$query_pattern = "INSERT INTO %s%s SELECT %s";
					$_keys = ( $this->keys == "*" OR is_null($this->keys) ) ? NULL : "(".$this->keys.")";
					$_values = implode(' FROM DUAL UNION ALL SELECT ', $this->values)." FROM DUAL";
					$query = sprintf($query_pattern,$this->table," ".$_keys,$_values);
				break;

				case ("INFORMIX_PDO"):
					comodojo_debug('Unsupported method for this database (multiple insert - set - on Informix)','ERROR','database');
					throw new Exception('Unsupported method for this database',1015);
				break;

	    	}
		}

		try {
			$queryResult = $this->query($query);
		}
		catch (Exception $e) {
			throw $e;
        }
		
		return $queryResult;
	}

	public function update() {

		if (is_null($this->table) OR empty($this->keys_array) OR empty($this->values_array)) {
			comodojo_debug('Invalid parameters for database::update','ERROR','database');
			throw new Exception('Invalid parameters for database::update',1024);
		}

		if (sizeof($this->keys_array) != sizeof($this->values_array)) {
			comodojo_debug('Could not update multiple values with database::update','ERROR','database');
			throw new Exception('Could not update multiple values with database::update',1025);
		}

		$query_pattern = "UPDATE %s SET %s%s";

		$query_content_array = Array();

		foreach ($this->keys_array as $position => $key) {
			array_push($query_content_array,$key.'='.$this->values_array[$position]);
		}

		$query = sprintf($query_pattern,$this->table,implode(',',$query_content_array),is_null($this->where) ? null : " ".$this->where);

		try {
			$queryResult = $this->query($query);
		}
		catch (Exception $e) {
			throw $e;
        }
		
		return $queryResult;

	}

	public function delete() {

		if (is_null($this->table)) {
			comodojo_debug('Invalid parameters for database::delete','ERROR','database');
			throw new Exception('Invalid parameters for database::delete',1018);
		}

		$query_pattern = "DELETE FROM %s %s";

		$query = sprintf($query_pattern,$this->table,$this->where);
		
		try {
			$queryResult = $this->query($query);
		}
		catch (Exception $e) {
			throw $e;
        }
		
		return $queryResult;

	}

	public function emtpy() {
		
		if (is_null($this->table)) {
			comodojo_debug('Invalid parameters for database::empty','ERROR','database');
			throw new Exception('Invalid parameters for database::empty',1016);
		}
		
		$query_pattern = "DELETE FROM %s WHERE TRUE";
		$query = sprintf($query_pattern,$this->table);

		try {
			$queryResult = $this->query($query);
		}
		catch (Exception $e) {
			throw $e;
        }
		
		return $queryResult;

	}

	public function create_table($name, $if_not_exists=false, $engine=false) {

		if (is_null($name) OR empty($this->columns)) {
			comodojo_debug('Invalid parameters for database::create_table','ERROR','database');
			throw new Exception('Invalid parameters for database::create_table',1027);
		}

		$table_pattern = in_array($this->dbDataModel, Array('MYSQLI','MYSQL','MYSQL_PDO')) ? "`*_DBPREFIX_*%s`" : "*_DBPREFIX_*%s";
		$table = sprintf($table_pattern,trim($name));

		switch ($this->dbDataModel) {
			case 'MYSQL':
			case 'MYSQLI':
			case 'MYSQL_PDO':
				$query_pattern = "CREATE TABLE%s %s (%s)%s";
				$query = sprintf($query_pattern,!$if_not_exists ? null : " IF NOT EXISTS",$table,implode(',',$this->columns),!$engine ? null : ' ENGINE '.$engine);
			break;
			case 'INFORMIX_PDO':
			case 'POSTGRESQL':
			case 'DB2':
			case 'DBLIB_PDO':
			case 'ORACLE_PDO':
			case 'SQLITE_PDO':
			default:
				$query_pattern = "CREATE TABLE%s %s (%s)";
				$query = sprintf($query_pattern,!$if_not_exists ? null : " IF NOT EXISTS",$table,implode(',',$this->columns));
			break;
		}

		try {
			$queryResult = $this->query($query);
		}
		catch (Exception $e) {
			throw $e;
        }
		
		return $queryResult;

	}

	public function drop_table($if_exists=false) {

		if (is_null($this->table)) {
			comodojo_debug('Invalid parameters for database::drop','ERROR','database');
			throw new Exception('Invalid parameters for database::drop',1023);
		}
		
		$query_pattern = "DROP TABLE %s%s";

		switch ($this->dbDataModel) {
			case 'MYSQL':
			case 'MYSQLI':
			case 'MYSQL_PDO':
			case 'POSTGRESQL':
			case 'DBLIB_PDO':
			case 'ORACLE_PDO':
			case 'SQLITE_PDO':
				$query = sprintf($query_pattern, $if_exists ? 'IF EXISTS ' : null, $this->table);
				break;
			case 'INFORMIX_PDO':
			case 'DB2':
			default:
				$query = sprintf($query_pattern,null,$this->table);
				break;
		}

		try {
			$queryResult = $this->query($query);
		}
		catch (Exception $e) {
			throw $e;
        }
		
		return $queryResult;

	}

	/*
	public function alter_rename_table() {}

	public function alter_add_column() {}

	public function alter_add_index() {}

	public function alter_remove_column() {}

	public function alter_remove_index() {}
	*/

/********************* PUBLIC METHODS ********************/

/********************* PRIVATE METHODS *******************/
	/**
	 * Connect to the database using selected datamodel, or throw an exception.
	 * connect() uses following class parameters:
	 * 	- db::$dbDataModel
	 * 	- db::$dbHost
	 * 	- db::$dbPort
	 * 	- db::$dbUserName
	 * 	- db::$dbUserPass
	 * 	- db::$dbName
	 */
    private function connect() {
    		
    	switch ($this->dbDataModel) {
				
			case ("MYSQL"):
				$this->dbHandler = @mysql_connect($this->dbHost.":".$this->dbPort, $this->dbUserName, $this->dbUserPass);
				if (!$this->dbHandler) throw new Exception(mysql_error(),mysql_errno()); 
				$this->dbSelect = @mysql_select_db($this->dbName, $this->dbHandler);
				if (!$this->dbSelect) throw new Exception(mysql_error(),mysql_errno());
			break;

			case ("MYSQLI"):
				$this->dbHandler = new mysqli($this->dbHost, $this->dbUserName, $this->dbUserPass, $this->dbName, $this->dbPort);
				if ($this->dbHandler->connect_error) throw new Exception($this->dbHandler->connect_error,$this->dbHandler->connect_errno); 
			break;
			
			case ("MYSQL_PDO"):
				$dsn="mysql:host=".$this->dbHost.";port=".$this->dbPort .";dbname=".$this->dbName;
				try {
					$this->dbHandler = new PDO($dsn,$this->dbUserName,$this->dbUserPass);	
				}
				catch (PDOException $e){
					throw new Exception($e->getMessage(),$e->getCode());
				}
			break;
			
			case ("ORACLE_PDO"):
				$dsn="oci:dbname=".$this->dbHost.":".$this->dbPort."/".$this->dbName;
				try {
					$this->dbHandler = new PDO($dsn,$this->dbUserName,$this->dbUserPass);	
				}
				catch (PDOException $e) {
					throw new Exception($e->getMessage(),$e->getCode());
				}
			break;
				
			case ("SQLITE_PDO"):
				$dsn="sqlite:".(defined('COMODOJO_SITE_PATH') ? COMODOJO_SITE_PATH : COMODOJO_BOOT_PATH).COMODOJO_HOME_FOLDER.COMODOJO_FILESTORE_FOLDER.$this->dbName;
				try {
					$this->dbHandler = new PDO($dsn);	
				}
				catch (PDOException $e) {
					throw new Exception($e->getMessage(),$e->getCode());
				}
			break;
				
			case ("INFORMIX_PDO"):
				list($db_host,$db_server)=explode(":",$this->dbHost);
				$dsn="informix:host=" . $db_host . ";server=" . $db_serv . ";service=" . $this->dbPort . ";database=".$this->dbName;
				try {
					$this->dbHandler = new PDO($dsn,$this->dbUserName,$this->dbUserPass);	
				}
				catch (PDOException $e) {
					throw new Exception($e->getMessage(),$e->getCode());
				}
			break;
			
			case ("DB2"):
				$dsn="ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=".$this->dbName.";HOSTNAME=".$this->dbHost.";PORT=".$this->dbPort.";PROTOCOL=TCPIP;UID=".$this->dbUserName.";PWD=".$this->dbUserPass.";";
				$this->dbHandler = db2_pconnect($dsn,$this->dbUserName,$this->dbUserPass);
				if (!$this->dbHandler) throw new Exception(db2_conn_errormsg(),db2_conn_error());
			break;

			case ("DBLIB_PDO"):
				$dsn = "dblib:host=".$this->dbHost.":".$this->dbPort.";dbname=".$this->dbName;
				try {
					$this->dbHandler = new PDO($dsn,$this->dbUserName,$this->dbUserPass);
				}
				catch (PDOException $e) {
					throw new Exception($e->getMessage(),$e->getCode());
				}
			break;

			case ("POSTGRESQL"):
				$dsn = "host=".$this->dbHost." port=".$this->dbPort." dbname=".$this->dbName." user=".$this->dbUserName." password=".$this->dbUserPass;
				$this->dbHandler = @pg_connect($dsn);
				if (!$this->dbHandler) throw new Exception(pg_last_error($this->dbHandler)); 
			break;

    	}
		
		comodojo_debug('Connection to database '.$this->dbName.' established.','INFO','database');
		
    }
	
    /**
	 * Disconnect from the database.
	 */
	private function disconnect() {
		
		switch($this->dbDataModel) {
			case ("MYSQL"):
				@mysql_close($this->dbHandler);
				$this->dbHandler=null;
			break;
			case ("MYSQLI"):
				$this->dbHandler->close(); 
			break;
			case ("MYSQL_PDO"):
			case ("ORACLE_PDO"):
			case ("SQLITE_PDO"):
			case ("INFORMIX_PDO"):
			case ("DBLIB_PDO"):
				$this->dbHandler=null;
			break;
			case ("DB2"):
				@db2_close($this->dbHandler);
			break;
			case ("POSTGRESQL"):
				@pg_close($this->dbHandler);
				$this->dbHandler=null;
			break;
		}
		
	}
	
	private function add_where_clause($column, $operator, $value) {

		$to_return = null;

		$operator = strtoupper($operator);

		if (is_array($column) AND is_array($value)) {

			$clause_pattern = "(%s %s %s)";

			if (!in_array($operator, Array('AND','OR'))) {
				comodojo_debug('Invalid syntax for a where clause: wrong operator for a nested clause','ERROR','database');
				throw new Exception('Invalid syntax for a where clause',1017);
			}
			
			if (sizeof($column)!=3 OR sizeof($value)!=3) {
				comodojo_debug('Invalid syntax for a where clause: wrong array size for a nested clause','ERROR','database');
				throw new Exception('Invalid syntax for a where clause',1017);
			}

			try {
				$_column = $this->add_where_clause($column[0],$column[1],$column[2]);
				$_value = $this->add_where_clause($value[0],$value[1],$value[2]);
			}
			catch (Exception $e) {
				throw $e;
			}

			$to_return = sprintf($clause_pattern, $_column, $_operator, $_value);

		}
		elseif (is_scalar($column) AND is_array($value)) {

			switch($operator) {
				case 'IN':
					$clause_pattern = in_array($this->dbDataModel, Array('MYSQLI','MYSQL','MYSQL_PDO')) ? "`%s` IN (%s)" : "%s IN (%s)";
					$_value = "'".implode("','", $value)."'";
					$to_return = sprintf($clause_pattern, $column, $_value);
					break;
				case 'BETWEEN':
					$clause_pattern = in_array($this->dbDataModel, Array('MYSQLI','MYSQL','MYSQL_PDO')) ? "`%s` BETWEEN %s AND %s" : "%s BETWEEN %s AND %s";
					$to_return = sprintf($clause_pattern, $value[0], $value[1]);
					break;
				default:
					comodojo_debug('Invalid syntax for a where clause: wrong operator for array of values','ERROR','database');
					throw new Exception('Invalid syntax for a where clause',1017);
					break;
			}

		}
		elseif (is_scalar($column) AND is_scalar($value)) {
			
			$clause_pattern = in_array($this->dbDataModel, Array('MYSQLI','MYSQL','MYSQL_PDO')) ? "`%s` %s %s" : "%s %s %s";

			if ($operator == 'IS') {
				$_column = $column;
				$_operator = $operator;
				$_value = (is_null($value) OR $value == 'NULL') ? 'IS NULL' : 'IS NOT NULL';
			}
			elseif ($operator == 'LIKE' OR $operator == 'NOT LIKE') {
				$_column = $column;
				$_operator = $operator;
				$_value = "'".$value."'";
			}
			else {
				$_column = $column;
				$_operator = $operator;
				if	(is_bool($value) === true) {
					switch ($this->dbDataModel) {
						case 'MYSQL':
						case 'MYSQLI':
						case 'MYSQL_PDO':
						case 'INFORMIX_PDO':
						case 'POSTGRESQL':
						case 'DB2':
							$_value = $value;
							break;
						case 'DBLIB_PDO':
						case 'ORACLE_PDO':
						case 'SQLITE_PDO':
						default:
							$_value = !$value ? 0 : 1;
							break;
					}
				}
				elseif	(is_numeric($value))	$_value = $value;
				elseif	(is_null($value))		$_value = "NULL";
				else {
					/*
					switch ($this->dbDataModel) {
						case 'MYSQL':
							$value_or_array[$key] = sprintf($value_string_pattern,mysql_escape_string($key_val));
							break;
						case 'MYSQLI':
							$value_or_array[$key] = sprintf($value_string_pattern,$this->dbHandler->escape_string($key_val));
							break;
						case 'POSTGRESQL':
							$value_or_array[$key] = sprintf($value_string_pattern,pg_escape_string($key_val));
							break;
						case 'MYSQL_PDO':
						case 'ORACLE_PDO':
						case 'SQLITE_PDO':
						case 'INFORMIX_PDO':
						case 'DBLIB_PDO':
							$_value = $this->dbHandler->quote($key_val);
							$value_or_array[$key] = !$_value ? sprintf($value_string_pattern,$key_val) : $_value;
							break;
						default:
							$value_or_array[$key] = sprintf($value_string_pattern,$key_val);
							break;
					}
					*/
					$_value = "'".$value."'";
				}

			}

			$to_return = sprintf($clause_pattern, $_column, $_operator, $_value);			

		}
		else {
			comodojo_debug('Invalid syntax for a where clause','ERROR','database');
			throw new Exception('Invalid syntax for a where clause',1017);
		}

		return $to_return;

	}

	private function add_on_clause($column_one, $operator, $column_two) {

		$valid_operators = Array('=','!=','>','>=','<','<=','<>');

		$on_pattern = "%s%s%s";

		if (!in_array($operator, $valid_operators)) {
			comodojo_debug('Invalid syntax for a on clause: wrong operator','ERROR','database');
			throw new Exception('Invalid syntax for a on clause',1021);
		}

		return sprintf($on_pattern,$column_one,$operator,$column_two);

	}

	/**
	 * Build a result set, converting objects into arrays 
	 */
	private function resource_to_array($data,$id=false,$affectedRows=false) {
			
		comodojo_debug('Building result set...','INFO','database');
		
		$_fetch = strtoupper($this->fetch);

		if (is_resource($data) AND @get_resource_type($data) == "mysql result") {
			switch ($_fetch) {
				case 'NUM': $fetch = MYSQL_NUM; break;
				case 'ASSOC': $fetch = MYSQL_ASSOC; break;
				default: $fetch = MYSQL_BOTH; break;
			}
			$i = 0;
			$myResult = array();
			$myResultLength = mysql_num_rows($data);
			while($i < $myResultLength) {
				$myResult[$i] = mysql_fetch_array($data, $fetch);
				$i++;
			}			
		}
		elseif (is_resource($data) AND @get_resource_type($data) == "pgsql result") {
			$i = 0;
			$myResult = array();
			$myResultLength = pg_num_rows($data);
			while($i < $myResultLength) {
				switch ($_fetch) {
					case 'NUM': 	$myResult[$i] = pg_fetch_array($data);	break;
					case 'ASSOC': 	$myResult[$i] = pg_fetch_assoc($data);	break;
					default: 		$myResult[$i] = pg_fetch_all($data);	break;
				}
				$i++;
			}			
		}
		elseif (is_resource($data) AND @get_resource_type($data) == "DB2 Statement") {
			$myResult = array();
			$myResultLength = db2_num_fields($data);
			switch ($_fetch) {
				case 'NUM': 	while ($row = db2_fetch_row($data)) array_push($myResult, $row);	break;
				case 'ASSOC': 	while ($row = db2_fetch_assoc($data)) array_push($myResult, $row);	break;
				default: 		while ($row = db2_fetch_both($data)) array_push($myResult, $row);	break;
			}
		}
		elseif (is_object($data) AND is_a($data, 'mysqli_result')) {
			switch ($_fetch) {
				case 'NUM': $fetch = MYSQLI_NUM; break;
				case 'ASSOC': $fetch = MYSQLI_ASSOC; break;
				default: $fetch = MYSQLI_BOTH; break;
			}
			$i = 0;
			$myResult = array();
			$myResultLength = $data->num_rows;
			while($i < $myResultLength) {
				$myResult[$i] = $data->fetch_array($fetch);
				$i++;
			}
			$data->free();
		}
		elseif (is_object($data)) {
			$myResult = array();
			foreach($data as $key=>$val) $myResult[$key] = $val;
			$myResultLength = sizeof($myResult);
		}
		else {
			$myResult = $data;
			$myResultLength = false;
		}

		foreach ($this->transform as $to) {
			if (isset($myResult[$to])) $myResult[$to] = json2array($myResult[$to]);
		}

		return Array(
			"result"		=>	$myResult,
			"resultLength"	=>	$myResultLength,
			"transactionId"	=>	$id,
			"affectedRows"	=>	$affectedRows
		);
		
	}
/********************* PRIVATE METHODS *******************/

}

class auto_database extends database {
	
	public function __construct($account, $keychain='SYSTEM', $userPass=null) {
		
		if (is_null($account)) {
			comodojo_debug('Missing keychain account name for auto_database','ERROR','database');
			throw new Exception('Missing keychain account name for auto_database',1009);
		}
				
		comodojo_load_resource('keychain');
		
		$k = new keychain();
		
		try {
			if ($keychain == 'SYSTEM') $db_pattern = $k->use_system_account($account);
			else $db_pattern = $k->get_account($account, $keychain, $userPass);
			parent::__construct($db_pattern['host'],$db_pattern['model'],$db_pattern['name'],$db_pattern['port'],$db_pattern['prefix'],$db_pattern['keyUser'],$db_pattern['keyPass']);
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
}

function loadHelper_database() { return false; }

?>