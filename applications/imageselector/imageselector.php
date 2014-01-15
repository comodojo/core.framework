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
		$fs->filePath = "/";
		$fs->fileName = false;
		$fs->showHidden = false;
		$fs->filterBy = "directory";
		$fs->generateIcons = true;
		$fs->generateThumbnails = true;
		$fs->deepListing = true;
		
		$ret = $fs->listDirectory();
		
		$this->success = $ret['success'];
		$completeRet = $ret['result'];
		
		return $completeRet;
		
	}
	
	private function listDirectory() {
	
		if (!function_exists("loadHelper_fsLayer")) {
			require($_SESSION[SITE_UNIQUE_IDENTIFIER]['sitePath'] . "comodojo/abstractionLayers/fsLayer.php");
		}
		
		$fs = new fs();
		$fs->filePath = $this->filePath;
		$fs->fileName = $this->fileName;
		$fs->showHidden = false;
		$fs->filterBy = "extension";
		$fs->filter = array("jpeg","jpg","png","gif");
		$fs->generateIcons = true;
		$fs->generateThumbnails = true;
		$fs->deepListing = false;
		
		$ret = $fs->listDirectory();

		$this->success = $ret['success'];
		$completeRet = $ret['result'];
		
		return $completeRet;
		
	}
	

}

?>
