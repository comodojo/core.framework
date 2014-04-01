<?php

/** 
 * xmlRpcEncoder.php
 * 
 * XML-RPC transformation class;
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 * @info		Based on the work of Half-Dead
 */
class xmlRpcEncoder {

	/**
	 * Encoding of request
	 * PLEASE NOTE: this should be the same used in xmlRpcSender!
	 */
	public $encoding = COMODOJO_DEFAULT_ENCODING;

	/**
	 * Array of parameters
	 * @var	array
	 */
	private $params;
	
	/**
	 * Pointer to the current data type
	 * @var string
	 */
	private $type;

	/**
	 * <methodName>, if any
	 */
	private $methodName = 'null';
	
	/**
	 * <methodName>, if any
	 */
	private $currentIndent = '    ';

	/**
	 * Adds a <params> tags
	 */
	private function start_params() {
		$this->params[] = $this->currentIndent."<params>\n";
		$this->currentIndent .= '  ';
	}

	/**
	 * Adds a </params> tags
	 */
	private function end_params() {
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</params>\n";
	}

	/**
	 * Adds a <param> tag
	 */
	private function start_param() {
		$this->params[] = $this->currentIndent."<param>\n";
		$this->currentIndent .= '  ';
	}

	/**
	 * Adds a </param> tag
	 */
	private function end_param() {
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</param>\n";
	}

	/**
	 * Adds a <struct> tag
	 */
	private function start_struct() {
		$this->params[] = $this->currentIndent."<value>\n";
		$this->currentIndent .= '  ';
		$this->params[] = $this->currentIndent."<struct>\n";
		$this->currentIndent .= '  ';
	}

	/**
	 * Adds a </struct> tag
	 */
	private function end_struct() {
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</struct>\n";
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</value>\n";
	}

	/**
	 * Adds a <array> tag
	 */
	private function start_array() {
		$this->params[] = $this->currentIndent."<value>\n";
		$this->currentIndent .= '  ';
		$this->params[] = $this->currentIndent."<array>\n";
		$this->currentIndent .= '  ';
		$this->params[] = $this->currentIndent."<data>\n";
		$this->currentIndent .= '  ';
	}

	/**
	 * Adds a </array> tag
	 */
	private function end_array() {
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</data>\n";
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</array>\n";
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</value>\n";
	}

	/**
	 * Adds a <member><name> tag
	 * @param string $name Name of the member object
	 */
	private function start_member($name) {
		$this->params[] = $this->currentIndent."<member>\n";
		$this->currentIndent .= '  ';
		$this->params[] = $this->currentIndent."<name>" . trim($name) . "</name>\n";
	}

	/**
	 * Adds a </member> tag
	 */
	private function end_member() {
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</member>\n";
	}
	
	/**
	 * Adds a <value><[type]> tag
	 * @param	string	$type	Type of the value to add
	 * @param	string	$value	Value to add
	 */
	private function start_value($type, $value) {
		$this->params[] = $this->currentIndent."<value>\n";
		$this->currentIndent .= '  ';
		$this->params[] = $this->currentIndent."<".$type.">".trim($value)."</".$type.">\n";
	}

	/**
	 * Adds a <\value> tag
	 */
	private function end_value() {
		$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
		$this->params[] = $this->currentIndent."</value>\n";
	}

	/**
	 * Initializes the encoder.
	 * @usage:	$encoder = new xmlRpcEncoder([methodName]);
	 * @param	string	$methodName	The <methodName> of the outgoing call.
	 */
	public function xmlRpcEncoder($methodName = 'null') {
		$this->type   = 'none';
		$this->methodName = $methodName;
	}

	/**
	 * Add param to struct.
	 * @usage:	$encoder->add_param($type, $value, $name);
	 * @param	string	$type		Type of value to add
	 * @param	mixed	$value		The data to add
	 * @param	string	$name		Optional struct member name: results in a member/name pair
	 */
	public function add_param($type, $value='', $name='') {
		$this->start_param();
		$this->add_value($type, $value, $name);
		$this->end_param();
	}

	/**
	 * Add value to struct
	 *
	 * @usage:	$encoder->add_value($type, $value, $name);
	 *
	 * $type can of any of the following:
	 * i4, int, long, integer
	 * real, float, double
	 * bool, boolean
	 * b64, base64
	 * time, date, datetime, dateTime.iso8601 ($value is unix timestamp)
	 * file ($value is path to a file to include)
	 * array, struct, object
	 * string
	 *
	 * The above non standard types are for convienince,
	 * and are automaticly mapped to their xmlrpc standard counterparts.
	 *
	 * $type can also be non standard and used for custom needs.
	 *
	 * @param	string	$type		Type of value to add
	 * @param	mixed	$value		The data to add
	 * @param	string	$name	Optional struct member name: results in a member/name pair
	 * @see add_param()
	*/
	private function add_value($type, $value = false, $name = '') {
		$type = strtolower($type);
		switch($type) {
			case "i4" :
			case "int" :
			case "long" :
			case "integer" :
				$this->type = 'int';
			break;
	
			case "real" :
			case "float" :
			case "double" :
				$this->type = 'double';
				break;
	
			case "bool" :
			case "boolean" :
				($value == "1" || $value == "true") ? $value = 1 : $value = 0;
				$this -> type = 'boolean';
			break;
	
			case "b64" :
			case "base64" :
				$value = base64_encode($value);
				$this->type = 'base64';
			break;
	
			case "time" :
			case "date" :
			case "datetime" :
			case "dateTime.iso8601" :
				$value = timestamp2iso8601time(strtotime($value));
				$this->type = 'dateTime.iso8601';
			break;
	
			case "file" :
				$fp = fopen($value, "rb");
				$ffile = fread($fp, filesize($value));
				fclose($fp);
				$value = base64_encode($ffile);
				$this->type = 'base64';
			break;
	
			case "array" :
			case "object" :
				$this->add_array($value, $name);
				$this->type = 'none';
			break;
	
			case "struct" :
				$this->add_struct($value, $name);
				$this->type = 'none';
			break;
	
			case "string" :
				$this->type = 'string';
				$value = trim(htmlentities($value,ENT_NOQUOTES,$this->encoding));
			break;
	
			default :
				$this->type = preg_replace("/[^a-z0-9]/i", "", $type);
				$value = trim(htmlentities($value,ENT_NOQUOTES,$this->encoding));
			break;
		}
	
		if ($this->type != 'none') {
			if ($name) $this->start_member($name);
			//$this->type = strtolower($this->type);
			$this->start_value($this->type, $value);
			$this->end_value();
			if ($name) $this->end_member();
		}
	}

	/**
	 * Auto add array of values to request
	 *
	 * @usage:	$encoder->auto_add_values($values);
	 *
	 * It will automatically match the datatype and (hopefully) map it to xmlrpc standard counterpart.
	 *
	 * @param	array	$values		Values to be parsed and added
	 */
	public function auto_add_values($values) {
		
		foreach ($values as $key => $value) {
			
			$this->start_param();
			
			switch(TRUE) {
					
					case is_int($value) :
						$this->type = 'int';
					break;
		
					case is_bool($value) :
						$this->type = 'boolean';
					break;
		
					case is_float($value) :
						$this->type = 'double';
					break;
					
					case (!base64_decode($value, true) ? false : true):
						$this->type = 'base64';
					break;
					
					case (!strtotime($value) ? false : true):
						$value = timestamp2iso8601time(strtotime($value));
						$this->type = 'dateTime.iso8601';
					break;
					
					case is_array($value) :
					case is_object($value) :
						if (!array_keys($value) !== range(0, count($value) - 1)) $this->add_array($value, is_string($key) ? $key : false); 
						else $this->add_struct($value, is_string($key) ? $key : false);
						$this->type = 'none';
					break;
		
					default :
						$this->type = 'string';
						$value = trim(htmlentities($value,ENT_NOQUOTES,$this->encoding));
					break;
				}
			
			if ($this->type != 'none') {
				if (is_string($key)) $this -> start_member($key);
				$this->start_value($this->type, $value);
				$this->end_value();
				if (is_string($key)) $this -> end_member();
			}
			
			$this->end_param();
			
		}

	}

	/**
	 * Add array to struct
	 * Values' type are autodetected
	 * @param	array	$array	The name of the array
	 * @param	string	$name	Optional member name: results in a member/name pair
	 */
	private function add_array($array, $name = false) {
		if (!is_array($array)) return;
		if ($name != false) { $this -> start_member($name);}
		$this -> start_array();
		foreach ($array as $key => $value) {
			switch(TRUE) {
				case is_int($value) :
					$this->type = 'int';
				break;
	
				case is_bool($value) :
					$this->type = 'boolean';
				break;
	
				case is_float($value) :
					$this->type = 'double';
				break;
				
				case (!base64_decode($value, true) ? false : true):
						$this->type = 'base64';
				break;
				
				case (!strtotime($value) ? false : true):
					$value = timestamp2iso8601time(strtotime($value));
					$this->type = 'dateTime.iso8601';
				break;
				
				case is_array($value) :
				case is_object($value) :
					if (!array_keys($value) !== range(0, count($value) - 1)) $this->add_array($value, is_string($key) ? $key : false); 
					else $this->add_struct($value, is_string($key) ? $key : false);
					$this->type = 'none';
				break;
	
				default :
					$this->type = 'string';
					$value = trim(htmlentities($value,ENT_NOQUOTES,$this->encoding));
				break;
			}
			if ($this->type != 'none') {
				if (is_string($key)) $this -> start_member($key);
				$this->start_value($this->type, $value);
				$this->end_value();
				if (is_string($key)) $this -> end_member();
			}
		}
		$this -> end_array();
		if ($name != false) $this -> end_member();
	}
	
	/**
	 * Add struct to struct :)
	 * Values' type are autodetected
	 * @param	struct	$struct	The name of the struct
	 * @param	string	$name	Optional member name: results in a member/name pair
	 */
	private function add_struct($array, $name = false) {
		if (!is_array($array)) return;
		if ($name != false) { $this -> start_member($name);}
		$this -> start_struct();
		foreach ($array as $key => $value) {
			switch(TRUE) {
				case is_int($value) :
					$this->type = 'int';
				break;
	
				case is_bool($value) :
					$this->type = 'boolean';
				break;
	
				case is_float($value) :
					$this->type = 'double';
				break;
				
				case (!base64_decode($value, true) ? false : true):
						$this->type = 'base64';
				break;
				
				case (!strtotime($value) ? false : true):
					$value = timestamp2iso8601time(strtotime($value));
					$this->type = 'dateTime.iso8601';
				break;
				
				case is_array($value) :
				case is_object($value) :
					if (!array_keys($value) !== range(0, count($value) - 1)) $this->add_array($value, is_string($key) ? $key : false); 
					else $this->add_struct($value, is_string($key) ? $key : false);
					$this->type = 'none';
				break;
	
				default :
					$this->type = 'string';
					$value = trim(htmlentities($value,ENT_NOQUOTES,$this->encoding));
				break;
			}
			if ($this->type != 'none') {
				if (is_string($key)) $this -> start_member($key);
				$this->start_value($this->type, $value);
				$this->end_value();
				if (is_string($key)) $this -> end_member();
			}
		}
		$this -> end_struct();
		if ($name != false) $this -> end_member();
	}

	/** target="_blank"
	 * Get xml-encoded data.
	 * @usage:	$message = $encoder->getData();
	 * @return	string	Returns encoded values
	 */
	public function getData() {
		$this->currentIndent = '';
		if ($this->methodName == 'null') {
			$payload  = '<?xml version="1.0" encoding="'.$this->encoding.'"?>' . "\n";
			$payload .= "<methodResponse>\n";
			$payload .= "  <params>\n";
			if ($this->params != '') {
				$payload .= $this->currentIndent."<param>\n";
					$this->currentIndent .= '  ';
				$payload .=	implode('', $this->params);
					$this->currentIndent = substr($this->currentIndent,0,strlen($this->currentIndent)-2);
				$payload .= $this->currentIndent."</param>\n";
			}
			$payload .= $this->currentIndent."  </params>\n";
			$payload .= $this->currentIndent."</methodResponse>";
		} else {
			$payload  = '<?xml version="1.0" encoding="'.$this->encoding.'"?>' . "\n";
			$payload .= "<methodCall>\n";
			$payload .= "  <methodName>".trim($this->methodName)."</methodName>\n";
			$payload .= "  <params>\n";
			if ($this->params != '') $payload .= implode('', $this->params);
			$payload .= "  </params>\n";
			$payload .= "</methodCall>";
		}
		//if (GLOBAL_DEBUG_ENABLED) error_log("(DEBUG) - Request payload: ".$payload);
		return $payload;
	}

	public function getError($error_code, $error_name) {
		$payload  = '<?xml version="1.0" encoding="'.$this->encoding.'"?>' . "\n";
		$payload .= "<methodResponse>\n";
		$payload .= "  <fault>\n";
		$payload .= "    <value>\n";
		$payload .= "      <struct>\n";
		$payload .= "        <member>\n";
		$payload .= "          <name>faultCode</name>\n";
		$payload .= "          <value><int>".$error_code."</int></value>\n";
		$payload .= "        </member>\n";
		$payload .= "        <member>\n";
		$payload .= "          <name>faultString</name>\n";
		$payload .= "          <value><string>".$error_name."</string></value>\n";
		$payload .= "        </member>\n";
		$payload .= "      </struct>\n";
		$payload .= "    </value>\n";
		$payload .= "  </fault>\n";
		$payload .= "</methodResponse>";
		return $payload;
	}

}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_xmlRpcEncoder
 */
function loadHelper_xmlRpcEncoder() { return false; }

?>
