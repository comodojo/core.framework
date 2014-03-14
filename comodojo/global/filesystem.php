<?php

/**
 * filesystem.php
 * 
 * The comodojo filesystem abstraction layer.
 * 
 * Filesystem only handle files in COMODOJO_HOME_FOLDER
 * 
 * This class lets you interact with filesystem using acl, ...
 * 
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class filesystem {

/*********************** PUBLIC VARS *********************/
	/**
	 * File name
	 * @var	string
	 */
	public $fileName = null;
	
	/**
	 * File path, starting from __HOME__/
	 * @var	string
	 */
	public $filePath = null;

	/**
	 * File content (used in creating new file)
	 * @var	string
	 */
	public $fileContent = null;

	/**
	 * Destination file name (when moving or copying file)
	 * @var	string
	 */
	public $destinationFileName = null;
	
	/**
	 * Destination file path (when moving or copying file)
	 * @var	string
	 */
	public $destinationFilePath = null;
	
	/**
	 * If true, destination file will be overwritten if it already
	 * exists (when moving or copying file)
	 * @var	bool
	 */
	public $overwrite = false;

	/**
	 * Username (used in add/remove permission)
	 * @var	string
	 */
	public $userName = null;
	
	/**
	 * Permission (owner,writer,reader - used in add/remove permission)
	 * @var	string
	 */
	public $permission = null;

	/**
	 * Readers' list (used in set permission)
	 * @var	Array
	 */
	public $readers = Array();
	
	/**
	 * Writers' list (used in set permission)
	 * @var	Array
	 */
	public $writers = Array();
	
	/**
	 * Owners' list (used in set permission)
	 * @var	Array
	 */
	public $owners = Array();

	/**
	 * Show hidden files in listing
	 * @var	bool
	 */
	public $showHidden = false;
	
	/**
	 * Deep-listing directories (-R)
	 * @var	bool
	 */
	public $deepListing = false;
	
	/**
	 * Deep-listing limit (0 = none)
	 * @var	integer
	 */
	public $deepListingLimit = 0;
	
	/**
	 * Filter by constraint (?)
	 * @var	(?)
	 */
	public $filterBy = false;
	public $accessLevelFilter = 'reader';
	public $filter = null;
	
	/**
	 * If true, generate thumb of image
	 * @var	bool
	 */
	public $generateThumbnails = false;
	
	/**
	 * Thumb size
	 * @var	integer
	 */
	public $thumbSize = 64;
	
/*********************** PUBLIC VARS *********************/

/********************** PRIVATE VARS *********************/
	
	private $expose_full_path = false;
	private $expose_full_resource = false;
	private $expose_file_url = false;
	
	 /**
	 * Internal pointer to home path 
	 */
	private $_homePath = null;
	
	/**
	 * Internal pointer to file path 
	 */
	private $_filePath = null;
	
	/**
	 * Internal pointer to file & ghost 
	 */
	private $_file = null;
	private $_fileGhost = null;
	private $_fileGlobalGhost = null;
	
	/**
	 * Internal pointer to destination file & ghost 
	 */
	private $_destinationFile = null;
	private $_destinationFilePath = null;
	private $_destinationFileGhost = null;
	
	/**
	 * Internal pointer to resource
	 */
	private $_resource = null;
	
	/**
	 * Internal pointer to permissions & others
	 */
	private $_userPermissions = null;
	private $_fileContent = null;
	private $_deepListingActualLevel = 0;
/********************** PRIVATE VARS *********************/

/********************* PRIVATE METHODS *******************/
	/**
	 * Check permissions on real file
	 * 
	 * @param	string	$file	The file complete reference (path+filename)
	 */
	private function _checkRealFilePermissions($file) {

		$perm = (is_readable($file) && is_writable($file));
		comodojo_debug('File '.$file.($perm ? ' IS ' : ' IS NOT ').'readable & writable','INFO','filesystem');
		return $perm;

	}

	/**
	 * Compute filename from path and home
	 */
	private function _computeFileName () {

		if (!$this->filePath AND !$this->fileName) {
			comodojo_debug('Error computing file name, no path/name specified','ERROR','filesystem');
			return false;
		}
		
		elseif ($this->filePath == '/' AND !$this->fileName) {
				
			$this->_homePath = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER;
				
			$this->_filePath = $this->_homePath;
				
			$this->_file = $this->_filePath;
				
			$this->_resource = COMODOJO_SITE_URL.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER;
				
		}
		
		elseif ($this->filePath == '/' AND $this->fileName != false) {
				
			$this->_homePath = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER;
				
			$this->_filePath = $this->_homePath;
				
			$this->_file = $this->_filePath . $this->fileName;
				
			$this->_resource = COMODOJO_SITE_URL.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$this->fileName;

		}
		else {
				
			$this->_homePath = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER;
				
			$internalFilePath = ($this->filePath[0] == "/" ? substr($this->filePath, 1) : $this->filePath);

			$cleanedFilePath = ($internalFilePath[strlen($internalFilePath)-1] == "/" ? $internalFilePath : $internalFilePath . "/");
				
			$this->_filePath = $this->_homePath . $cleanedFilePath;
				
			$this->_file = $this->_filePath . $this->fileName;
				
			$this->_resource = COMODOJO_SITE_URL.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER.$cleanedFilePath.$this->fileName;

		}
		
		comodojo_debug('File reference computed as: '.$this->_file,'INFO','filesystem');
		return true;

	}

	/**
	 * Compute ghost filename from path and home
	 */
	private function _computeFileGhost() {

		if (!$this->_computeFileName()) return false;
		
		else {
				
			$this->_fileGhost = $this->_filePath . "._" . $this->fileName . ".acl";

			if (!$this->filePath OR ($this->filePath == "/")) $this->_fileGlobalGhost = $this->_homePath . "._.acl";
			else {
				$pathParts = explode("/", $this->_filePath);
				$pathParent = substr($this->_filePath, 0, -(strlen($pathParts[sizeof($pathParts)-2])+1));
				$this->_fileGlobalGhost = $pathParent . "._" . $pathParts[sizeof($pathParts)-2] . ".acl";
			}
				
			if ($this->_checkRealFilePermissions($this->_fileGhost)) {
				comodojo_debug('Ghost file reference computed as: '.$this->_fileGhost,'INFO','filesystem');
				return $this->_fileGhost;
			}
			elseif ($this->_checkRealFilePermissions($this->_fileGlobalGhost)) {
				comodojo_debug('Ghost file reference computed as global: '.$this->_fileGlobalGhost,'INFO','filesystem');
				return $this->_fileGlobalGhost;
			}
			else {
				comodojo_debug('Error computing ghost file reference','ERROR','filesystem');
				return false;
			}
			
		}

	}

	/**
	 * Compute destination filename from path and home
	 */
	private function _computeDestinationFileName () {

		if (!$this->destinationFilePath AND !$this->destinationFileName) {
			comodojo_debug('Error computing destinaton file name, no path/name specified','ERROR','filesystem');
			return false;
		}

		else {
				
			$this->_homePath = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER;
				
			$this->_destinationFilePath = $this->_homePath . ($this->destinationFilePath[strlen($this->destinationFilePath)-1] == "/" ? $this->destinationFilePath : $this->destinationFilePath . "/");
				
			$this->_destinationFile = $this->_destinationFilePath . $this->destinationFileName;
				
			$this->_resource = COMODOJO_SITE_URL.COMODOJO_HOME_FOLDER.COMODOJO_USERS_FOLDER . ($this->destinationFilePath[strlen($this->destinationFilePath)-1] == "/" ? $this->destinationFilePath : $this->destinationFilePath . "/") . $this->destinationFileName;
			
			comodojo_debug('Destination file computed as: '.$this->_destinationFile,'INFO','filesystem');
			
			return true;
				
		}

	}

	/**
	 * Compute destination ghost filename from path and home
	 * 
	 * @todo	write debug instr. for this method
	 */
	private function _computeDestinationFileGhost() {

		if (!$this->_computeDestinationFileName()) $toReturn = false;

		else {
				
			$this->_destinationFileGhost = $this->_filePath . "._" . $this->fileName . ".acl";
				
			//if(is_readable($this->_destinationFileGhost) && is_writable($this->_destinationFileGhost)) return $this->_destinationFileGhost;
			
			if($this->_checkRealFilePermissions($this->_destinationFileGhost)) return $this->_destinationFileGhost;
			
			else {
				if (!$this->destinationFilePath) $this->_destinationFileGhost = $_homePath . "._.acl";
				
				else {
					$pathParts = explode("/", $this->_destinationFilePath);
					$pathParent = substr($this->_destinationFilePath, 0, -(strlen($pathParts[sizeof($pathParts)-2])+1));
					$this->_destinationFileGhost = $pathParent . "._" . $pathParts[sizeof($pathParts)-2] . ".acl";
				}

				//if(is_readable($this->_destinationFileGhost) && is_writable($this->_destinationFileGhost)) return $this->_destinationFileGhost;
				
				if($this->_checkRealFilePermissions($this->_destinationFileGhost)) return $this->_destinationFileGhost;
				
				else return false;
				
			}
				
		}

	}

	/**
	 * Read user permission on file from comodojo fs acl
	 */
	private function _readUserPermissions() {

		$myGhost = $this->_computeFileGhost();

		if (!$myGhost) {
			comodojo_debug('Cannot find ghost file for resource specified','ERROR','filesystem');
			return false;
		}
		
		$cont = file_get_contents($myGhost);
		
		if (!$cont) {
			comodojo_debug('Cannot read ghost file for resource specified','ERROR','filesystem');
			return false;
		}
		
		comodojo_debug('Requested resource ACL: '.$cont,'INFO','filesystem');
		$this->_userPermissions = json2array(trim($cont));

		return true;

	}

	/**
	 * Read user permission on destination file from comodojo fs acl
	 */
	private function _readUserDestinationPermissions() {

		$myGhost = $this->_computeDestinationFileGhost();

		if (!$myGhost) {
			comodojo_debug('Cannot find destination ghost file for resource specified','ERROR','filesystem');
			return false;
		}

		$cont = file_get_contents($myGhost);
		
		if (!$cont) {
			comodojo_debug('Cannot read destination ghost file for resource specified','ERROR','filesystem');
			return false;
		}
		
		comodojo_debug('Requested destination resource ACL: '.$cont,'INFO','filesystem');
		$this->_userPermissions = json2array(trim($cont));

		return true;

	}

	/**
	 * Write user permission on file (set comodojo fs acl)
	 */
	private function _writeUserPermissions() {

		$myGhost = $this->_computeFileGhost();

		if (!$myGhost) {
			comodojo_debug('Cannot find ghost file for resource specified','ERROR','filesystem');
			return false;
		}
		
		$myNewGhost = $this->_homePath . ($this->filePath[strlen($this->filePath)-1] == "/" ? $this->filePath : $this->filePath . "/") . "._" . $this->fileName . ".acl";
		
		//if (is_readable($myNewGhost) && is_writable($myNewGhost)) unlink($myNewGhost);
		if ($this->_checkRealFilePermissions($myNewGhost)) unlink($myNewGhost);
		
		$gCreate = fopen($myNewGhost, "w");
		if (!$gCreate) {
			comodojo_debug('Cannot create new ghost file','ERROR','filesystem');
			return false;
		}
		
		if (!fwrite($gCreate, array2json($this->_userPermissions))) {
			comodojo_debug('Cannot write to new ghost file','ERROR','filesystem');
			return false;
		}

		fclose($gCreate);
		
		comodojo_debug('Correctly written new user permission on resource','INFO','filesystem');		
		return true;

	}
	
	/**
	 * Check current user access level on resource
	 * 
	 * REMEMBER:
	 * - an owner can also read and write
	 * - a writer can also read
	 * - a reader will only read
	 * 
	 * @param	string	$privilege	[optional]	The privilege to check (reader, writer, owner)
	 * @param	bool	$string		[optional]	If true, wildcard like "everybody" will not be considered in privilege evaluation
	 * 
	 * @return	bool
	 */
	private function _checkUserAccessByLevel($privilege='reader', $strict=false) {

		switch (strtolower($privilege)) {
				
			case "reader":
				if (!$strict) {
					if (
					( is_array($this->_userPermissions['readers']) ? !in_array(COMODOJO_USER_ROLE, $this->_userPermissions['readers']) : COMODOJO_USER_ROLE != $this->_userPermissions['readers'] )
					AND
					( is_array($this->_userPermissions['writers']) ? !in_array(COMODOJO_USER_ROLE, $this->_userPermissions['writers']) : COMODOJO_USER_ROLE != $this->_userPermissions['writers'] )
					AND
					( is_array($this->_userPermissions['owners']) ? !in_array(COMODOJO_USER_ROLE, $this->_userPermissions['owners']) : COMODOJO_USER_ROLE != $this->_userPermissions['owners'] )
					AND
					( is_array($this->_userPermissions['readers']) ? !in_array("everybody", $this->_userPermissions['readers']) : "everybody" != $this->_userPermissions['readers'] )
					AND
					( is_array($this->_userPermissions['writers']) ? !in_array("everybody", $this->_userPermissions['writers']) : "everybody" != $this->_userPermissions['writers'] )
					AND
					( is_array($this->_userPermissions['owners']) ? !in_array("everybody", $this->_userPermissions['owners']) : "everybody" != $this->_userPermissions['owners'] )
					AND
					(COMODOJO_USER_ROLE != 1)
					) {
						$toReturn = false;
					}
					else {
						$toReturn = true;
					}
				}
				else {
					if (
					( is_array($this->_userPermissions['readers']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['readers']) : COMODOJO_USER_NAME != $this->_userPermissions['readers'] )
					AND
					( is_array($this->_userPermissions['writers']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['writers']) : COMODOJO_USER_NAME != $this->_userPermissions['writers'] )
					AND
					( is_array($this->_userPermissions['owners']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['owners']) : COMODOJO_USER_NAME != $this->_userPermissions['owners'] )
					AND
					(COMODOJO_USER_ROLE != 1)
					) {
						$toReturn = false;
					}
					else {
						$toReturn = true;
					}
				}
				break;
			case "writer":
				if (!$strict) {
					if (
					( is_array($this->_userPermissions['writers']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['writers']) : COMODOJO_USER_NAME != $this->_userPermissions['writers'] )
					AND
					( is_array($this->_userPermissions['owners']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['owners']) : COMODOJO_USER_NAME != $this->_userPermissions['owners'] )
					AND
					( is_array($this->_userPermissions['writers']) ? !in_array("everybody", $this->_userPermissions['writers']) : "everybody" != $this->_userPermissions['writers'] )
					AND
					( is_array($this->_userPermissions['owners']) ? !in_array("everybody", $this->_userPermissions['owners']) : "everybody" != $this->_userPermissions['owners'] )
					AND
					(COMODOJO_USER_ROLE != 1)
					) {
						$toReturn = false;
					}
					else {
						$toReturn = true;
					}
				}
				else {
					if (
					( is_array($this->_userPermissions['writers']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['writers']) : COMODOJO_USER_NAME != $this->_userPermissions['writers'] )
					AND
					( is_array($this->_userPermissions['owners']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['owners']) : COMODOJO_USER_NAME != $this->_userPermissions['owners'] )
					AND
					(COMODOJO_USER_ROLE != 1)
					) {
						$toReturn = false;
					}
					else {
						$toReturn = true;
					}
				}
				break;
			case "owner":
				if (!$strict) {
					if (
					( is_array($this->_userPermissions['owners']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['owners']) : COMODOJO_USER_NAME != $this->_userPermissions['owners'] )
					AND
					( is_array($this->_userPermissions['owners']) ? !in_array("everybody", $this->_userPermissions['owners']) : "everybody" != $this->_userPermissions['owners'] )
					AND
					(COMODOJO_USER_ROLE != 1)
					) {
						$toReturn = false;
					}
					else {
						$toReturn = true;
					}
				}
				else {
					if (
					( is_array($this->_userPermissions['owners']) ? !in_array(COMODOJO_USER_NAME, $this->_userPermissions['owners']) : COMODOJO_USER_NAME != $this->_userPermissions['owners'] )
					AND
					(COMODOJO_USER_ROLE != 1)
					) {
						$toReturn = false;
					}
					else {
						$toReturn = true;
					}
				}
				break;
					
		}

		return $toReturn;

	}

	/*
	 * Remove a directory recursively
	 * 
	 * @param	string	$directory	The target directory
	 */
	private function _removeDirectoryHelper($directory) {

		if(substr($directory,-1) == '/') {
			$directory = substr($directory,0,-1);
		}

		$handle = opendir($directory);
		while (false !== ($item = readdir($handle))) {
			if($item != '.' && $item != '..') {
				$path = $directory.'/'.$item;
				if(is_dir($path)) {
					$this->_removeDirectoryHelper($path);
				}else{
					comodojo_debug('Unlinking directory: '.$path,'INFO','filesystem');
					unlink($path);
				}
			}
		}
		
		closedir($handle);
		
		if(!rmdir($directory)) {
			comodojo_debug('Directory '.$directory.' can\'t be removed','ERROR','filesystem');
			return false;
		}

		comodojo_debug('Directory '.$directory.' successfully removed','INFO','filesystem');
		return true;

	}
	
	/**
	 * Create file or directory (recursively) according to content
	 * 
	 * @todo	This method is a stub. It works but needs to be rewritten from scratch
	 */
	private function _createFile() {

		if ($this->_fileContent === false) { //is a directory, so...
				
			if ($this->overwrite AND $this->_checkRealFilePermissions($this->_file)) {
					
				if (!$this->_removeDirectoryHelper($this->_file)) {
					$toReturn = false;
				}
				elseif (!mkrid($this->_file, umask(), true)) {
					comodojo_debug('Directory '.$this->_file.' can\'t be created','ERROR','filesystem');
					$toReturn = false;
				}
				else {
					$this->_userPermissions = array(
						"owners"	=> $this->owners === false ? array(COMODOJO_USER_NAME) : (is_array($this->owners) ? $this->owners : array($this->owners)),
						"readers"	=> $this->readers === false ? array(COMODOJO_USER_NAME) : (is_array($this->readers) ? $this->readers : array($this->readers)),
						"writers"	=> $this->writers === false ? array(COMODOJO_USER_NAME) : (is_array($this->writers) ? $this->writers : array($this->writers))
					);
					if (!$this->_writeUserPermissions()) {
						comodojo_debug('Directory '.$this->_file.' created WITHOUT ACL (error writing user permission)','ERROR','filesystem');
						$toReturn = false;
					}
					else {
						comodojo_debug('Directory '.$this->_file.' created','INFO','filesystem');
						$toReturn = true;
					}
				}
					
			}
			elseif (!$this->overwrite AND $this->_checkRealFilePermissions($this->_file)) {
				
				comodojo_debug('Directory '.$this->_file.' can\'t be created without overwrite flag','INFO','filesystem');
				$toReturn = false;

			}
			else {

				if (!mkrid($this->_file, umask(), true)) {
					comodojo_debug('Directory '.$this->_file.' can\'t be created','ERROR','filesystem');
					$toReturn = false;
				}
				else {
					
					$this->_userPermissions = array(
						"owners"	=> $this->owners === false ? array(COMODOJO_USER_NAME) : (is_array($this->owners) ? $this->owners : array($this->owners)),
						"readers"	=> $this->readers === false ? array(COMODOJO_USER_NAME) : (is_array($this->readers) ? $this->readers : array($this->readers)),
						"writers"	=> $this->writers === false ? array(COMODOJO_USER_NAME) : (is_array($this->writers) ? $this->writers : array($this->writers))
					);
					if (!$this->_writeUserPermissions()) {
						comodojo_debug('Directory '.$this->_file.' created WITHOUT ACL (error writing user permission)','ERROR','filesystem');
						$toReturn = false;
					}
					else {
						comodojo_debug('Directory '.$this->_file.' created','INFO','filesystem');
						$toReturn = true;
					}
				}

			}
				
		}
		else { //is a file
				
			if ($this->overwrite AND $this->_checkRealFilePermissions($this->_file)) {
					
				if (!unlink($this->_file)) {
					comodojo_debug('File '.$this->_file.' can\'t be created (it exists and can\'t be removed)','ERROR','filesystem');
					return false;
				}
				
				$fCreate = fopen($this->_file, "w");
				
				if (!$fCreate) {
					comodojo_debug('File '.$this->_file.' can\'t be created','ERROR','filesystem');
					return false;
				}
				
				$fWr = fwrite($fCreate, $this->_fileContent);

				if(!$fWr) {
					comodojo_debug('File '.$this->_file.' can\'t be written','ERROR','filesystem');
					return false;
				}
				
				fclose($fCreate);

				$this->_userPermissions = array(
					"owners"	=> $this->owners === false ? array(COMODOJO_USER_NAME) : (is_array($this->owners) ? $this->owners : array($this->owners)),
					"readers"	=> $this->readers === false ? array(COMODOJO_USER_NAME) : (is_array($this->readers) ? $this->readers : array($this->readers)),
					"writers"	=> $this->writers === false ? array(COMODOJO_USER_NAME) : (is_array($this->writers) ? $this->writers : array($this->writers))
				);
				
				if (!$this->_writeUserPermissions()) {
					comodojo_debug('File '.$this->_file.' created WITHOUT ACL (error writing user permission)','ERROR','filesystem');
					return false;
				}
				
				comodojo_debug('Directory '.$this->_file.' created','INFO','filesystem');
				$toReturn = true;
					
			}
			elseif (!$this->overwrite AND $this->_checkRealFilePermissions($this->_file)) {
				comodojo_debug('File '.$this->_file.' can\'t be created without overwrite flag','INFO','filesystem');
				$toReturn = false;

			}
			else {

				$fCreate = fopen($this->_file, "w");
				if (!$fCreate) {
					comodojo_debug('File '.$this->_file.' can\'t be created','ERROR','filesystem');
					return false;
				}
				
				$fWr = fwrite($fCreate, $this->_fileContent);

				if(!$fWr) {
					comodojo_debug('File '.$this->_file.' can\'t be written','ERROR','filesystem');
					return false;
				}
				
				fclose($fCreate);

				$this->_userPermissions = array(
					"owners"	=> $this->owners === false ? array(COMODOJO_USER_NAME) : (is_array($this->owners) ? $this->owners : array($this->owners)),
					"readers"	=> $this->readers === false ? array(COMODOJO_USER_NAME) : (is_array($this->readers) ? $this->readers : array($this->readers)),
					"writers"	=> $this->writers === false ? array(COMODOJO_USER_NAME) : (is_array($this->writers) ? $this->writers : array($this->writers))
				);
				
				if (!$this->_writeUserPermissions()) {
					comodojo_debug('File '.$this->_file.' created WITHOUT ACL (error writing user permission)','ERROR','filesystem');
					return false;
				}
				
				comodojo_debug('Directory '.$this->_file.' created','INFO','filesystem');
				$toReturn = true;

			}
				
		}

		return $toReturn;

	}

	/**
	 * Remove a file
	 * 
	 * @todo	This method is a stub. It works but needs to be rewritten from scratch
	 */
	private function _removeFile() {

		if (is_dir($this->_file)) return $this->_removeDirectoryHelper($this->_file);
		elseif (!unlink($this->_file)) {
			comodojo_debug('File '.$this->_file.' can\'t be removed','ERROR','filesystem');
			return false;
		}
		else { 
			$myNewGhost = $this->_homePath . ($this->filePath[strlen($this->filePath)-1] == "/" ? $this->filePath : $this->filePath . "/") . "._" . $this->fileName . ".acl";
			//if (is_readable($myNewGhost) && is_writable($myNewGhost)) unlink($myNewGhost);
			if ($this->_checkRealFilePermissions($myNewGhost)) unlink($myNewGhost);
			return true;
		}

	}
	
	/**
	 * Read a file
	 * 
	 * @todo	This method is a stub. It works but needs to be rewritten from scratch
	 */
	private function _readFile($encode=false) {
		if (is_dir($this->_file)) {
			comodojo_debug('A directory ('.$this->_file.') can\'t be read...','ERROR','filesystem');
			return false;
		}
		return !$encode ? file_get_contents($this->_file) : base64_encode(file_get_contents($this->_file));
	}
	
	/**
	 * Copy a file, eventually removing destination (overwrite flag)
	 * 
	 * @todo	This method is a stub. It works but needs to be rewritten from scratch
	 */
	private function _copyFile() {

		$realPerm = $this->_checkRealFilePermissions($this->_destinationFile);
		//if ( ((is_readable($this->_destinationFile) AND is_writable($this->_destinationFile)) AND !$this->overwrite) ){
		if ( $realPerm AND !$this->overwrite ) {
			comodojo_debug('File ('.$this->_destinationFile.') exists and no overwrite flag selected','ERROR','filesystem');
			return false;
		}
		else if ( $realPerm AND $this->overwrite ) {
				
			if (is_dir($this->_destinationFile)) $this->_removeDirectoryHelper($this->_destinationFile);
			else {
				if (!unlink($this->_destinationFile)) {
					comodojo_debug('Destination file ('.$this->_destinationFile.') can\'t be removed','ERROR','filesystem');
					return false;
				}
			}
				
		}
		else {
			null;
		}

		$this->_readUserPermissions();

		if (!is_dir($this->_file)) {
			if (!copy($this->_file, $this->_destinationFile)) {
				comodojo_debug('File ('.$this->_file.') can\'t be copied to '.$this->_destinationFile,'ERROR','filesystem');
				return false;
			}
		}
		else {
			if (!$this-> _copyDirectoryHelper($this->_file, $this->_destinationFile)) {
				comodojo_debug('Directory ('.$this->_file.') can\'t be copied to '.$this->_destinationFile,'ERROR','filesystem');
				return false;
			}
		}

		$this->fileName = $this->destinationFileName;
		$this->filePath = $this->destinationFilePath;

		if (!$this->_writeUserPermissions()) {
			comodojo_debug('File or directory ('.$this->_file.') copied WITHOUT ACL (error writing permissions)','ERROR','filesystem');
			return false;
		}

		return true;

	}

	/**
	 * Move a file, eventually removing destination (overwrite flag)
	 * 
	 * @todo	This method is a stub. It works but needs to be rewritten from scratch
	 */
	private function _moveFile() {

		if ( $this->_checkRealFilePermissions($this->_destinationFile) AND !$this->overwrite) {
			comodojo_debug('File ('.$this->_destinationFile.') exists and no overwrite flag selected','ERROR','filesystem');
			return false;
		}
		else if ($this->_checkRealFilePermissions($this->_destinationFile) AND $this->overwrite) {
				
			if (is_dir($this->_destinationFile)) $this->_removeDirectoryHelper($this->_destinationFile);
			else {
				if (!unlink($this->_destinationFile)) {
					comodojo_debug('Destination file ('.$this->_destinationFile.') can\'t be removed','ERROR','filesystem');
					return false;
				}
			}
				
		}
		else {
			null;
		}

		$this->_readUserPermissions();
		$prePath = $this->_filePath;
		$preFile = $this->fileName;
		
		if (!rename($this->_file, $this->_destinationFile)) {
			comodojo_debug('File ('.$this->_file.') can\'t be moved to '.$this->_destinationFile,'ERROR','filesystem');
			return false;
		}

		$this->fileName = $this->destinationFileName;
		$this->filePath = $this->destinationFilePath;

		if (!$this->_writeUserPermissions()) {
			comodojo_debug('File or directory ('.$this->_file.') moved WITHOUT ACL (error writing permissions)','ERROR','filesystem');
			return false;
		}

		$this->_fileGhost = $prePath . "._" . $preFile . ".acl";

		@unlink($this->_fileGhost);

		//overwrite the local _resource to be passed in throwSuccess
		$this->_resource = $this->_destinationFile;

		return true;

	}

	/**
	 * Move a file from temp, eventually removing destination (overwrite flag)
	 * 
	 * @todo	This method is a stub. It works but needs to be rewritten from scratch
	 */
	private function _moveFileFromTemp($tmp_file) {

		if ( $this->_checkRealFilePermissions($this->_destinationFile) AND !$this->overwrite) {
			comodojo_debug('File ('.$this->_destinationFile.') exists and no overwrite flag selected','ERROR','filesystem');
			return false;
		}
		else if ($this->_checkRealFilePermissions($this->_destinationFile) AND $this->overwrite) {
				
			if (is_dir($this->_destinationFile)) $this->_removeDirectoryHelper($this->_destinationFile);
			else {
				if (!unlink($this->_destinationFile)) {
					comodojo_debug('Destination file ('.$this->_destinationFile.') can\'t be removed','ERROR','filesystem');
					return false;
				}
			}
				
		}
		else {
			null;
		}

		$tmp_file_complete = COMODOJO_SITE_PATH.COMODOJO_TEMP_FOLDER.$tmp_file;

		if (!rename($tmp_file_complete, $this->_destinationFile)) {
			comodojo_debug('File ('.$tmp_file_complete.') can\'t be moved to '.$this->_destinationFile,'ERROR','filesystem');
			return false;
		}

		$this->fileName = $this->destinationFileName;
		$this->filePath = $this->destinationFilePath;

		if (!$this->_writeUserPermissions()) {
			comodojo_debug('File or directory ('.$this->_file.') moved WITHOUT ACL (error writing permissions)','ERROR','filesystem');
			return false;
		}

		$this->_resource = $this->_destinationFile;

		return true;

	}

	/**
	 * Copy directory helper, to handle recursive content copy
	 * 
	 * @param	string	$source	The source directory
	 * @param	string	$dest	The destination directory
	 * 
	 * @todo	This method is a stub. It works but needs to be completely rewritten!
	 */
	private function _copyDirectoryHelper($source, $destination) {

		if (mkdir($destination)) {
			comodojo_debug('Destination directory ('.$destination.') can\'t be created','ERROR','filesystem');
			return false;
		};

		$dir = dir($source);

		while (false !== $entry = $dir->read()) {

			if ($entry == "." || $entry == "..") {
				continue;
			}

			if (is_dir($source . '/' . $entry) ) {
				if (!$this->_copyDirectoryHelper($source . '/' . $entry,$destination . '/' . $entry)) {
					comodojo_debug('Can\'t copy '.$source.'/'.$entry.' to '.$destination.'/'.$entry,'ERROR','filesystem');
					return false;
				}
			}
			else {
				if (!copy($source . '/' . $entry,$destination . '/' . $entry)) {
					comodojo_debug('Can\'t copy '.$source.'/'.$entry.' to '.$destination.'/'.$entry,'ERROR','filesystem');
					return false;
				};
			}

		}

		$dir->close();
		
		return true;

	}
	
	/**
	 * Split a $file reference in filePath & fileName and set $this->filePath and $this->fileName
	 * 
	 * @param	string	$file	The file reference to split
	 */
	private function splitFileReference($file) {
		
		$reference = rtrim(trim($file),DIRECTORY_SEPARATOR);
		
		$this->fileName = basename($reference);
		
		$this->filePath = dirname($reference) == '.' ? '/' : dirname($reference);
		
	}
	
	/**
	 * Split a $destinationFile reference in destinationFilePath & destinationFileName
	 * 
	 * @param	string	$file	The file reference to split
	 */
	private function splitDestinationFileReference($file) {
		
		$reference = rtrim(trim($file),DIRECTORY_SEPARATOR);
		
		$this->destinationFileName = basename($reference);
		
		$this->destinationFilePath = dirname($reference) == '.' ? '/' : dirname($reference);
		
	}

	/**
	 * Return file/directory info
	 * 
	 * @todo	This function is a stub; it works but needs to be completely rewritten
	 */
	private function file_info($complete_directory, $directory, $file, $resource) {
		
		$complete_file = $complete_directory . "/" . $file;
		
		if (!is_dir($complete_file)) {
			
			list($mime,$icon) = comodojo_mimeByFile($complete_file);
			
			if (in_array($mime, Array('image/jpeg','image/png','image/gif'))) {
				$img = new image_tools();
				$thumb = COMODOJO_SITE_URL.COMODOJO_HOME_FOLDER.COMODOJO_THUMBNAILS_FOLDER.$img->thumbnail($complete_file, $this->thumbSize);
				
			}
			else $thumb = false;
			
			$toReturn = Array(
				"file_name"			=>	$file,
				"relative_path"		=>	substr($directory,-1) == '/' ? $directory : $directory . "/",
				"relative_resource" =>	substr($directory,-1) == '/' ? $directory : $directory . "/".$file,
				"full_path"			=>	$this->expose_full_path ? ($complete_directory . "/") : false,
				"full_resource"		=>	$this->expose_full_resource ? $complete_file : false,
				"type"				=>	'file',
				"real_type"			=> 	filetype($complete_file),
				"extension"			=>	substr(strrchr($file,'.'),1),
				"mimetype"			=>	$mime,
				"icon"				=>	$icon,
				"thumb"				=>	$thumb,
				"file_url"			=>	$this->expose_file_url ? $complete_directory : COMODOJO_SITE_URL.'download.php?p='.$directory.'&n='.$file,
				"size"				=>	filesize($complete_file),
				"last_mod" 			=>	filemtime($complete_file)
			);
			
		}
		else {
			
			$toReturn = Array(
				"file_name"			=>	$file,
				"relative_path"		=>	substr($directory,-1) == '/' ? $directory : $directory . "/",
				"relative_resource" =>	substr($directory,-1) == '/' ? $directory : $directory . "/".$file,
				"full_path"			=>	$this->expose_full_path ? ($complete_directory . "/") : false,
				"full_resource"		=>	$this->expose_full_resource ? $complete_file : false,
				"type"				=>	'folder',
				"real_type"			=> 	filetype($complete_file),
				"extension"			=>	false,
				"mimetype"			=>	false,
				"icon"				=>	'folder.png',
				"thumb"				=>	false,
				"file_url"			=>	false,
				"size"				=>	0,
				"last_mod" 			=>	filemtime($complete_file)
			);
						
		}
		
		return $toReturn;
		
	}
	
	/**
	 * List $directory according to filters
	 * 
	 * @todo	This function is a stub; it works but needs to be completely rewritten
	 */
	private function list_helper($directory) {

		$originalPath = $this->filePath;
		$originalFileName = $this->fileName;

		if ($directory == "/") $completeDirectory = $this->_homePath;
		else $completeDirectory = $this->_homePath . ($directory[0] == "/" ? substr($directory, 1) : $directory);

		//remove final slash (if any...)
		if(substr($completeDirectory,-1) == '/') $completeDirectory = substr($completeDirectory,0,-1);

		//echo $completeDirectory;
		
		$handler = opendir($completeDirectory);
		
		if (!$handler) {
			comodojo_debug('Cannot open a file for listing','ERROR','filesystem');
			return false;
		}
		
		$myLs = array();
		
		while (false !== ($item = readdir($handler))) {
				
			//skip references
			if ( ($item == ".") OR ($item == "..") ) {
				continue;
			}
				
			//skip comodojo internal files (acl, thmb, ...)
			if ( ($item[0] == ".") AND ($item[1] == "_") ) {
				continue;
			}
				
			//skip hidden files (if not requested)
			if ( (!$this->showHidden) AND ($item[0] == ".") ) continue;
			
			//hide temp folder ...
			//if ( ($directory == "") AND ($item == "temp") ) continue;
			
			//filters' battery
			
			if (strtolower($this->filterBy) == 'extension') {
				$ext = @substr(strrchr($item,'.'),1);
				if ( !in_array($ext, is_array($this->filter) ? $this->filter : array($this->filter) ) ) continue 1;
			}
			
			if (strtolower($this->filterBy) == '!extension') {
				$ext = @substr(strrchr($item,'.'),1);
				if ( in_array($ext, is_array($this->filter) ? $this->filter : array($this->filter) ) ) continue 1;
			}
			
			if (strtolower($this->filterBy) == 'type' AND (strtolower($this->filter) == 'file' OR strtolower($this->filter) == 'folder')) {
				$nature = is_dir($completeDirectory."/".$item);
				if ( (strtolower($this->filter) == 'file' AND $nature) OR (strtolower($this->filter) == 'folder' AND !$nature) ) continue 1;
			}
			
			if (strtolower($this->filterBy) == '!type' AND (strtolower($this->filter) == 'file' OR strtolower($this->filter) == 'folder')) {
				$nature = is_dir($completeDirectory."/".$item);
				if ( (strtolower($this->filter) == 'file' AND !$nature) OR (strtolower($this->filter) == 'folder' AND $nature) ) continue 1;
			}
			
			if (strtolower($this->filterBy) == 'name') {
				if (strpos($item,$this->filter) === false) continue 1;
			}
			
			if (strtolower($this->filterBy) == '!name') {
				if (strpos($item,$this->filter) !== false) continue 1;
			}
			
			//now read permissions and manage
			$this->filePath = $directory;
			$this->fileName = $item;
			$this->_readUserPermissions();
			if (!$this->_checkUserAccessByLevel($this->accessLevelFilter,false)) {
				continue;
			}
				
			$directory = $directory[0] == "/" ? substr($directory, 1) : $directory;
			
			$info = $this->file_info($completeDirectory, $directory, $item, $this->_resource);
			
			if ($info['type'] == 'file') array_push($myLs, $info);
			else {
				$this->_deepListingActualLevel++;
				$info['childs'] = ($this->deepListing == true AND (!$this->deepListingLimit OR $this->_deepListingActualLevel <= $this->deepListingLimit) ) ? $this->list_helper((substr($directory,-1) == '/' ? $directory : $directory . "/") . $item) : Array();
				array_push($myLs, $info);
				$this->_deepListingActualLevel--;
			}
				
		}

		closedir($handler);

		$this->filePath = $originalPath;
		$this->fileName = $originalFileName;

		return $myLs;

	}
/********************* PRIVATE METHODS *******************/

/********************** PUBLIC METHODS *******************/
	/**
	 * Check file existence 
	 *
	 * @param	string	$file	[optional]	If $file is set, will override global $this->fileName and $this->filePath 
	 * @return	bool	true if file exists, false otherwise
	 */
	public function checkFileExists($file=false) {

		if ($file !== false) $this->splitFileReference($file);

		if (!$this->_computeFileName()) {
			comodojo_debug('Invalid file or directory reference','ERROR','filesystem');
			throw new Exception("Invalid file or directory reference", 1101);
		}

		if(!$this->_checkRealFilePermissions($this->_file)) {
			comodojo_debug('File or directory '.$this->_file.' doesn\'t exist','INFO','filesystem');
			return false;
		}
		else {
			comodojo_debug('File or directory '.$this->_file.' exists','INFO','filesystem');
			return true;
		}

	}

	/**
	 * Check directory existence 
	 * 
	 * An alias for $filesystem::checkFileExists()
	 *
	 * @param	string	$directory	[optional]	If $directory is set, will override global $this->fileName and $this->filePath 
	 * @return	bool	true if file exists, false otherwise
	 */
	public function checkDirectoryExists($directory=false) {
		try {
			$toReturn = $this->checkFileExists($directory);
		} catch (Exception $e) {
			throw $e;
		}
		return $toReturn;
	}

	/**
	 * Get user permission on resource
	 *
	 * @param	string	$file	[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @return	array|bool					Files acl, if any, or false
	 */
	public function getPermissions($file=false) {
		
		if ($file !== false) $this->splitFileReference($file);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif (!$this->_checkUserAccessByLevel()) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			$toReturn = $this->_userPermissions;
		}

		return $toReturn;

	}

	/**
	 * Set user permissions, if use has enough privileges to do it
	 * 
	 * @param	string	$file		[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @param	string	$readers	[optional]	If $readers is set, will override global $this->readers
	 * @param	string	$writers	[optional]	If $writers is set, will override global $this->writers
	 * @param	string	$owners		[optional]	If $owners is set, will override global  $this->owners
	 * 
	 * @return	bool
	 */
	public function setPermissions($file=false, $readers=false, $writers=false, $owners=false) {

		if ($file !== false) $this->splitFileReference($file);
		
		if ($readers !== false) $this->readers = is_array($readers) ? $readers : Array($readers);
		if ($writers !== false) $this->writers = is_array($writers) ? $writers : Array($writers);
		if ($owners  !== false) $this->owners  = is_array($owners)  ? $owners  : Array($owners);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif (!$this->_checkUserAccessByLevel('owner',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			$this->_userPermissions = array(
				"readers" => is_array($this->readers) ? $this->readers : array($this->readers),
				"writers" => is_array($this->writers) ? $this->writers : array($this->writers),
				"owners"  => is_array($this->owners)  ? $this->owners  : array($this->owners)
			);
			
			if (!$this->_writeUserPermissions()) {
				comodojo_debug('Cannot write permissions even if current user has enough privileges','ERROR','filesystem');
				throw new Exception("Cannot write permissions even if current user has enough privileges", 1103);
			}
			
			$toReturn = true;
				
		}

		return $toReturn;

	}

	/**
	 * Check $user permissions on $file
	 * 
	 * User must be at least reader to check other users permissions
	 * 
	 * WARNING: This method consider only the recorded acl BUT NOT the role (i.e. if admin has no direct control on resource,
	 * 			$filesystem::checkPermissions() will return false, but admin is owner by default...)
	 * 
	 * @param	string	$user		[optional]	The user to check for; if noone passed, COMODOJO_USER_NAME (current user) will be selected
	 * @param	string	$permission	[optional]	The permission to check; if false or noone passed, method will return an array with complete perm list 
	 * @param	string	$file		[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * 
	 * @return	array|bool						An array containing 3 bools values (canRead, canWrite, isOwner) or false
	 */
	public function checkPermissions($user=COMODOJO_USER_NAME,$permission=false, $file=false) {
		
		if ($file !== false) $this->splitFileReference($file);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif (!$this->_checkUserAccessByLevel('reader',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			
			switch(strtolower($permission)) {
				
				case "reader":
					$toReturn = (in_array($user, $this->_userPermissions['readers']) OR in_array("everybody", $this->_userPermissions['readers']) ) ? true : false;
				break;
				
				case "writer":
					$toReturn = (in_array($user, $this->_userPermissions['writers']) OR in_array("everybody", $this->_userPermissions['writers']) ) ? true : false;
				break;
				
				case "owner":
					$toReturn = (in_array($user, $this->_userPermissions['owners'])  OR in_array("everybody", $this->_userPermissions['owners']) )  ? true : false;
				break;
				
				default:
					$toReturn = array(
						"canRead"	=>	(in_array($user, $this->_userPermissions['readers']) OR in_array("everybody", $this->_userPermissions['readers']) ) ? true : false,
						"canWrite"	=>	(in_array($user, $this->_userPermissions['writers']) OR in_array("everybody", $this->_userPermissions['writers']) ) ? true : false,
						"isOwner"	=>	(in_array($user, $this->_userPermissions['owners'])  OR in_array("everybody", $this->_userPermissions['owners']) )  ? true : false
					);
				break;
				
			}
			
			return $toReturn;
			
		}

	}

	//public function addPermission() {} //to be coded...

	/**
	 * Create new file
	 *
	 * it handle new file requests, write down the file on fs and also the ghost containing related acl;
	 * request user access rights (writer) in directory with acl control.
	 *
	 * @param	string	$file			[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @param	string	$fileContent	[optional]	If $fileContent is set, will override global $this->fileContent
	 * @param	string	$overwrite		[optional]	If $overwrite is set, will override global $this->overwrite
	 * @param	string	$readers		[optional]	If $readers is set, will override global $this->readers
	 * @param	string	$writers		[optional]	If $writers is set, will override global $this->writers
	 * @param	string	$owners			[optional]	If $owners is set, will override global  $this->owners
	 * 
	 * @return	bool								True in case of success, false in case of no privileges; exception in case of error
	 */
	public function createFile($file=false, $fileContent=false, $overwrite=false, $readers=false, $writers=false, $owners=false) {

		if ($file !== false) $this->splitFileReference($file);
		if ($fileContent !== false) $this->fileContent = $fileContent;
		if ($overwrite !== false) $this->overwrite = true;
		
		if ($readers !== false) $this->readers = is_array($readers) ? $readers : Array($readers);
		if ($writers !== false) $this->writers = is_array($writers) ? $writers : Array($writers);
		if ($owners  !== false) $this->owners  = is_array($owners)  ? $owners  : Array($owners);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif (!$this->_checkUserAccessByLevel('writer',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			//force void char writing to file, or _createFile will take it as a directory!
			if (!$this->fileContent) $this->_fileContent = " ";
			else $this->_fileContent = $this->fileContent;
				
			if (!$this->_createFile()) {
				comodojo_debug('Error creating new file '.$this->_file,'ERROR','filesystem');
				throw new Exception("Error creating new file ".$this->_file, 1104);
			}
			
			return true;
				
		}

	}

	/**
	 * Create new directory
	 *
	 * it handle new directory requests, write down it on fs and also the ghost containing related acl;
	 * request user access rights (writer) in directory with acl control.
	 *
	 * @param	string	$file			[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @param	string	$overwrite		[optional]	If $overwrite is set, will override global $this->overwrite
	 * @param	string	$readers		[optional]	If $readers is set, will override global $this->readers
	 * @param	string	$writers		[optional]	If $writers is set, will override global $this->writers
	 * @param	string	$owners			[optional]	If $owners is set, will override global  $this->owners
	 * 
	 * @return	bool								True in case of success, false in case of no privileges; exception in case of error
	 */
	public function createDirectory($file=false, $overwrite=false, $readers=false, $writers=false, $owners=false) {
		
		if ($file !== false) $this->splitFileReference($file);
		if ($overwrite !== false) $this->overwrite = true;
		
		if ($readers !== false) $this->readers = is_array($readers) ? $readers : Array($readers);
		if ($writers !== false) $this->writers = is_array($writers) ? $writers : Array($writers);
		if ($owners  !== false) $this->owners  = is_array($owners)  ? $owners  : Array($owners);
		
		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif (!$this->_checkUserAccessByLevel('writer',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {

			//force _fileContent as false, so _createFile will understand it's a directory
			$this->_fileContent = false;
				
			if (!$this->_createFile()) {
				comodojo_debug('Error creating new directory '.$this->_file,'ERROR','filesystem');
				throw new Exception("Error creating new directory ".$this->_file, 1108);
			}
			
			return true;
				
		}

	}

	/**
	 * Read a file from filesystem
	 *
	 * @param	string	$file		[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @param	bool	$decode		[optional]	Turn on base64 decoding of file
	 * 
	 * @return	string|bool						File content as a string if success, false in case of no privileges; exception in case of error
	 */
	public function readFile($file=false,$decode=false) {
		
		if ($file !== false) $this->splitFileReference($file);
		
		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif (!$this->_checkUserAccessByLevel('reader',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			
			$fileContent = $this->_readFile($decode);	
			if (!$fileContent) {
				comodojo_debug('File cannot be readed or it is a directory','ERROR','filesystem');
				throw new Exception("File cannot be readed or it is a directory", 1104);
			}
			return $fileContent;
				
		}

	}

	/**
	 * Unlink/remove a file from filesystem
	 * 
	 * @param	string	$file	[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 *
	 * @return	bool						True if success, false in case of no privileges; exception in case of error
	 */
	public function removeFile($file=false) {

		if ($file !== false) $this->splitFileReference($file);
		
		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif (!$this->_checkUserAccessByLevel('owner',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			
			if (!$this->_removeFile()) {
				comodojo_debug('File cannot be removed','ERROR','filesystem');
				throw new Exception("File cannot be removed", 1106);
			}
			
			return true;
				
		}

	}

	/**
	 * Unlink/remove a directory from filesystem
	 * 
	 * @param	string	$directory	[optional]	If $directory is set, will override global $this->fileName and $this->filePath
	 *
	 * @return	bool							True if success, false in case of no privileges; exception in case of error
	 */
	public function removeDirectory($directory=false) {

		try {
			$toReturn = $this->removeFile($directory);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $toReturn;

	}

	/**
	 * Copy file
	 * 
	 * @param	string	$file				[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @param	string	$destinationFile	[optional]	If $destinationFile is set, will override global $this->destinationFileName and $this->destinationFilePath
	 * @param	string	$overwrite			[optional]	If $overwrite is set, will override global $this->overwrite
	 *
	 * @return	bool						True if success, false in case of no privileges; exception in case of error
	 */
	public function copyFile($file=false, $destinationFile=false, $overwrite=false) {

		if ($file !== false) $this->splitFileReference($file);
		if ($destinationFile !== false) $this->splitDestinationFileReference($destinationFile);

		$this->overwrite = $overwrite === true ? true : false;

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif(!$this->_checkUserAccessByLevel('reader',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		elseif (!$this->_readUserDestinationPermissions()) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		elseif(!$this->_checkUserAccessByLevel('writer',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
				
			if (!$this->_copyFile()) {
				comodojo_debug('File cannot be copied','ERROR','filesystem');
				throw new Exception("File cannot be copied", 1107);
			}
			
			return true;
				
		}

	}

	/**
	 * Copy directory
	 * 
	 * @param	string	$directory				[optional]	If $directory is set, will override global $this->fileName and $this->filePath
	 * @param	string	$destinationDirectory	[optional]	If $destinationDirectory is set, will override global $this->destinationFileName and $this->destinationFilePath
	 * @param	string	$overwrite				[optional]	If $overwrite is set, will override global $this->overwrite
	 *
	 * @return	bool						True if success, false in case of no privileges; exception in case of error
	 */
	public function copyDirectory($directory=false, $destinationDirectory=false, $overwrite=false) {

		try {
			$toReturn = $this->copyFile($directory, $destinationDirectory, $overwrite);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $toReturn;

	}

	/**
	 * Move file
	 * 
	 * @param	string	$file				[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @param	string	$destinationFile	[optional]	If $destinationFile is set, will override global $this->destinationFileName and $this->destinationFilePath
	 * @param	string	$overwrite			[optional]	If $overwrite is set, will override global $this->overwrite
	 *
	 * @return	bool						True if success, false in case of no privileges; exception in case of error
	 */
	public function moveFile($file=false, $destinationFile=false, $overwrite=false) {

		if ($file !== false) $this->splitFileReference($file);
		if ($destinationFile !== false) $this->splitDestinationFileReference($destinationFile);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif(!$this->_checkUserAccessByLevel('owner',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		elseif (!$this->_readUserDestinationPermissions()) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		elseif(!$this->_checkUserAccessByLevel('writer',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
				
			if (!$this->_moveFile()) {
				comodojo_debug('File cannot be moved','ERROR','filesystem');
				throw new Exception("File cannot be moved", 1109);
			}
			
			return true;
				
		}

	}

	/**
	 * Move file from temp directory
	 * 
	 * @param	string	$file				[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 * @param	string	$destinationFile	[optional]	If $destinationFile is set, will override global $this->destinationFileName and $this->destinationFilePath
	 * @param	string	$overwrite			[optional]	If $overwrite is set, will override global $this->overwrite
	 *
	 * @return	bool						True if success, false in case of no privileges; exception in case of error
	 */
	public function moveFileFromTemp($file, $destinationFile=false, $overwrite=false) {

		if ($destinationFile !== false) $this->splitDestinationFileReference($destinationFile);

		if (!$this->_readUserDestinationPermissions()) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		elseif(!$this->_checkUserAccessByLevel('writer',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
				
			if (!$this->_moveFileFromTemp($file)) {
				comodojo_debug('File cannot be moved','ERROR','filesystem');
				throw new Exception("File cannot be moved", 1109);
			}
			
			return true;
				
		}

	}

	/**
	 * Move directory
	 * 
	 * @param	string	$directory				[optional]	If $directory is set, will override global $this->fileName and $this->filePath
	 * @param	string	$destinationDirectory	[optional]	If $destinationDirectory is set, will override global $this->destinationFileName and $this->destinationFilePath
	 * @param	string	$overwrite				[optional]	If $overwrite is set, will override global $this->overwrite
	 *
	 * @return	bool						True if success, false in case of no privileges; exception in case of error
	 */
	public function moveDirectory($directory=false, $destinationDirectory=false, $overwrite=false) {

		try {
			$toReturn = $this->moveFile($directory, $destinationDirectory, $overwrite);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $toReturn;

	}

	/**
	 * List directory content, according to filters
	 * 
	 * For more information on filters see $filesystem::filterBy, $filesystem::filter, $filesystem::deepListingLevel.
	 * 
	 * For more information of returned array, please see $filesystem::getInfo; please note also that nested directories
	 * are returned like nested arrays in "childs" array elements.
	 * 
	 * @param	string	$directory			[optional]	If $directory is set, will override global $this->fileName and $this->filePath
	 *
	 * @return	array						Array if success, false in case of no privileges; exception in case of error
	 */
	public function listDirectory($directory=false) {

		if ($directory !== false) $this->splitFileReference($directory);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif(!$this->_checkUserAccessByLevel('reader',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			
			comodojo_load_resource('mime_types');
			/*if ($this->generateThumbnails)*/ comodojo_load_resource('image_tools');
			
			$toReturn = $this->list_helper((substr($this->filePath,-1) == '/' ? $this->filePath : $this->filePath . "/") . $this->fileName);
			
			if (!$toReturn) {
				comodojo_debug('Error listing resource '.$directory,'ERROR','filesystem');
				throw new Exception("No file founded or no acl for this directory", 1111);
			}
			
			return $toReturn;
				
		}

	}
	
	/**
	 * Get file or directory information
	 * 
	 * This method returns an array containing:
	 * 
	 * @todo
	 * 
	 * @param	string	$file			[optional]	If $file is set, will override global $this->fileName and $this->filePath
	 *
	 * @return	array					Array if success, false in case of no privileges; exception in case of error
	 */
	public function getInfo($file=false) {

		if ($file !== false) $this->splitFileReference($file);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		elseif(!$this->_checkUserAccessByLevel('reader',false)) {
			comodojo_debug('User has not enough privileges','INFO','filesystem');
			throw new Exception("Not enough privileges", 1112);
		}
		else {
			
			comodojo_load_resource('mime_types');
			if ($this->generateThumbnails) comodojo_load_resource('image_tools');
			
			if ($this->filePath == "/") $completeDirectory = $this->_homePath;
			else $completeDirectory = $this->_homePath . ($directory[0] == "/" ? substr($directory, 1) : $directory);
			
			return $this->file_info($this->_homePath.($this->filePath[0] == "/" ? substr($this->filePath, 1) : $this->filePath), $this->filePath, $this->fileName, $this->_resource);
				
		}

	}
/********************** PUBLIC METHODS *******************/

/********************* SPECIAL METHODS *******************/
	/**
	 * Force user permissions without cheking ownership on file
	 *
	 * This function is useful in case of file/directory permission remapping by automated procedures, like 
	 * "uploader" that invoke it from constructor to reset user permission on a new, uploaded file.
	 * 
	 * Use with care...
	 *
	 * @param	string	$this->filePath	passed file path (base directory = /home/) - could be null, default null
	 * @param	string	$this->fileName	passed file name - could NOT be null, default null
	 * @return	array	$this->_throwSuccess/_throwFailure();
	 * @access	public
	 */
	public function forcePermissions($file=false, $readers=false, $writers=false, $owners=false) {

		if ($file !== false) $this->splitFileReference($file);
		
		if ($readers !== false) $this->readers = is_array($readers) ? $readers : Array($readers);
		if ($writers !== false) $this->writers = is_array($writers) ? $writers : Array($writers);
		if ($owners  !== false) $this->owners  = is_array($owners)  ? $owners  : Array($owners);

		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		else {
			$this->_userPermissions = array(
				"readers" => is_array($this->readers) ? $this->readers : array($this->readers),
				"writers" => is_array($this->writers) ? $this->writers : array($this->writers),
				"owners"  => is_array($this->owners)  ? $this->owners  : array($this->owners)
			);
			
			if (!$this->_writeUserPermissions()) {
				comodojo_debug('Cannot write permissions even if current user has enough privileges','ERROR','filesystem');
				throw new Exception("Cannot write permissions even if current user has enough privileges", 1103);
			}
			
			$toReturn = true;
				
		}

		return $toReturn;

	}

	/**
	 * Create home directory for new users
	 *
	 * It's identical to $filesystem::createDirectory BUT unprivileged (don't check for write perm on fs) and limited to __HOME__ dir creation.
	 * 
	 * Use with care...
	 *
	 * @param	string	$user	Username to process
	 * 
	 * @return	bool			True in case of success, false in case of no privileges; exception in case of error
	 */
	public function createHome($user) {
		
		if (!$user) {
			comodojo_debug('Cannot create user home without username','ERROR','filesystem');
			throw new Exception("Cannot create user home without username", 1110);
		}
		
		$this->filePath = "/";
		$this->fileName = $user;
		
		$this->readers = Array($user);
		$this->writers = Array($user);
		$this->owners  = Array('nobody');
		
		if (!$this->_readUserPermissions()) {
			comodojo_debug('No file founded or no acl for this directory','ERROR','filesystem');
			throw new Exception("No file founded or no acl for this directory", 1102);
		}
		else {

			//force _fileContent as false, so _createFile will understand it's a directory
			$this->_fileContent = false;
				
			if (!$this->_createFile()) {
				comodojo_debug('Error creating new directory '.$this->_file,'ERROR','filesystem');
				throw new Exception("Error creating new directory ".$this->_file, 1108);
			}
			
			return true;
				
		}

	}
/********************* SPECIAL METHODS *******************/

}

function loadHelper_filesystem() { return false; }

?>