<?php

/**
 * commonFunctions.php
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

/**
 * Load comodojo global resources, located in comodojo/global folder
 * 
 * @param string $resource Name of resource to load
 */
function comodojo_load_resource($resource) {
	if (!function_exists("loadHelper_".$resource)) {
		try {
			require (defined('COMODOJO_SITE_PATH') ? COMODOJO_SITE_PATH : COMODOJO_BOOT_PATH) ."comodojo/global/".$resource.".php";
		}
		catch (Exception $e) {
			throw $e;
		}
	}
}

/**
 * Write log to file or standard php error log
 * 
 * WARNING: using a custom error log file is actually highly inefficient,
 * it open/close file at each call.
 *
 * This behaviour will be changed in future releases.
 */
function _write_log_string($log) {
	if (is_null(COMODOJO_GLOBAL_DEBUG_FILE)){
		error_log($log);
	}
	else {
		$file = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_TEMP_FOLDER.COMODOJO_GLOBAL_DEBUG_FILE;
		$file_handler = fopen($file, 'a');
		if (!$file_handler) return false;
		fwrite($file_handler, date(DATE_RFC822)." - ".$log."\n");
		fclose($file_handler);
	}
}

/**
 * Helper func to handle array log
 *
 */
function _comodojo_debug_helper($value, $margin='') {
	foreach ($value as $key => $value) {
		if (is_array($value)) {
			_write_log_string($margin.$key." = Array(");
			_comodojo_debug_helper($value, $margin+='   ');
			_write_log_string($margin.")");
		}
		else {
			_write_log_string($margin.$key." = ".$value.",");
		}
	}
}

/**
 * Debug something to error_log
 * 
 * @param	string|object|array|integer	$message	Debug message
 * @param	string						$type		The message type (INFO|WARNING|ERROR)
 * @param	string						$reference	The message reference (i.e. DATABASE, SSH, ...)
 */
function comodojo_debug($message,$type='ERROR',$reference="UNKNOWN") {
	if (COMODOJO_GLOBAL_DEBUG_ENABLED) {
		if ( strtoupper(COMODOJO_GLOBAL_DEBUG_LEVEL) == 'ERROR' AND strtoupper($type) != 'ERROR') return;
		elseif ( strtoupper(COMODOJO_GLOBAL_DEBUG_LEVEL) == 'WARNING' AND (strtoupper($type) != 'ERROR' OR strtoupper($type) != 'WARNING')) return;
		elseif (is_array($message)) {
			_write_log_string("(".$type.") ".$reference." | Array(");
			_comodojo_debug_helper($message);
			_write_log_string(")");
		}
		elseif (is_object($message)) {
			comodojo_debug($this->stdObj2array($message),$type,$reference);
		}
		elseif(is_scalar($message)) {
			_write_log_string("(".$type.") ".$reference." | ".$message);
		}
		else {
			_write_log_string("(DEBUG-ERROR): invalid value type for debug.");
		}
	}
}

/**
 * Transform an array into json string
 * 
 * @param	array		$array			The array to encode
 * @return	string/json					The encoded string
 */
function array2json($array) {
	
	if (!function_exists("json_encode")) {
		comodojo_load_resource('JSON');
		$json = new Services_JSON();
		$string = $json->encode($array);
	}
	else {
		$string = json_encode($array, JSON_NUMERIC_CHECK);	
	}
	return $string;
	
}

/**
 * Transform json string into array 
 * 
 * @param	string/json		$string			The string to decode
 * @param	bool			$rawConversion	If true, DO NOT attempt to convert stdObj to array, instead return raw JSON2PHP data; default: false
 * @return	array							The decoded array
 */
function json2array($string, $rawConversion = false) {
	
	if (!function_exists("json_decode")) {
		comodojo_load_resource('JSON');
		$json = new Services_JSON();
		$array = $json->decode($string);
	}
	else {
		$array = json_decode($string, JSON_NUMERIC_CHECK);
	}
	if ($rawConversion) return $array;
	else return stdObj2array($array);
	
}

/**
 * Transform stdObject string into array 
 * 
 * @param	string/stdObject		$string			The string to decode
 * @return	array									The decoded array
 */
function stdObj2array($stdObj) {
	
	if(is_object($stdObj) OR is_array($stdObj)) {
		$array = array();
		foreach($stdObj as $key=>$val){
				$array[$key] = stdObj2array($val);
		}
		return $array;
	}
	else {
		 return $stdObj;
	}

}

/**
 * Transform an array into xml string 
 * 
 * @param	array		$array		The array to encode
 * @return	string/xml				The encoded string
 */
function array2xml($array) {
	
	comodojo_load_resource('XML');
	$xmlEngine = new XML();
	$xmlEngine->sourceArray = $array;
	return $xmlEngine->encode();
	
}

/**
 * Transform XML string into an array 
 * 
 * @param	string/json		$dataString		The string to decode
 * @return	array							The decoded array
 */
function xml2array($dataString) {
	
	comodojo_load_resource('XML');
	$xmlEngine = new XML();
	$xmlEngine->sourceString = $dataString;
	return $xmlEngine->decode();
	
}

/**
 * Transform an array into YAML string 
 * 
 * @param	array		$array		The array to encode
 * @return	string/YAML				The encoded string
 */
function array2yaml($array) {
	
	comodojo_load_resource('spyc');
	return Spyc::YAMLDump($array);
	
}

/**
 * Transform YAML string into an array 
 * 
 * @param	string/json		$dataString		The string to decode
 * @return	array							The decoded array
 */
function yaml2array($dataString) {
	
	comodojo_load_resource('spyc');
	return Spyc::YAMLLoadString($dataString);
	
}

/**
 * Get serverside active locale 
 * 
 * @return	string	$locale	Serverside active locale
 */
function getLocale() {
	
	$langs = @$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$locale=strtok($langs,",");
	if(strlen($locale) < 1) {
		$locale = "en";
	}
	else {
		$locale = strtok($locale, "-");
	}
	return $locale;

}

/**
 * Get globally supported locales
 * 
 * @return	array	Supported locales
 */
function getSupportedLocales() {
	
	return explode(',',COMODOJO_SUPPORTED_LOCALES);

}

/**
 * Get server timezone offset 
 * 
 * @return	int	current server offset
 */
function getServerTimezoneOffset() {
	if(!is_string($serverTimezone = date_default_timezone_get())) {
		return 0;
	}
	$serverTimezone_dtz = new DateTimeZone($serverTimezone);
    $serverTimezone_dt = new DateTime("now", $serverTimezone_dtz);
    $offset = $serverTimezone_dtz->getOffset($serverTimezone_dt);
    return $offset/3600;
}

/**
 * Get site URL using external/internal reference 
 * 
 * @return	string	site url
 */
function getSiteUrl() {
	return filter_var(COMODOJO_SITE_EXTERNAL_URL, FILTER_VALIDATE_URL) === FALSE ? COMODOJO_SITE_URL : COMODOJO_SITE_EXTERNAL_URL;
}

/**
 * Transform hex data to binary data (reverse of bin2hex) 
 * 
 * @return	string
 */
if (!function_exists('hex2bin')) {
	function hex2bin($data) {
		$len = strlen($data);
		return pack("H" . $len, $data);
	}
}

/**
 * Generate random alphanumerical string
 * 
 * @param	int		$length	[optional] The random string length; default 128
 * @return	string	
 */
function random($length=128) {
	
	if ($length == 128) {
		$randNum = md5(uniqid(rand(), true), 0);
	}
	if ($length < 128) {
		$randNum = substr(md5(uniqid(rand(), true)), 0, $length);
	}
	else {
		$numString = (int)($length/128) + 1;
		$randNum = "";
		for ($i = 0; $i < $numString; $i++) {
			$randNum .=  md5(uniqid(rand(), true));
		}
		$randNum = substr($randNum, 0, $length);
	}
	return $randNum;
	
}

/**
 * Low level check of file exsistance
 * 
 * @param	string	$file	The file path+name
 * @return	bool			Check result
 */
function realFileExists($file){
	return (is_readable($file) /*&& is_writable($file)*/) ? true : false;
}

/**
 * Get a comodojo cookie 
 * 
 * @param	string	$cookieName	The cookie name
 * @return	string
 */
function getComodojoCookie($cookieName) {
	if (!isset($_COOKIE['comodojo_'.$cookieName])) {
		return -1;
	}
	else {
		return $_COOKIE['comodojo_'.$cookieName];
	}
}

//function setComodojoCookie() {
//	actually handled by javascript only
//}

/**
 * Transform xml string into html-formatted one for easier reading
 * 
 * @param	string	$xmlString	
 * @return	string			
 */
function xml2html($xmlString) {
	return htmlspecialchars(xml2txt($xmlString), ENT_QUOTES);
}

/**
 * Format xml string into txt string
 * 
 * @param	string	$xmlString	
 * @return	string			
 */
function xml2txt($xmlString) {
	$indent = '';
	$xmlString = str_replace("\n","",$xmlString);
	$xmlString = trim(preg_replace("/<\?[^>]+>/", "", $xmlString));
	$xmlString = preg_replace("/>([\s]+)<\//", "></", $xmlString);
	$xmlString = str_replace(">", ">\n", $xmlString);
	$xmlString = str_replace("<", "\n<", $xmlString);
	$xmlStringArray = explode("\n", $xmlString);
	$_xmlString = '';
	foreach($xmlStringArray as $k=>$tag){
		if ($tag == "") continue;
		if ($tag[0]=="<" AND $tag[1] != "/") {
			$_xmlString .= $indent.$tag."\n";
			$indent .= '  ';
		}
		elseif($tag[0]=="<" AND $tag[1] == "/") {
			$indent = substr($indent,0,strlen($indent)-2);
			$_xmlString .= (substr($_xmlString,strlen($_xmlString)-1)==">" || substr($_xmlString,strlen($_xmlString)-1)=="\n" ? $indent : '').$tag."\n";
		}
		else {
			$_xmlString = substr($_xmlString,0,strlen($_xmlString)-1);
			$_xmlString .= $tag;
		}
	}
	return $_xmlString;
}

/**
 * Check if $dateString represent a valid date
 * 
 * @param	string	$dateString	
 * @return	string			
 */
function is_date($dateString) {
	$dateArray = date_parse($dateString);
	return checkdate($dateArray["month"], $dateArray["day"], $dateArray["year"]);
}

/**
 * Check if $timeString represent a valid time (in the format THH:MM:SS)
 * 
 * @param	string	$timeString	
 * @return	string			
 */
function is_time($timeString) {
	return preg_match("/T(?:[01][0-9]|2[0-4]):[0-5][0-9]:[0-9][0-9]/", $timeString);
}

/**
 * Encodes unix timestamp format to iso8601 time format.
 * @access private
 * @param integer $time Takes unix timestamp as input
 * @return string Returns the datetime in iso8601 format. UTC
 */
function timestamp2iso8601time($timestamp) {
	return date("Ymd\TH:i:s", $timestamp);
}

/**
 * Decodes iso8601 time format to unix timestamp format
 * @access private
 * @param string $iso8601_Date Takes iso8601 string as input
 * @return integer Returns the datetime in unix timestamp format. UTC
 */
function iso8601time2timestamp($iso8601_Date) {
	return strtotime($iso8601_Date);
}

/**
 * Check if required $parameters are matched by $attributes.
 * 
 * This function check not onlu the presence of parameters but also the
 * type and value (a mix of logical and math operators plus simple regex
 * match via preg_match()).
 * 
 * $parameters array should look like this:
 * <code>
 * Array(
 * 
 *         //simple match, only check the presence
 *         "name",
 *         
 *         //combined presence and type
 *         Array("phone","IS","NUMERIC"),
 *         
 *         //combined presence and value
 *         Array("seats",">=",1),
 *         
 *         //combined presence, type and value
 *         Array("confirm","IS","STRING"),
 *         Array("confirm","==","YES")
 * 
 * );
 * </code>
 * 
 * @param    array    $attributes        The attributes to match, in array format (see above)
 * @param    array    $parameters        The parameters to check, array-enclosed
 * 
 * @return    bool    true in case of match, false otherwise.
 * 
 */
function attributes_to_parameters_match($attributes, $parameters) {
    foreach ($parameters as $parameter) {
        if (is_array($parameter) AND @count($parameter) == 3) {
            if (value_coherence_check($parameter[0],$parameter[1],$parameter[2])) continue;
            else return false;
        }
        else {
            if (isset($attributes[$parameter])) continue;
            else return false;
        }
    }
    return true;
}

/**
 * check if $value is coherent with declared rules
 * 
 * @param    string    $value        The value that should match condition
 * @param    string    $condition    The condition to check
 * @param    string    $check        The value for the condition
 * 
 * @return    bool                True in case of condition match, false otherwise 
 */
function value_coherence_check($value, $condition, $check) {
    $to_return = true;
    switch (strtoupper($condition)) {
        case '=':
        case '!=':
            $to_return = $value == $check ? true : false;
        break;
        case '<=':
            $to_return = $value <= $check ? true : false;
        break;
        case '<':
            $to_return = $value <  $check ? true : false;
        break;
        case '>=':
            $to_return = $value >= $check ? true : false;
        break;
        case '>':
            $to_return = $value >  $check ? true : false;
        break;
        case 'IS':
        case '!IS':
            switch (strtoupper($check)) {
                case 'SCALAR':
                    $to_return = is_scalar($value);
                break;
                case 'STRING':
                    $to_return = is_string($value);
                break;
                case 'NUMERIC':
                    $to_return = is_numeric($value);
                break;
                case 'INTEGER':
                case 'INT':
                    if (is_numeric($value)) {
                        if(intval($value) === $value) {
                            $to_return = true;
                        }
                    }
                    else $to_return = false;
                break;
                case 'DOUBLE':
                case 'FLOAT':    
                    $to_return = is_double($value);
                break;
                default: return false; break;
            }
        break;
        case 'CONTAINS':
        case '!CONTAINS':
            $to_return = strstr($value, $check) === false ? false : true; 
        break;
        case 'STARTS':
        case '!STARTS':
            $to_return = substr($value, 0, sizeof($check)) == $check ? true : false;
        break;
        case 'ENDS':
        case '!ENDS':
            $to_return = substr($value, sizeof($value), -sizeof($check)) == $check ? true : false;
        break;
        case 'REGEXP':
        case '!REGEXP':
            $to_return = preg_match($check, $value);
        break;
        default: return false; break;
    }
    if (substr($condition,0,1) == '!') return !$to_return;
    else return $to_return;
}

/**
 * Returns the current comdojo version, optionally fields-splitted
 * 
 * $fields parameter set the field that shoud be returned; supported
 * parameters are:
 * 
 * "PRODUCT"	=>	Product (i.e. "Comodojo core")
 * "VERSION"	=>	Product version (i.e. "1.0")
 * "BUILD"		=>	Version build (i.e. "312")
 * "ALL"		=>	[default] Returns an array of PRODUCT,VERSION,BUILD
 * 
 * @param	string	$field	[optional]	The field to return (see above)
 * 
 * @return	string|array				The current comodojo version 
 */
function comodojo_version($fields='ALL') {
		
	if (@defined('COMODOJO_SITE_PATH')) {
		$comodojo_version = file_get_contents(COMODOJO_SITE_PATH.'comodojo/others/version');
	}
	elseif (@defined('COMODOJO_BOOT_PATH')) {
		$comodojo_version = file_get_contents(COMODOJO_BOOT_PATH.'comodojo/others/version');
	}
	else {
		return false;
	}
	
	$comodojo_version_array = explode("\n",$comodojo_version);
	
	switch (strtoupper($fields)) {
		case 'PRODUCT': $toReturn = $comodojo_version_array[0]; break;
		case 'VERSION': $toReturn = $comodojo_version_array[1]; break;
		case 'BUILD': $toReturn = $comodojo_version_array[2]; break;
		case 'ALL': $toReturn = $comodojo_version; break;
		case 'ARRAY': $toReturn = $comodojo_version_array; break;
		default: $toReturn = false; break;
	}
	
	return $toReturn;
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_commonFunctions
 */
function loadHelper_common_functions() { return false; }

?>