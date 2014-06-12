<?php

/**
 * cache.php
 * 
 * Comodojo unified cache control.
 * 
 * This class can cache data as simple string or structures/arrays stored in JSON/XML/YAML form.
 * Retrieving cache, it's possible to transform back metadata in array using the $decode parameter.
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 
class cache {
	
	/**
	 * If true, cache methods will not throw exception in case of error.
	 * @var	bool
	 */
	public $fail_silently = true;
	
	/**
	 * Set cache.
	 * 
	 * Cache requires $data, that could be an array or a string.
	 * 
	 * If $data is an array, it will be encoded in JSON (default), XML or JAML, depending on $format parameter.
	 * If it's a string, it will be cached like plaintext.
	 *
	 * @param	string	$data			Data to cache.
	 * @param	string	$request		The request to associate the cache to.
	 * @param	string	$format			[optional] Format to encode data to (JSON, XML, YAML). Default JSON.
	 * @param	bool	$userDependent	[optional] If true, cache access will be limited to logged user.
	 * 
	 * @return	bool
	 */
	public final function set_cache($data, $request/*, $format='JSON'*/, $userDependent=false) {
		
		if (!COMODOJO_CACHE_ENABLED) {
			comodojo_debug('Caching administratively disabled','INFO','cache');
			return false;
		}
		
		if (empty($data)) {
			comodojo_debug('Nothing to cache','INFO','cache');
			return false;
		}
		
		$cacheTag = $userDependent ? md5($request).'_'.COMODOJO_USER_NAME : md5($request);
		
		$cacheFile = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.$cacheTag;

		$f_data = serialize(Array("cache_content" => $data));

		//if (is_scalar($data)) {
		//	$f_data = $data;
		//}
		//elseif (is_array($data)) {
		//	switch (strtoupper($format)) {
		//		case 'XML':
		//			$f_data = array2xml($data);
		//		break;
		//		case 'YAML':
		//			$f_data = array2yaml($data);
		//		break;
		//		default:
		//			$f_data = array2json($data);
		//		break;
		//	}
		//}
		//else {
		//	comodojo_debug('Cannot cache something different from array or scalar','ERROR','cache');
		//	if ($this->fail_silently) {
		//		return false;
		//	}
		//	else {
		//		throw new Exception("Cannot cache something different from array or string", 1202);
		//	}
		//}		

		$cached = file_put_contents($cacheFile, $f_data);
		if ($cached === false) {
			comodojo_debug('Error writing to cache folder','ERROR','cache');
			if ($this->fail_silently) {
				return false;
			}
			else {
				throw new Exception("Error writing to cache cache folder", 1201);
			}
		}

		//$fh = fopen(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.$cacheTag, 'w');
		//if (!$fh) {
		//	comodojo_debug('Error writing to cache folder','ERROR','cache');
		//	if ($this->fail_silently) {
		//		return false;
		//	}
		//	else {
		//		throw new Exception("Error writing to cache cache folder", 1201);
		//	}
		//}
		//if (!fwrite($fh, $f_data)) {
		//	fclose($fh);
		//	comodojo_debug('Error writing to cache folder','ERROR','cache');
		//	if ($this->fail_silently) {
		//		return false;
		//	}
		//	else {
		//		throw new Exception("Error writing to cache cache folder", 1201);
		//	}
		//}
		//
		//fclose($fh);
		
		return true;
		
	}
		
	/**
	 * Get cache
	 * 
	 * If $format parameter is not false, cache will be decoded according to format specified.
	 * If it's true, cache will try to decode data from JSON
	 *
	 * @param	string	$request		The request to associate the cache to.
	 * @param	string	$decode			[optional] Decode cache from specified format to array (JSON,XML,YAML); if false, disable decoding (will return the plain text).
	 * @param	bool	$userDependent	[optional] If true, cache access will be limited to logged user.
	 * 
	 * @return	array|string|bool		Data cached, in array or plaintext, or false if no cache saved.
	 */
	public final function get_cache($request, /*$decode=false,*/ $userDependent=false, $ttl=COMODOJO_CACHE_TTL) {
		
		if (!COMODOJO_CACHE_ENABLED) {
			comodojo_debug('Caching administratively disabled','INFO','cache');
			return false;
		}
		
		$currentTime = strtotime('now');

		$last_time_limit = $currentTime-$ttl;
		
		$cacheTag = $userDependent ? md5($request).'_'.COMODOJO_USER_NAME : md5($request);

		$cacheFile = COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.$cacheTag;
		
		if (is_readable($cacheFile) AND @filemtime($cacheFile) >= $last_time_limit) {
			
			$cache_time = filemtime($cacheFile);
			
			$maxAge = $cache_time + $ttl - $currentTime;
			$bestBefore = gmdate("D, d M Y H:i:s", $cache_time + $ttl) . " GMT";
			
			$data = file_get_contents($cacheFile);
			$u_data = unserialize($data);
			
			if ($u_data === false) {
				comodojo_debug('Error reading from cache file '.$cacheTag,'ERROR','cache');
				if ($this->fail_silently) {
					return false;
				}
				else {
					throw new Exception("Error reading from cache file ".$cacheTag, 1203);
				}
			}
			
			//if (!$decode) return Array($maxAge,$bestBefore,$data);
			//else {
			//	switch(strtoupper($decode)) {
			//		case 'JSON':
			//			$decoded_data = json2array($data);
			//		break;
			//		case 'XML':
			//			$decoded_data = xml2array($data);
			//		break;
			//		case 'YAML':
			//			$decoded_data = yaml2array($data);
			//		break;
			//		default:
			//			comodojo_debug('Unsupported cache decoding '.$decode,'ERROR','cache');
			//			if ($this->fail_silently) {
			//				return false;
			//			}
			//			else {
			//				throw new Exception("Unsupported cache decoding ".$decode, 1204);
			//			}
			//		break;
			//	}
			//	return Array($maxAge,$bestBefore,$decoded_data);
			//}
			
			return Array($maxAge,$bestBefore,$u_data["cache_content"]);

		}
		
		else return false;
		
	}

	/**
	 * Get cache statistics
	 * 
	 * This method will return cache statistics as:
	 * 
	 *  - number of active cache pages
	 *  - number of expired cache pages
	 *  - current time to live
	 *  - oldest cache page
	 *
	 * @return	array
	 */
	public final function get_stats() {
		$currentTime = strtotime('now');
		$last_time_limit = $currentTime-COMODOJO_CACHE_TTL;
		
		$active_cache_files = 0;
		$expired_cache_files = 0;
		$oldest_cache_page = $currentTime;
		
		$cache_path = opendir(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER);    	
		while(false !== ($cache_file = readdir($cache_path))) {
        	if($cache_file != "." AND $cache_file != ".." AND !is_dir(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.$cache_file)) {
        		$file_time = filemtime(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.$cache_file);
        		if ($file_time >= $last_time_limit) {
        			$active_cache_files++;
        		}
				else {
					$expired_cache_files++;
				}
				$oldest_cache_page = $file_time < $oldest_cache_page ? $file_time : $oldest_cache_page;
			}
        }
		closedir($cache_path);
		
		return Array(
			'active_pages'	=>	$active_cache_files,
			'expired_pages'	=>	$expired_cache_files,
			'cache_ttl'		=>	COMODOJO_CACHE_TTL,
			'oldest_page'	=>	$oldest_cache_page
		);
	}

	/**
	 * Purge cache
	 * 
	 * Clean cache folder; errors are not caught nor thrown.
	 *
	 * @return	bool
	 */
	public function purge_cache() {
		comodojo_debug('Purging cache content','INFO','cache');
		$cache_files_number = 0;
    	$cache_path = opendir(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER);
		while(false !== ($cache_file = readdir($cache_path))) {
        	if($cache_file != "." AND $cache_file != ".." AND !is_dir(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.$cache_file)) {
        		if(!unlink(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_CACHE_FOLDER.$cache_file)) return false;
				$cache_files_number++;
			}
        }
		closedir($cache_path);
		comodojo_debug('Cache files deleted: '.$cache_files_number,'INFO','cache');
		return true;
    }
	
}

/**
 * Sanity check for CoMoDojo loader
 * 
 * @define function loadHelper_browser_cache
 */
function loadHelper_cache() { return false; }

?>