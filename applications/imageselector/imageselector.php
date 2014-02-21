<?php

/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

comodojo_load_resource('application');

class imageselector extends application {
	
	public function init() {
		$this->add_application_method('list_directories', 'listDirectories', Array(), 'No description yet, sorry.',false);
		$this->add_application_method('list_directory', 'listDirectory', Array('filePath','fileName'), 'No description yet, sorry.',false);
	}
	
	public function listDirectories() {
		
		comodojo_load_resource('filesystem');
		
		$fs = new filesystem();
		
		try {
			$fs->filePath = "/";
			$fs->fileName = false;
			
			$fs->showHidden = false;
			$fs->filterBy = "directory";
			
			$fs->accessLevelFilter = 'reader';
			
			$fs->deepListing = true;
			
			$result = $fs->listDirectory();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result;
		
	}
	
	public function listDirectory($params) {
	
		comodojo_load_resource('filesystem');
		
		$fs = new filesystem();
		
		try {
			$fs->filePath = $params['filePath'];
			$fs->fileName = $params['fileName'];
			
			$fs->showHidden = false;
			$fs->filterBy = "extension";
			$fs->filter = array("jpeg","jpg","png","gif");
			
			$fs->generateThumbnails = true;

			$fs->accessLevelFilter = 'reader';
			
			$fs->deepListing = false;
			
			$result = $fs->listDirectory();
		}
		catch (Exception $e){
			throw $e;
		}
		
		return $result;
		
	}
	

}

?>
