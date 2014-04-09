<?php

/**
 * xmlRpcDecoder.php
 * 
 * XML-RPC transformation class;
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
class xmlRpcDecoder  {

	private $fault = false;
	
	private function & serialize(&$current_node){
		if(is_array($current_node)){
			if(isset($current_node['array'])){
				if(!@is_array($current_node['array']['data'])){
					$tr = array();
					$temp = &$tr;
				}else{
					$temp = &$current_node['array']['data']['value'];
					if(is_array($temp) and array_key_exists(0, $temp)){
						$count = count($temp);
						for($n=0;$n<$count;$n++){
							$temp2[$n] = &$this->serialize($temp[$n]);
						}
						$temp = &$temp2;
					}else{
						$temp2 = &$this->serialize($temp);
						$temp = array(&$temp2);
					}
				}
			}elseif(isset($current_node['struct'])){
				if(!is_array($current_node['struct'])){
					$tr = array();
					$temp = &$tr;
				}else{
					$temp = &$current_node['struct']['member'];
					if(is_array($temp) and array_key_exists(0, $temp)){
						$count = count($temp);
						for($n=0;$n<$count;$n++){
							$temp2[$temp[$n]['name']] = &$this->serialize($temp[$n]['value']);
						}
					}
					else{
						$temp2[$temp['name']] = &$this->serialize($temp['value']);
						$temp = &$temp2;
					}
				}
			}
			elseif (sizeof($current_node) != 0 ) {
				$types = array('string', 'int', 'i4', 'double', 'dateTime.iso8601', 'base64', 'boolean');
				foreach($types as $type){
					if(array_key_exists($type, $current_node)){
						$temp = &$current_node[$type];
						break;
					}
				}
				switch ($type){
					case 'int':
					case 'i4':
						$temp = (int)$temp;
					break;
					case 'string':
						$temp = (string)$temp;
					break;
					case 'double':
						$temp = (double)$temp;
					break;
					case 'boolean':
						$temp = (bool)$temp;
					break;
					case 'dateTime.iso8601':
						$temp = (object) Array(
							"scalar"		=>	$temp,
                    		"xmlrpc_type" 	=>	"datetime",
                    		"timestamp"		=>	iso8601time2timestamp($temp)
						);
					break;
					case 'base64':
						$temp = base64_decode($temp);
					break;
					default:
						$temp = (string)$temp;
					break;
				}
			}
			else {
				
			}
		}else{
			$temp = (string)$current_node;
		}
		return $temp;
	}

	public function decode($request){
		
		$_request = xml2Array($request);

		if (!isset($_request['methodResponse'])) {
			$this->fault = "Uncomprensible response";
			return -1;
		}
		elseif (isset($_request['methodResponse']['fault'])) {
			$this->fault = $_request['methodResponse']['fault']['value']['struct']['member'][1]['value']['string'];
			return -1;
		}
		else if(!is_array($_request['methodResponse']['params'])) return array();
		else{
			$temp = &$_request['methodResponse']['params']['param'];
			if(is_array($temp) and array_key_exists(0, $temp)){
				$count = count($temp);
				for($n = 0; $n < $count; $n++){
					$temp2[$n] = &$this->serialize($temp[$n]['value']);
				}
			}else{
				$temp2[0] = &$this->serialize($temp['value']);
			}
			$temp = &$temp2;
			return $temp[0];
			//foreach ($_request['methodResponse']['params'] as $key => $value) {
				
			//}
		}
	}
	
	public function decode_call($request){

		$_request = xml2Array($request);
		
		if (!isset($_request['methodCall']) OR !isset($_request['methodCall']['methodName'])) {
			$this->fault = "Uncomprensible request";
			return Array(null,"Uncomprensible request");
		}
		else{
			$method_name = $_request['methodCall']['methodName'];
			$temp = &$_request['methodCall']['params']['param'];
			if(is_array($temp) and array_key_exists(0, $temp)){
				$count = count($temp);
				for($n = 0; $n < $count; $n++){
					$temp2[$n] = &$this->serialize($temp[$n]['value']);
				}
			}else{
				$temp2[0] = &$this->serialize($temp['value']);
			}
			$temp = &$temp2;
			return Array($method_name, $temp[0]);
		}
	}
	
	public function getFault() {
		return $this->fault;
	}
 

}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_xmlRpcDecoder
 */
function loadHelper_xmlRpcDecoder() { return false; }

?>