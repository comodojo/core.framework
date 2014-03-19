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

	public $upload_file_size_limit = 0;

	public $do_safe_post_check = false;
	
	private $upload_method = false;

	private $header_accept = 'application/json';
	
	public function logic($attributes) {
		
		comodojo_load_resource('events');
		comodojo_load_resource('filesystem');

		$header_all = get_header();

		$this->header_accept = empty($header_all['Accept']) ? $this->header_accept : $header_all['Accept'];

		if (!$this->safe_post_check()) {
			comodojo_debug("Exceeded server filesize limit",'ERROR','upload');
			throw new Exception("Exceeded server filesize limit", 3104);
		}

		if (!isset($attributes['destination'])) {
			comodojo_debug("No destination path specified",'ERROR','upload');
			throw new Exception("No destination path specified", 3101);
		}
		
		$overwrite = isset($attributes['overwrite']) ? filter_var($attributes['overwrite'], FILTER_VALIDATE_BOOLEAN) : false;
		
		//if(isset($_FILES['flashUploadFiles']) || isset($_FILES['uploadedfileFlash'])) { $this->upload_method = 'FLASH'; }
		if(isset($_FILES['uploadedfiles'])) { $this->upload_method = 'HTML5'; }
		elseif (isset($_FILES['uploadedfile'])) { $this->upload_method = 'HTML'; }
		elseif (isset($_FILES['uploadedfile0'])) { $this->upload_method = 'MULTIHTML'; }
		else {
			comodojo_debug("No file received or unsupported upload method",'ERROR','upload');
			throw new Exception("No file received or unsupported upload method", 3106);
		}

		comodojo_debug("Upload will use method: ".$this->upload_method,'INFO','upload');
		
		$this->fs = new filesystem();
		$this->event = new events();
		
		if (!$this->fs->checkPermissions(COMODOJO_USER_NAME,'writer',$attributes['destination'])) {
			comodojo_debug("Invalid destination path",'ERROR','upload');
			throw new Exception("Invalid destination path", 3107);
		}
		
		try {
			switch ($this->upload_method) {
				//case 'FLASH':
				//	$to_return = $this->upload_flash($attributes['destination'],$overwrite);
				//	$contentType = 'text/plain';
				//break;
				case 'HTML5':
					$to_return = array2json($this->upload_html5($attributes['destination'],$overwrite));
				break;
				case 'HTML':
					$to_return = array2json($this->upload_html($attributes['destination'],$overwrite));
				break;
				case 'MULTIHTML':
					$to_return = array2json($this->upload_multihtml($attributes['destination'],$overwrite));
				break;
			}
			if ($this->header_accept == 'application/json') {
				$contentType = 'application/json';
			}
			else {
				$contentType = 'text/html';
				$to_return = '<textarea>'.$to_return.'</textarea>';
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
		
		//switch ($this->upload_method) {
		//	case 'FLASH':
		//		$to_return = 'success=false,error='.$error_name.',errorcode='.$error_code;
		//		$contentType = 'text/plain';
		//	break;
		//	case 'HTML':
		//	case 'MULTIHTML':
		//		$to_return = '<textarea>'.array2json(Array('success'=>false,'error'=>$error_name,'errorcode'=>$error_code)).'</textarea>';
		//		$contentType = 'text/html';
		//	break;
		//	default:
		//		$to_return = array2json(Array('success'=>false,'error'=>$error_name,'errorcode'=>$error_code));
		//		$contentType = 'text/html';
		//		//$contentType = 'application/json';
		//	break;
		//}

		$to_return = array2json(Array('success'=>false,'name'=>$error_name,'code'=>$error_code));

		if ($this->header_accept == 'application/json') {
			$contentType = 'application/json';
		}
		else {
			$contentType = 'text/html';
			$to_return = '<textarea>'.$to_return.'</textarea>';
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

		$postdata = array();

		$temp_file_name = stripslashes($_FILES['uploadedfile']["name"]);
		$temp_file_sffx = random(10);
		$temp_file = $temp_file_name . "." . $temp_file_sffx;
		$destination_file = ($destination[0] == "/" ? substr($destination, 1) : $destination) . '/' . $temp_file_name;

		try {
			$this->check_upload_file_error($_FILES['uploadedfile']['error'],$_FILES['uploadedfile']['size']);			
		}
		catch (Exception $e) {
			comodojo_debug("Error uploading file: ".$e->getMessage(),'ERROR','upload');
			$this->event->record('upload_file', $temp_file_name, false);		
			array_push($postdata, array(
				'success'	=>	false,
				'name'		=>	$temp_file_name,
				'error'		=>	$e->getMessage(),
				'code'		=>	$e->getCode()
			));
		}
		
		if (!move_uploaded_file($_FILES['uploadedfile']['tmp_name'], COMODOJO_SITE_PATH . COMODOJO_HOME_FOLDER . COMODOJO_TEMP_FOLDER . $temp_file)) {
			comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
			$this->event->record('upload_file', $temp_file_name, false);
			array_push($postdata, array(
				'success'	=>	false,
				'name'		=>	$temp_file_name,
				'error'		=>	'Uploaded file cannot be moved',
				'code'		=>	3108
			));
		}
		
		try {
			$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
			$resource_owner = COMODOJO_USER_ROLE == 0 ? 'everybody' : COMODOJO_USER_NAME;
			$this->fs->forcePermissions($destination_file,$resource_owner,$resource_owner,$resource_owner);
		}
		catch (Exception $e) {
			comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
			$this->event->record('upload_file', $temp_file_name, false);		
			array_push($postdata, array(
				'success'	=>	false,
				'name' 		=>	$temp_file_name, 
				'error'		=>	$e->getMessage(),
				'code'		=>	$e->getCode()
			));
		}
		
		$this->event->record('upload_file', $temp_file_name, true);
		array_push($postdata, array(
			'success'	=>	true,
			'name'		=>	$temp_file_name,
			'file'		=>	$destination_file,
			'size'		=>	filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file)
	 	));

		return $postdata;
	
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
			$destination_file = ($destination[0] == "/" ? substr($destination, 1) : $destination) . '/' . $temp_file_name;

			try {
				$this->check_upload_file_error($_FILES['uploadedfile'.$cnt]['error'],$_FILES['uploadedfile'.$cnt]['size']);			
			}
			catch (Exception $e) {
				comodojo_debug("Error uploading file: ".$e->getMessage(),'ERROR','upload');
				$this->event->record('upload_file', $temp_file_name, false);		
				$postdata[$cnt] = array(
					'success'	=>	false,
					'name'		=>	$temp_file_name,
					'error'		=>	$e->getMessage(),
					'code'		=>	$e->getCode()
				);
				$cnt++;
				continue;
			}

			if (!move_uploaded_file($_FILES['uploadedfile'.$cnt]['tmp_name'],  COMODOJO_SITE_PATH . COMODOJO_HOME_FOLDER . COMODOJO_TEMP_FOLDER . $temp_file)) {
				comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
				$this->event->record('upload_file', $temp_file_name, false);
				$postdata[$cnt] = array(
					'success'	=>	false,
					'name'		=>	$temp_file_name,
					'error'		=>	'Uploaded file cannot be moved',
					'code'		=>	3108
				);
				$cnt++;
				continue;
			}

			try {
				$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
				$resource_owner = COMODOJO_USER_ROLE == 0 ? 'everybody' : COMODOJO_USER_NAME;
				$this->fs->forcePermissions($destination_file,$resource_owner,$resource_owner,$resource_owner);
			}
			catch (Exception $e) {
				comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
				$this->event->record('upload_file', $temp_file_name, false);
				$postdata[$cnt] = array(
					'success' 	=>	false,
					'name' 		=>	$temp_file_name,
					'error'		=>	$e->getMessage(),
					'code'		=>	$e->getCode()
				);
				$cnt++;
				continue;
			}
			
			$this->event->record('upload_file', $temp_file_name, true);
			$postdata[$cnt] = array(
				'success'	=>	true,
				'name'		=>	$temp_file_name,
				'file'		=>	$destination_file,
				'size'		=>	filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file)
		 	);

		 	$cnt++;
			
		}
		
		return $postdata;
		
	}

	/**
	 * Manage files uploaded via flash plugin
	 */
	 //private function upload_flash($destination,$overwrite) {
	 //	
	 //	if( isset($_FILES["flashUploadFiles"])){
	 //		$temp_file_name = stripslashes($_FILES['flashUploadFiles']["name"]);
	 //		$temp_file_origin = $_FILES["flashUploadFiles"]['tmp_name'];
	 //		$temp_error_origin = $_FILES["flashUploadFiles"]['error'];
	 //		$temp_size_origin = $_FILES["flashUploadFiles"]['size'];
	 //	}else{
	 //		$temp_file_name = stripslashes($_FILES['uploadedfileFlash']["name"]);
	 //		$temp_file_origin = $_FILES["uploadedfileFlash"]['tmp_name'];
	 //		$temp_error_origin = $_FILES["uploadedfileFlash"]['error'];
	 //		$temp_size_origin = $_FILES["uploadedfileFlash"]['size'];
	 //	}
	 //	$temp_file_sffx = random(10);
	 //	$temp_file = $temp_file_name . "." . $temp_file_sffx;
	 //	$destination_file = $destination.$temp_file_name;
	 //	
	 //	try {
	 //		$this->check_upload_file_error($temp_error_origin,$temp_size_origin);			
	 //	} catch (Exception $e) {
	 //		comodojo_debug("Error uploading file: ".$e->getMessage(),'ERROR','upload');
	 //		$this->event->record('upload_file', $temp_file_name, false);		
	 //		throw $e;
	 //	}
	 //	
	 //	if (!move_uploaded_file($temp_file_origin,  COMODOJO_SITE_PATH . COMODOJO_TEMP_FOLDER . $temp_file)) {
	 //		comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
	 //		$this->event->record('upload_file', $temp_file_name, false);
	 //		throw new Exception("Uploaded file cannot be moved", 3108);
	 //	}
	 //	
	 //	try {
	 //		$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
	 //	}
	 //	catch (Exception $e) {
	 //		comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
	 //		$this->event->record('upload_file', $temp_file_name, false);		
	 //		throw $e;
	 //	}
	 //	
	 //	$this->event->record('upload_file', $temp_file_name, true);
	 //	return 'success=true,file='.$destination_file.',name='.$temp_file_name.',size='.filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file);
	 //	
	 //}

	/**
	 * Manage files uploaded via HTML5
	 */
	private function upload_html5($destination,$overwrite) {

		$postdata = array();
		$files_array_length = count($_FILES['uploadedfiles']['name']);
		
		for($i=0;$i<$files_array_length;$i++){
		
			$temp_file_name = stripslashes($_FILES['uploadedfiles']['name'][$i]);
			$temp_file_sffx = random(10);
			$temp_file = $temp_file_name . "." . $temp_file_sffx;
			$destination_file = ($destination[0] == "/" ? substr($destination, 1) : $destination) . '/' . $temp_file_name;

			try {
				$this->check_upload_file_error($_FILES['uploadedfiles']['error'][$i],$_FILES['uploadedfiles']['size'][$i]);			
			}
			catch (Exception $e) {
				comodojo_debug("Error uploading file: ".$e->getMessage(),'ERROR','upload');
				$this->event->record('upload_file', $temp_file_name, false);
				$postdata[$i] = array(
					'success' 	=>	false,
					'name' 		=>	$temp_file_name,
					'error' 	=>	$e->getMessage(),
					'code'		=>	$e->getCode()
				);
				continue;
			}

			if (!move_uploaded_file($_FILES['uploadedfiles']['tmp_name'][$i],  COMODOJO_SITE_PATH . COMODOJO_HOME_FOLDER . COMODOJO_TEMP_FOLDER . $temp_file)) {
				comodojo_debug("Uploaded file cannot be moved via move_uploaded_file",'ERROR','upload');
				$this->event->record('upload_file', $temp_file_name, false);
				$postdata[$cnt] = array(
					'success'	=>	false,
					'name'		=>	$temp_file_name,
					'error'		=>	'Uploaded file cannot be moved',
					'code'		=>	3108
				);
				continue;
			}

			try {
				$this->fs->moveFileFromTemp($temp_file,$destination_file,$overwrite);
				$resource_owner = COMODOJO_USER_ROLE == 0 ? 'everybody' : COMODOJO_USER_NAME;
				$this->fs->forcePermissions($destination_file,$resource_owner,$resource_owner,$resource_owner);
			}
			catch (Exception $e) {
				comodojo_debug("Uploaded file cannot be moved: ".$e->getMessage(),'ERROR','upload');
				$this->event->record('upload_file', $temp_file_name, false);
				$postdata[$i] = array(
					'success' 	=>	false,
					'name' 		=>	$temp_file_name,
					'error' 	=>	$e->getMessage(),
					'code'		=>	$e->getCode()
				);
				continue;
			}
			
			$this->event->record('upload_file', $temp_file_name, true);
			$postdata[$i] = array(
				'success'	=>	true,
				'name'		=>	$temp_file_name,
				'file'		=>	$destination_file,
				'size'		=>	filesize(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$destination_file)
		 	);
			
		}
		
		return $postdata;
		
	}

	private function check_upload_file_error($error_field, $size_field) {

		if ( !isset($error_field) || is_array($error_field) ) {
			throw new Exception('Invalid parameters', 3102);
		}

		switch ($error_field) {
			case UPLOAD_ERR_OK:
				return;
			break;
			case UPLOAD_ERR_INI_SIZE:
				throw new Exception('Exceeded server filesize limit', 3104);
			break;
			case UPLOAD_ERR_FORM_SIZE:
				throw new Exception('Exceeded client filesize limit', 3109);
			break;
			case UPLOAD_ERR_PARTIAL:
				throw new Exception('File partially uploaded', 3110);
			break;
			case UPLOAD_ERR_NO_FILE:
				throw new Exception('No file sent', 3103);
			break;
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new Exception('Missing temporary folder', 3111);
			break;
			case UPLOAD_ERR_CANT_WRITE:
				throw new Exception('Failed to write file to disk', 3112);
			break;
			case UPLOAD_ERR_EXTENSION:
				throw new Exception('File upload stopped by extension', 3113);
			break;
			default:
				throw new Exception('Upload unknown error', 3105);
			break;
		}

		if ($this->upload_file_size_limit != 0 AND $size_field > $this->upload_file_size_limit) {
			throw new Exception('Exceeded comodojo filesize limit', 3114);
		}

	}

}

$upload = new upload();

?>