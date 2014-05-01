<?php

/**
 * Select file from comodojo users' home
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class filepicker extends application {
	
	public function init() {
		$this->add_application_method('listReader', 'list_reader', Array(), 'No description available, sorry',false);
		$this->add_application_method('listWriter', 'list_writer', Array(), 'No description available, sorry',false);
		$this->add_application_method('listOwner', 'list_owner', Array(), 'No description available, sorry',false);
	}

	public function list_reader() {
		try {
			$result = $this->listDirectory('reader');
		}
		catch (Exception $e){
			throw $e;
		}
		return $result;
	}

	public function list_writer() {
		try {
			$result = $this->listDirectory('writer');
		}
		catch (Exception $e){
			throw $e;
		}
		return $result;
	}

	public function list_owner() {
		try {
			$result = $this->listDirectory('owner');
		}
		catch (Exception $e){
			throw $e;
		}
		return $result;
	}

	private function listDirectory($access) {
		
		//$access = in_array(strtolower($params['access']), Array('reader','writer','owner')) ? $params['access'] : 'reader';
		
		comodojo_load_resource('filesystem');
		
		$fs = new filesystem();
		
		try {
			$fs->filePath = "/";
			$fs->fileName = false;
			
			$fs->showHidden = false;
			
			$fs->accessLevelFilter = $access;
			
			$fs->deepListing = true;

			$fs->generateThumbnails = false;
			
			$result = $fs->listDirectory();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result;
		
	}
	
}

?>