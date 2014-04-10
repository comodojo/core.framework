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

	private $last_fault = null;

	public function decode_response($response) {

		$xml_data = simplexml_load_string($response);

		if ( !isset($xml_data->params) ) {
			$this->last_fault = "Uncomprensible response";
			return false;
		}

		$data = array();
		try {
			foreach ($xml_data->params->param as $param) {
				//$data[] = $this->decode_value($param->value);
				array_push($data,$this->decode_value($param->value));
			}
		} catch (Exception $e) {
			$this->last_fault = $e->getMessage();
			return false;
		}

		return $data;

	}

	public function decode_call($request) {

		$xml_data = simplexml_load_string($request);

		if ( !isset($xml_data->methodName) ) {
			$this->last_fault = "Uncomprensible request";
			return array(null,$this->last_fault);
		}

		$method_name = $this->decode_string($xml_data->methodName[0]);

		$data = array();
		try {
			foreach ($xml_data->params->param as $param) {
				$data[] = $this->decode_value($param->value);
			}
		} catch (Exception $e) {
			$this->last_fault = $e->getMessage();
			return array(null,$this->last_fault);
		}

		return array($method_name, $data);

	}

	public function decode_multicall($request) {

		$xml_data = simplexml_load_string($request);

		if ( !isset($xml_data->methodName) ) {
			$this->last_fault = "Uncomprensible multicall request";
			return false;
		}

		if ( $this->decode_string($xml_data->methodName[0]) != "system.multicall" ) {
			$this->last_fault = "Invalid multicall request";
			return false;
		}

		$data = array();
		try {
			foreach ($xml_data->params->param as $param) {
				$children = $param->value->children();
				$child = $children[0];
				$call = $this->decode_array($child);
				$data[] = array($call['methodName'], $call['params']);
			}
		} catch (Exception $e) {
			$this->last_fault = $e->getMessage();
			$data[] = array(null, $this->last_fault);
		}
		/*
		$found = preg_match_all('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)</'.$element_name.'>#s', $xml, $matches, PREG_PATTERN_ORDER);
		if ($found != false) {
			if ($content_only) {
				return $matches[1];  //ignore the enlosing tags
			} else {
			return $matches[0];  //return the full pattern match
		}
		*/

		return $data;

	}

	public function getFault() {

		return $this->last_fault;

	}

	private function decode_value($value) {

		$children = $value->children();

		if (count($children) != 1) {
			throw new Exception("Invalid value element");
		}

		$child = $children[0];

		$child_type = $child->getName();

		switch ($child_type) {

			case "i4":
			case "int":
				$return_value = $this->decode_int($child);
			break;

			case "double":
				$return_value = $this->decode_double($child);
			break;

			case "boolean":
				$return_value = $this->decode_bool($child);
			break;

			case "base64":
				$return_value = $this->decode_base($child);
			break;
			
			case "dateTime.iso8601":
				$return_value = $this->decode_iso_8601_datetime($child);
			break;

			case "string":
				$return_value = $this->decode_string($child);
			break;

			case "array":
				$return_value = $this->decode_array($child);
			break;
			
			case "struct":
				$return_value = $this->decode_struct($child);
			break;
			
			case "nil":
			case "ex:nil":
				$return_value = $this->decode_nil();
			break;
			
			default:
				throw new Exception("Invalid value type");
			break;

		}

		return $return_value;

	}

	/**
	 * Decode an XML-RPC <base64> element
	 */
	private function decode_base($base64) {
		return base64_decode($this->decode_string($base64));
	}

	/**
	 * Decode an XML-RPC <boolean> element
	 */
	private function decode_bool($boolean) {
		return filter_var($boolean, FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Decode an XML-RPC <dateTime.iso8601> element
	 */
	private function decode_iso_8601_datetime($date_time) {
		return iso8601time2timestamp($date_time);
	}

	/**
	 * Decode an XML-RPC <double> element
	 */
	private function decode_double($double) {
		return (double)($this->decode_string($double));
	}

	/**
	 * Decode an XML-RPC <int> or <i4> element
	 */
	private function decode_int($int) {
		return filter_var($int, FILTER_VALIDATE_INT);
	}

	/**
	 * Decode an XML-RPC <string>
	 */
	private function decode_string($string) {
		return (string)$string;
	}

	/**
	 * Decode an XML-RPC <nil/>
	 */
	private function decode_nil() {
		return null;
	}

	/**
	 * Decode an XML-RPC <struct>
	 */
	private function decode_struct($struct) {
		$return_value = array();
		foreach ($struct->member as $member) {
			$name = $member->name . "";
			$value = $this->decode_value($member->value);
			$return_value[$name] = $value;
		}
		return $return_value;
	}

	/** 
	 * Decode an XML-RPC <array> element
	 */
	private function decode_array($array) {
		$return_value = array();
		foreach ($array->data->value as $value) {
			$return_value[] = $this->decode_value($value);
		}
		return $return_value;
	}

}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_xmlRpcDecoder
 */
function loadHelper_xmlRpcDecoder() { return false; }

?>