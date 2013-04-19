<?php

/**
 * download.php
 * 
 * Get files in comodojo users' home directory
 * 
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 
require 'comodojo/global/comodojo_basic.php';

class download extends comodojo_basic {
	
	public $script_name = 'download.php';
	
	public $use_session_transport = true;
	
	public $require_valid_session = false;
	
	public $do_authentication = true;
	
	public function logic($attributes) {
		
		comodojo_load_resource('events');
		
		if (!isset($attributes['p']) OR !isset($attributes['n'])) {
			comodojo_debug("Download error: no file specified",'ERROR','download');
			throw new Exception("No file specified", 0);
		}
		
		comodojo_load_resource('filesystem');
		
		$fs = new filesystem();
		
		try{
			$fs->filePath = $attributes['p'];
			$fs->fileName = $attributes['n'];
			if ($fs->checkPermissions( (is_null(COMODOJO_USER_NAME) ? 'everybody' : COMODOJO_USER_NAME), 'reader')) {
				comodojo_debug("Download error: user ".COMODOJO_USER_NAME." has no reader access level for resource: ".$attributes['n']." in folder: ".$attributes['p'],'ERROR','download');
				throw new Exception($attributes['p']."/".$attributes['n'], 0);
			}
			else {
				$fInfo = $fs->getInfo();
				if ($fInfo['type'] == 'folder') {
					comodojo_debug("Download error: requested resource is a folder: ".$attributes['n']." in folder: ".$attributes['p'],'ERROR','download');
					throw new Exception($attributes['p']."/".$attributes['n'], 0);
				}
				$fMime = $fInfo['mimetype'];
				$fContent = $fs->readFile();
				
				$event = new events();
				$event->record('download_file', $attributes['p']."/".$attributes['n'], true);
				
				set_header(Array(
					'statusCode'			=>	200,
					'contentType'			=>	$fMime,
					'contentDisposition'	=>	$fMime == 'application/octet-stream' ? 'attachment' : 'inline',
					'filename'				=>	$attributes['n']
				), strlen($fContent));
				
				return $fContent; 
			}
		}
		catch (Exception $e) {
			throw $e;
		}
		
	}
	
	public function error($error_code, $error_name) {
		
		$event = new events();
		$event->record('download_file', $error_name, false);
		
		set_header(Array(
			'statusCode'	=>	302,
			'contentType'	=> 'text/html',
			'location'		=>	COMODOJO_BOOT_URL.'404.php'
		), 0);
		
		return false;
		
	}
	
}

$download = new download();

?>