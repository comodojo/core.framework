<?php

/**
 * upload.php
 * 
 * Send files to comodojo users' home directory
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 
require 'comodojo/global/comodojo_basic.php';

class upload extends comodojo_basic {
	
	public $script_name = 'upload.php';
	
	public $use_session_transport = true;
	
	public $require_valid_session = false;
	
	public $do_authentication = true;
	
	private $upload_method = false;
	
	public function logic($attributes) {
		
		comodojo_load_resource('events');
		comodojo_load_resource('filesystem');
		
		if (!isset($attributes['destination'])) {
			comodojo_debug("No destination path specified",'ERROR','upload');
			throw new Exception("No destination path specified", 3001);
		}
		
		if (isset($attributes['overwrite'])) {
			$overwrite = !$attributes['overwrite'] ? false : true;
		}
		else {
			$overwrite = false;
		}
		
		if(isset($_FILES['flashUploadFiles']) || isset($_FILES['uploadedfileFlash'])) { $this->upload_method = 'FLASH'; }
		elseif(isset($_FILES['uploadedfiles'])) { $this->upload_method = 'HTML5'; }
		elseif (isset($_FILES['uploadedfile'])) { $this->upload_method = 'HTML'; }
		elseif (isset($_FILES['uploadedfile0'])) { $this->upload_method = 'MULTIHTML'; }
		else {
			comodojo_debug("No file received",'ERROR','upload');
			throw new Exception("No file received", 3003);
		}
		
		$this->fs = new filesystem();
		$this->event = new events();
		
		if (!$this->fs->checkPermissions(COMODOJO_USER_NAME,'writer',$attributes['destination'])) {
			comodojo_debug("Invalid destination path",'ERROR','upload');
			throw new Exception("Invalid destination path", 3002);
		}
		
		try {
			switch ($this->upload_method) {
				case 'FLASH':
					$to_return = $this->upload_flash($attributes['destination'],$overwrite);
					$contentType = 'text/plain';
				break;
				case 'HTML5':
					$to_return = array2json($this->upload_html5($attributes['destination'],$overwrite));
					$contentType = 'application/json';
				break;
				case 'HTML':
					$to_return = /*'<textarea>'.*/array2json($this->upload_html($attributes['destination'],$overwrite))/*.'</textarea>'*/;
					//$contentType = 'text/html';
					$contentType = 'application/json';
				break;
				case 'MULTIHTML':
					$to_return = /*'<textarea>'.*/array2json($this->upload_multihtml($attributes['destination'],$overwrite))/*.'</textarea>'*/;
					//$contentType = 'text/html';
					$contentType = 'application/json';
				break;
			}
		}
		catch (Exception $e) {
			throw $e;
		}
		
		set_header(Array(
			'statusCode'	=>	200,
			'ttl'			=> 	0,
			'contentType'	=>	$contentType,
			'charset'		=>	COMODOJO_DEFAULT_ENCODING
		), strlen($to_return));
		
		return $to_return;
		
	}
	
	public function error($error_code, $error_name) {
		
		switch ($this->upload_method) {
			case 'FLASH':
				$to_return = 'success=false,error='.$error_name.',errorcode='.$error_code;
				$contentType = 'text/plain';
			break;
			//case 'HTML5':
			//break;
			//case 'HTML':
			//break;
			//case 'MULTIHTML':
			//break;
			default:
				$to_return = array2json(Array('success'=>false,'error'=>$error_name,'errorcode'=>$error_code));
				$contentType = 'application/json';
			break;
		}
		
		set_header(Array(
			'statusCode'	=>	200,
			'ttl'			=> 	0,
			'contentType'	=> $contentType,
			'charset'		=>	COMODOJO_DEFAULT_ENCODING
		), strlen($to_return));
		
		return $to_return;
		
	}
	
	/**
	* Manage file uploaded via html
	*/
	private function upload_html($destination,$overwrite) {
		
		$temp_file_name = stripslashes($_FILES['uploadedfile']["name"]);
		$temp_file_sffx = random(10);
		$temp_file = $temp_file_name . "." . $temp_file_sffx;
		$destination_file = $destination.$temp_file_name;
		
		if (!move_uploaded_file($_FILES['uploadedfile']['tmp_name'],  COMODOJO_SITE_PATH . COMODOJO_TEMP_FOLDER . $temp_file)) {
			comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
			$this->event->record('upload_file', $temp_file_name, false);
			throw new Exception("Uploaded file cannot be moved", 3004);
		}
		
		try {
			$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
		}
		catch (Exception $e) {
			comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
			$this->event->record('upload_file', $temp_file_name, false);		
			throw $e;
		}
		
		$this->event->record('upload_file', $temp_file_name, true);
		return array(
			'success'	=>	true,
			'name'		=>	$temp_file_name,//$_FILES['uploadedfile']['name'],
			'file'		=>	$destination_file,
			'size'		=>	filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file)
	 	);
	
	}
	/**
	* Manage files uploaded via html (multi)
	*/
	private function upload_multihtml($destination,$overwrite) {

		$cnt = 0;
		$postdata = array();
		
		while(isset($_FILES['uploadedfile'.$cnt])){
		
			$temp_file_name = stripslashes($_FILES['uploadedfile'.$cnt]["name"]);
			$temp_file_sffx = random(10);
			$temp_file = $temp_file_name . "." . $temp_file_sffx;
			$destination_file = $destination.$temp_file_name;

			if (!move_uploaded_file($_FILES['uploadedfile'.$cnt]['tmp_name'],  COMODOJO_SITE_PATH . COMODOJO_TEMP_FOLDER . $temp_file)) {
				comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
				$postdata[$cnt] = array( 'success' => false, 'error' => 'Uploaded file cannot be moved' );
				$this->event->record('upload_file', $temp_file_name, false);
				$cnt++;
				continue;
			}

			try {
				$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
			}
			catch (Exception $e) {
				comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
				$postdata[$cnt] = array( 'success' => false, 'error' => 'Uploaded file cannot be moved' );
				$this->event->record('upload_file', $temp_file_name, false);
				$cnt++;
				continue;
			}
			
			$this->event->record('upload_file', $temp_file_name, true);
			$postdata[$cnt] = array(
				'success'	=>	true,
				'name'		=>	$temp_file_name,//$_FILES['uploadedfile']['name'],
				'file'		=>	$destination_file,
				'size'		=>	filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file)
		 	);

		 	$postdata[$cnt] = $toReturn;
			$cnt++;
			
		}
		
		return $postdata;
		
	}
	/**
	* Manage files uploaded via flash plugin
	*/
	private function upload_flash($destination,$overwrite) {

		if( isset($_FILES["flashUploadFiles"])){
			$temp_file_name = stripslashes($_FILES['flashUploadFiles']["name"]);
			$temp_file_origin = $_FILES["flashUploadFiles"]['tmp_name'];
		}else{
			$temp_file_name = stripslashes($_FILES['uploadedfileFlash']["name"]);
			$temp_file_origin = $_FILES["uploadedfileFlash"]['tmp_name'];
		}
		$temp_file_sffx = random(10);
		$temp_file = $temp_file_name . "." . $temp_file_sffx;
		$destination_file = $destination.$temp_file_name;

		if (!move_uploaded_file($temp_file_origin,  COMODOJO_SITE_PATH . COMODOJO_TEMP_FOLDER . $temp_file)) {
			comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
			$this->event->record('upload_file', $temp_file_name, false);
			throw new Exception("Uploaded file cannot be moved", 3004);
		}

		try {
			$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
		}
		catch (Exception $e) {
			comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
			$this->event->record('upload_file', $temp_file_name, false);		
			throw $e;
		}
		
		$this->event->record('upload_file', $temp_file_name, true);
		return ='success=true,file='.$destination_file.',name='.$temp_file_name.',size='.filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file);
		
	}
	/**
	* Manage files uploaded via HTML5
	*/
	private function upload_html5($destination,$overwrite) {

		$postdata = array();
		$files_array_length = count($_FILES['uploadedfiles']['name']);

		for($i=0;$i<$len;$i++){
		
			$temp_file_name = stripslashes($_FILES['uploadedfiles']['name'][$i]);
			$temp_file_sffx = random(10);
			$temp_file = $temp_file_name . "." . $temp_file_sffx;
			$destination_file = $destination.$temp_file_name;

			if (!move_uploaded_file($_FILES['uploadedfiles']['tmp_name'][$i],  COMODOJO_SITE_PATH . COMODOJO_TEMP_FOLDER . $temp_file)) {
				comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
				$postdata[$cnt] = array( 'success' => false, 'error' => 'Uploaded file cannot be moved' );
				$this->event->record('upload_file', $temp_file_name, false);
				continue;
			}

			try {
				$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
			}
			catch (Exception $e) {
				comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
				$postdata[$i] = array( 'success' => false, 'error' => 'Uploaded file cannot be moved' );
				$this->event->record('upload_file', $temp_file_name, false);
				continue;
			}
			
			$this->event->record('upload_file', $temp_file_name, true);
			$postdata[$i] = array(
				'success'	=>	true,
				'name'		=>	$temp_file_name,//$_FILES['uploadedfile']['name'],
				'file'		=>	$destination_file,
				'size'		=>	filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file)
		 	);
			
		}
		
		return $postdata;
		
	}

}

$upload = new upload();

?>