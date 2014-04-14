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

	public function encode_response($data) {

		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="'.$this->encoding.'"?><methodResponse />');

		$params = $xml->addChild("params");
		$param = $params->addChild("param");
		$value = $param->addChild("value");

		$this->encode_value($value, $data);

		return $xml->asXML();

	}

	public function encode_call($method, $data) {

		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="'.$this->encoding.'"?><methodCall />');

		$xml->addChild("methodName",trim($method));

		$params = $xml->addChild("params");
		
		foreach ($data as $d) {
			$param = $params->addChild("param");
			$value = $param->addChild("value");
			$this->encode_value($value, $d);
		}

		return $xml->asXML();

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

	private function encode_value($xml, $value) {

		if ($value === NULL) {
			$xml->addChild("nil");
		}
		else if (is_array($value)) {
			//if ( array_keys($value) == range(0, count($value) - 1) ) $this->encode_array($xml, $value);
			if ( !$this->catch_struct($value) ) $this->encode_array($xml, $value);
			else $this->encode_struct($xml, $value);
		}
		else if (is_bool($value)) {
			$xml->addChild("boolean", $value ? 1 : 0);
		}
		else if (is_double($value)) {
			$xml->addChild("double", $value);
		}
		else if (is_integer($value)) {
			$xml->addChild("int", $value);
		}
		else if (is_object($value)) {
			$this->encode_object($xml, $value);
		}
		else if (is_string($value)) {
			$xml->addChild("string", htmlspecialchars($value, ENT_XML1, $this->encoding));
		}
		else {
			comodojo_debug("Unknown type for encoding: " . gettype($value),"ERROR","xmlRpcEncoder");
			//should I throw an exception here?
		}
	}

	private function encode_array($xml, $value) {
		
		$array = $xml->addChild("array");
		
		$data = $array->addChild("data");
		
		foreach ($value as $entry) {
		
			$val = $data->addChild("value");

			$this->encode_value($val, $entry);

		}

	}

	private function encode_object($xml, $value) {

		if ($value instanceof DataObject) {

			$this->encode_value($xml, $value->export());

		}
		else if ($value instanceof DateTime) {

			//$xml->addChild("dateTime.iso8601", gmstrftime("%Y%m%dT%T", $value->format('U')));
			$xml->addChild("dateTime.iso8601", timestamp2iso8601time($value->format('U')));

		} else {

			comodojo_debug("Cannot encode object of type: " . get_class($value),"ERROR","xmlRpcEncoder");
			//should I throw an exception here?

		}
		
	}

	private function encode_struct($xml, $value) {

		$struct = $xml->addChild("struct");

		foreach ($value as $k => $v) {

			$member = $struct->addChild("member");

			$member->addChild("name", $k);

			$val = $member->addChild("value");

			$this->encode_value($val, $v);

		}

	}

	private function catch_struct($value) {

		for ($i = 0; $i < count($value); $i++) {
			if (!array_key_exists($i, $value)) {
				return true;
			}
		}
		return false;

	} 

}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_xmlRpcEncoder
 */
function loadHelper_xmlRpcEncoder() { return false; }

?>
