<?php

/**
 * zip.php
 * 
 * zip/unzip files in comodojo using PHP native ZipArchie or PclZip (fallback)
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
class zip {

	public $skip_hidden_files = true;

	public $skip_comodojo_internal = true;

	private $zip_archive = null;

	public final function __construct() {

		if (class_exists('ZipArchive')) {
			$this->use_pclzip = false;
		}
		else {
			throw new Exception("No php ZipArchive extension", 3026);
		}
		
	}

	public function open($zip_file) {
		try {
			if (!empty($zip_file)) {
				$this->zip_archive = $this->open_file($zip_file, null);
			}
			else {
				comodojo_debug('Archive name not specified','ERROR','zip');
				throw new Exception("Archive name not specified", 3025);
			}
		}
		catch (Exception $e) {
			throw $e;
		}

		return $this;
	}

	public function create($zip_file) {
		try {
			if (!empty($zip_file)) {
				$this->zip_archive = $this->open_file($zip_file, ZipArchive::CREATE);
			}
			else {
				comodojo_debug('Archive name not specified','ERROR','zip');
				throw new Exception("Archive name not specified", 3025);
			}
		}
		catch (Exception $e) {
			throw $e;
		}

		return $this;
	}

	public function check($zip_file) {
		try {
			if (!empty($zip_file)) {
				$this->zip_archive = $this->open_file($zip_file, ZipArchive::CHECKCONS)->close();
			}
			else {
				comodojo_debug('Archive name not specified','ERROR','zip');
				throw new Exception("Archive name not specified", 3025);
			}
		}
		catch (Exception $e) {
			throw $e;
		}

		return true;
	}

	public function close() {
		return $this->zip_archive->close();
	}

	public function add_files($file_name_or_array) {

		try {
			if (empty($file_name_or_array)) {
				comodojo_debug('No file(s) specified','ERROR','zip');
				throw new Exception("No file(s) specified", 3028);
			}
			elseif (is_array($file_name_or_array)) {
				foreach ($file_name_or_array as $file_name) {
					$this->add_file($file_name);
				}
			}
			else {
				$this->add_file($file_name_or_array);
			}
		}
		catch (Exception $e) {
			throw $e;
		}

		return $this;

	}

	public function delete_files($file_name_or_array) {

		try {
			if (empty($file_name_or_array)) {
				comodojo_debug('No file(s) specified','ERROR','zip');
				throw new Exception("No file(s) specified", 3028);
			}
			elseif (is_array($file_name_or_array)) {
				foreach ($file_name_or_array as $file_name) {
					$this->delete_file($file_name);
				}
			}
			else {
				$this->delete_file($file_name_or_array);
			}
		}
		catch (Exception $e) {
			throw $e;
		}

		return $this;

	}

	public function extract_files($to) {

		$ext = $this->zip_archive($to);
		if ($ext !== true) {
			comodojo_debug('Extraction error','ERROR','zip');
			throw new Exception('Extraction error', 3027);
		}
    	$zip->close();

	}

	private function open_file($file, $flag) {

		$this->zip_archive = new ZipArchive;
		$z_open_result = $this->zip_archive->open($file,$flag);
		if ($z_open_result !== true) {
			list ($e,$c) = $this->eval_return_code($z_open_result);
			comodojo_debug($e,'ERROR','zip');
			throw new Exception($e, $c);
		}
	
	}

	private function add_file($file) {

		$file = str_replace('\\', '/', realpath($file));

		if (is_dir($file) === true) {
			
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file), RecursiveIteratorIterator::SELF_FIRST);

			foreach ($files as $item) {

				$item = str_replace('\\', '/', $item);

				$item_name = substr($item, strrpos($item, '/')+1);

				//skip root and self folder
				if( in_array($item_name, array('.', '..')) ) {
					continue;
				}
				//skip comodojo internal files (acl, thmb, ...)
				if( $item_name[0] == "." AND $item_name[1] == "_" AND  $this->skip_comodojo_internal) {
					continue;
				}
				//skip hidden files (if not requested)
				if( $item_name[0] == "." AND  $this->skip_hidden_files) {
					continue;
				}

				$item = realpath($item);

				if (is_dir($item) === true) {
					$add = $this->zip_archive->addEmptyDir(str_replace($files . '/', '', $item . '/'));
				}
				else if (is_file($file) === true) {
					$add = $this->zip_archive->addFromString(str_replace($files . '/', '', $item), file_get_contents($item));
				}

				if ($add === false) {
					comodojo_debug("Error adding file",'ERROR','zip');
					throw new Exception("Error adding file", 3029);
				}
		
			}
	
		}
		else (is_file($file) === true) {
			$add = $this->zip_archive->addFromString(basename($file), file_get_contents($file));
			if ($add === false) {
				comodojo_debug("Error adding file",'ERROR','zip');
				throw new Exception("Error adding file", 3029);
			}
		}
		else {
			comodojo_debug("Error adding file",'ERROR','zip');
			throw new Exception("Error adding file", 3029);
		}

	}

	private function delete_file($file) {

		$file_index = $this->zip_archive->locateName($file, ZipArchive::FL_NOCASE)

		if ($file_index === false) {
			comodojo_debug("Can't remove file",'ERROR','zip');
			throw new Exception("Can't remove file", 3022);
		}
		else {
			$del = $this->zip_archive->deleteIndex($file_index);
		}

		if ($del === false) {
			comodojo_debug("Can't remove file",'ERROR','zip');
			throw new Exception("Can't remove file", 3022);
		}

	}

	private function eval_return_code ($code) {
		switch($code) {
			case 1:
				$c = 3001;
				$e = "Multi-disk zip archives not supported";
			break;
			case 2:
				$c = 3002;
				$e = "Renaming temporary file failed";
			break;
			case 3:
				$c = 3003;
				$e = "Closing zip archive failed";
			break;
			case 4:
				$c = 3004;
				$e = "Seek error";
			break;
			case 5:
				$c = 3005;
				$e = "Read error";
			break;
			case 6:
				$c = 3006;
				$e = "Write error";
			break;
			case 7:
				$c = 3007;
				$e = "CRC error";
			break;
			case 8:
				$c = 3008;
				$e = "Containing zip archive was closed";
			break;
			case 9:
				$c = 3009;
				$e = "No such file";
			break;
			case 10:
				$c = 3010;
				$e = "File already exists";
			break;
			case 11:
				$c = 3011;
				$e = "Can't open file";
			break;
			case 12:
				$c = 3012;
				$e = "Failure to create temporary file";
			break;
			case 13:
				$c = 3013;
				$e = "Zlib error";
			break;
			case 14:
				$c = 3014;
				$e = "Malloc failure";
			break;
			case 15:
				$c = 3015;
				$e = "Entry has been changed";
			break;
			case 16:
				$c = 3016;
				$e = "Compression method not supported";
			break;
			case 17:
				$c = 3017;
				$e = "Premature EOF";
			break;
			case 18:
				$c = 3018;
				$e = "Invalid argument";
			break;
			case 19:
				$c = 3019;
				$e = "Not a zip archive";
			break;
			case 20:
				$c = 3020;
				$e = "Internal error";
			break;
			case 21:
				$c = 3021;
				$e = "Zip archive inconsistent";
			break;
			case 22:
				$c = 3022;
				$e = "Can't remove file";
			break;
			case 23:
				$c = 3023;
				$e = "Entry has been deleted";
			break;
			default:
				$c = 3024;
				$e = "Zip generic error";
			break;
		}
		return Array($e,$c);
	}
}

?>