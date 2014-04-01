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
		$this->add_application_method('list_reader', 'listReader', Array(), 'No description available, sorry',false);
		$this->add_application_method('list_writer', 'listWriter', Array(), 'No description available, sorry',false);
		$this->add_application_method('list_owner', 'listOwner', Array(), 'No description available, sorry',false);
	}

	public function listReader() {
		try {
			$result = $this->listDirectory('reader');
		}
		catch (Exception $e){
			throw $e;
		}
		return $result;
	}

	public function listWriter() {
		try {
			$result = $this->listDirectory('writer');
		}
		catch (Exception $e){
			throw $e;
		}
		return $result;
	}

	public function listOwner() {
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