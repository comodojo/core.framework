<?php

/**
 * image_tools.php
 * 
 * Simple image manipulation
 * 
 * @package		Comodojo PHP Backend
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class image_tools {
	
/*********************** PUBLIC VARS *********************/
	public $failSilently = true;
	public $jpegQuality = 75;
	public $pngQuality = 6;
/*********************** PUBLIC VARS *********************/

/********************** PRIVATE VARS *********************/
	private $old_width = false;
	private $old_height = false;
	private $new_width = false;
	private $new_height = false;
	
	private $source_image = false;
	private $image_type = false;
	
	private $work_image = false;
	
	private $percentage = false;
	
	private $preserveAspectRatio = true;

/********************** PRIVATE VARS *********************/
	
/********************** PUBLIC METHODS *******************/
	
	public final function __construct() {
		
		$gd = function_exists("gd_info");
		$exif = function_exists("exif_read_data");
		
		if (!$gd AND !$exif) {
			comodojo_debug("No GD or EXIF extensions; image tools not available",'ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("No GD or EXIF extensions; image tools not available", 1801);
		}
		
		if (!$gd) comodojo_debug("No GD extension; image tools partially available",'WARNING','image_tools');
		elseif (!$exif) comodojo_debug("No GD extension; image tools partially available",'WARNING','image_tools');
		else comodojo_debug("Image tools init",'INFO','image_tools');
		
	}

	public final function __destruct() {
		$this->close_image();
		comodojo_debug("Image tools closing",'INFO','image_tools');
	}

	/**
	 * Resize image to $width, $height
	 * 
	 * @param	string	$image			Image to process, full path and name
	 * @param	integer	$percentage		The scaling percentage
	 * @param	string	$destination	[optional] The destination image (in case of scaled copy); if omitted, image will be overwritten
	 * 
	 * @return	bool
	 */
	public function resize($image, $width, $height, $destinaton=false) {
			
		if (!$image) {
			comodojo_debug('Invalid image reference','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image reference", 1803);
			return false;
		}	
		
		if (!is_int($width) AND !is_int($height)) {
			comodojo_debug('Invalid image dimensions','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image dimensions", 1805);
			return false;
		}
		
		if (!$this->open_image($image)) {
			comodojo_debug('Invalid image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image", 1804);
			return false;
		}
		
		$this->new_width = $width;
		$this->new_height = $height;
		
		if (!$this->compute_dimensions()) {
			comodojo_debug('Invalid image dimensions','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image dimensions", 1805);
			return false;
		}
		
		$this->work_image = ImageCreateTrueColor($this->new_width,$this->new_height);
		if ($this->image_type == 'PNG' OR $this->image_type == 'GIF') {
			imagealphablending($this->work_image, false);
			imagesavealpha($this->work_image, true);
			$transparent = imagecolorallocatealpha($this->work_image, 255, 255, 255, 127);
			imagefilledrectangle($this->work_image, 0,  0, $this->new_width, $this->new_height,  $transparent);
		}
		
		if (!imagecopyresampled($this->work_image,$this->source_image,0,0,0,0,$this->new_width,$this->new_height,$this->old_width,$this->old_height)) {
			comodojo_debug('Cannot resample image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Cannot resample image", 1806);
			return false;
		}
		
		if (!$this->save_image(!$destinaton ? $image : $destinaton)) {
			comodojo_debug('Cannot save image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Cannot save image", 1807);
			return false;
		}
		
		return true;
		
	}
	
	/**
	 * Scale image of $percentage
	 * 
	 * @param	string	$image			Image to process, full path and name
	 * @param	integer	$percentage		The scaling percentage
	 * @param	string	$destination	[optional] The destination image (in case of scaled copy); if omitted, image will be overwritten
	 * 
	 * @return	bool
	 */
	public function scale($image, $percentage, $destinaton=false) {
		
		if (!$image) {
			comodojo_debug('Invalid image reference','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image reference", 1803);
			return false;
		}	
		
		if (!is_int($percentage)) {
			comodojo_debug('Invalid percentage','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid percentage", 1808);
			return false;
		}
		
		if (!$this->open_image($image)) {
			comodojo_debug('Invalid image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image", 1804);
			return false;
		}
		
		$this->percentage = $percentage;
		
		if (!$this->compute_dimensions()) {
			comodojo_debug('Invalid image dimensions','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image dimensions", 1805);
			return false;
		}
		
		$this->work_image = ImageCreateTrueColor($this->new_width,$this->new_height);
		if ($this->image_type == 'PNG' OR $this->image_type == 'GIF') {
			imagealphablending($this->work_image, false);
			imagesavealpha($this->work_image, true);
			$transparent = imagecolorallocatealpha($this->work_image, 255, 255, 255, 127);
			imagefilledrectangle($this->work_image, 0,  0, $this->new_width, $this->new_height,  $transparent);
		}
		
		if (!imagecopyresampled($this->work_image,$this->source_image,0,0,0,0,$this->new_width,$this->new_height,$this->old_width,$this->old_height)) {
			comodojo_debug('Cannot resample image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Cannot resample image", 1806);
			return false;
		}
		
		if (!$this->save_image(!$destinaton ? $image : $destinaton)) {
			comodojo_debug('Cannot save image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Cannot save image", 1807);
			return false;
		}
		
		return true;
		
	}
	
	/**
	 * Get image thumb
	 * 
	 * This method also cache thumbs in COMODOJO_THUMBNAILS_FOLDER: if there's no thumb cache for selected
	 * image, it will be generated and linked to filemktime, otherwise it will be used.
	 * 
	 * @param	string	$image		Image to process, full path and name
	 * @param	string	$dimension	[optional] The thumb dimension (default 64)
	 * 
	 * @return	string				The thumb filename WHITHOUT path (i.e. without COMODOJO_THUMBNAILS_FOLDER)
	 */
	public function thumbnail($image, $dimension=64) {
		
		if (!$image) {
			comodojo_debug('Invalid image reference','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image reference", 1803);
			return false;
		}	
		
		if (!$this->open_image($image)) {
			comodojo_debug('Invalid image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image", 1804);
			return false;
		}
		
		$filetime = @filemtime($image);
		
		$image_real_name = preg_split('/[\\/:]+/', $image);
		
		$image_thumb_name = preg_replace('/[\\/:]+/', '_', $image_real_name[count($image_real_name)-1]) . '-' . $filetime . '-' . $dimension . '.' . $this->image_type;
		
		if (is_readable(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_THUMBNAILS_FOLDER.$image_thumb_name)) {
			comodojo_debug('Serving thumb for '.$image.' from cache','INFO','image_tools');
			return $image_thumb_name;
		}
		
		if (imagesx($this->source_image) >= imagesy($this->source_image)) $this->new_width = $dimension;
		else $this->new_height = $dimension;
		
		if (!$this->compute_dimensions()) {
			comodojo_debug('Invalid image dimensions','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image dimensions", 1805);
			return false;
		}
		
		comodojo_debug("Generating thumb for resource: ".$image,'INFO','image_tools');

		$this->work_image = ImageCreateTrueColor($this->new_width,$this->new_height);
		if ($this->image_type == 'PNG' OR $this->image_type == 'GIF') {
			imagealphablending($this->work_image, false);
			imagesavealpha($this->work_image, true);
			$transparent = imagecolorallocatealpha($this->work_image, 255, 255, 255, 127);
			imagefilledrectangle($this->work_image, 0,  0, $this->new_width, $this->new_height,  $transparent);
		}
		
		if (!imagecopyresampled($this->work_image,$this->source_image,0,0,0,0,$this->new_width,$this->new_height,$this->old_width,$this->old_height)) {
			comodojo_debug('Cannot resample image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Cannot resample image", 1806);
			return false;
		}
		
		if (!$this->save_image(COMODOJO_SITE_PATH.COMODOJO_HOME_FOLDER.COMODOJO_THUMBNAILS_FOLDER.$image_thumb_name)) {
			comodojo_debug('Cannot save image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Cannot save image", 1807);
			return false;
		}
		
		return $image_thumb_name;
		
	}
	
	/*
	public function create_from_text($text, $width, $height, $background=false, $clone=false) {
		
	}
	
	public function text_watermark($image, $text, $position='sw') {
		
	}
	
	public function graphic_watermark($image, $graphic, $position='sw') {
		
	}
	*/
	
	/**
	 * Scale image of $percentage
	 * 
	 * @param	string	$image			Image to process, full path and name
	 * @param	integer	$format			The output format, one of PNG, JPEG, GIF
	 * @param	bool	$copy			[optional] If true, preserve source image from being deleted
	 * 
	 * @return	bool
	 */
	public function convert($image, $format, $copy=true) {
		
		if (!$image) {
			comodojo_debug('Invalid image reference','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image reference", 1803);
			return false;
		}	
		
		if (in_array(strtoupper($format),Array('JPEG','PNG','GIF'))) {
			comodojo_debug('Invalid output format','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid output format", 1809);
			return false;
		}
		
		if (!$this->open_image($image)) {
			comodojo_debug('Invalid image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image", 1804);
			return false;
		}
		
		if ($this->image_type == $format) {
			comodojo_debug('Samo input and output format','INFO','image_tools');
			return $image;
		}
		
		$this->image_type = $format;
		
		$path = pathinfo($image);
		$path = $path['dirname'];
		
		$name = basename($image, strrchr($image,'.'));
		
		switch ($this->image_type) {
			case 'JPEG':
				$name .= ".jpg";
			break;
			case 'PNG':
				$name .= ".png";
			break;
			case 'GIF':
				$name .= ".gif";
			break;
		}
		
		$output = $path . '/' . $name;
		
		$this->work_image = $this->source_image;
		
		if (!$this->save_image($name)) {
			comodojo_debug('Cannot save image','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Cannot save image", 1807);
			return false;
		}
		
		return $name;
		
	}
	
	/**
	 * Read exif data from image
	 * 
	 * See http://www.php.net/manual/en/function.exif-read-data.php
	 * 
	 * @param	string	$image		Image to process, full path and name
	 * @param	string	$sections	[optional] The exif section(s) to read, comma separated 
	 * 
	 * @return						Exif data, false if not available, exception on error or filetype mismatch
	 */
	public function readExif($image, $sections=ANY_TAG) {
		
		if (!$image) {
			comodojo_debug('Invalid image reference','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image reference", 1803);
			return false;
		}
		
		$type = exif_imagetype($image);
		
		if ($type != IMAGETYPE_JPEG OR $type != IMAGETYPE_TIFF_II OR $type != IMAGETYPE_TIFF_MM) {
			comodojo_debug('Invalid image type to read exif','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image type to read exif", 1802);
			else return false;
		}
		
		return exif_read_data($image, $sections, true);
		
	}
	
	/**
	 * Return image type in mimetype format.
	 * 
	 * @param	string	$image		Image to process, full path and name
	 * 
	 * @return						MIMETYPE if found, false if not available
	 */
	public final function getImageMimeType($image) {
		
		if (!$image) {
			comodojo_debug('Invalid image reference','ERROR','image_tools');
			if (!$this->failSilently) throw new Exception("Invalid image reference", 1803);
			return false;
		}
		
		if (function_exists('image_type_to_mime_type') AND function_exists('exif_imagetype')) return image_type_to_mime_type(exif_imagetype($image));
		elseif (function_exists("finfo_open")) {
			$comodojo_finfo = finfo_open(FILEINFO_MIME_TYPE);
			return finfo_file($comodojo_finfo, $file);
		}
		elseif (function_exists("mime_content_type")) return mime_content_type($file);
		else return false;
		
	}
/********************** PUBLIC METHODS *******************/

/********************* PRIVATE METHODS *******************/
	/**
	 * Open image and allocate a GD image in $image_tools::source_image
	 * 
	 * @param	string	$image	The image complete path and filename
	 */
	private function open_image($image) {
			
		if (!is_readable($image)) {
			comodojo_debug("Cannot open image: invalid image reference",'ERROR','image_tools');
			return false;
		}

		$image_size = getimagesize($image);
		$mem_usage = $image_size[0] * $image_size[1] * 4; //4 bytes per pixel (RGBA)
		$mem_available = ini_get('memory_limit');

		if (preg_match('/^(\d+)(.)$/', $mem_available, $matches)) {
			if ($matches[2] == 'K') {
				$mem_available = $matches[1] * 1024;
			}
			else if ($matches[2] == 'M') {
				$mem_available = $matches[1] * 1024 * 1024;
			}
			else if ($matches[2] == 'G') {
				$mem_available = $matches[1] * 1024 * 1024 * 1024;
			}
		}

		if ($mem_usage > $mem_available) {
			comodojo_debug("Cannot open image: image is too large - " . $mem_usage . " > " . $mem_available,'ERROR','image_tools');
			return false;
		}
				
		switch ($this->getImageMimeType($image)) {
			case 'image/jpeg':
				$this->source_image = @imagecreatefromjpeg($image);
				$this->image_type = 'JPEG';
			break;
			case 'image/png':
				$this->source_image = @imagecreatefrompng($image);
				$this->image_type = 'PNG';
			break;
			case 'image/gif':
				$this->source_image = @imagecreatefromgif($image);
				$this->image_type = 'GIF';
			break;
			default:
				comodojo_debug('Cannot open image: unsupported image format '.$this->getImageMimeType($image),'ERROR','image_tools');
				return false;
			break;
		}

		if (!$this->source_image) {
			comodojo_debug('Cannot open image: error opening','ERROR','image_tools');
			return false;
		}
		else {
			comodojo_debug('Correctly opened image: '.$image,'INFO','image_tools');
			return true;
		}

	}

	/**
	 * Save image writing GD representation to $fileName
	 * 
	 * @param	string	$fileName	The complete image file, path and filename 
	 */
	private function save_image($fileName) {
		
		switch ($this->image_type) {
			case 'JPEG':
				$toReturn = @imagejpeg($this->work_image, $fileName, $this->jpegQuality);
			break;
			case 'PNG':
				$toReturn = @imagepng($this->work_image, $fileName, $this->pngQuality);
			break;
			case 'GIF':
				$toReturn = @imagegif($this->work_image, $fileName);
			break;
			default:
				comodojo_debug('Cannot save image: unsupported image format '.$this->image_types,'ERROR','image_tools');
				$toReturn = false;
			break;
		}
		
		if (!$toReturn) {
			comodojo_debug('Cannot save image: error saving','ERROR','image_tools');
			return false;
		}
		else {
			comodojo_debug('Correctly saved image: '.$fileName,'INFO','image_tools');
			return true;
		}
		
	}
	
	/**
	 * Close GD representation of images
	 * 
	 * @param	string	$fileName	The complete image file, path and filename 
	 */
	private function close_image() {
		
		@imagedestroy($this->work_image); 
		@imagedestroy($this->source_image);
		
	}

	/**
	 * Compute image size automatically starting from:
	 * - $image_tools::percentage
	 * - $image_tools::new_width / $image_tools::new_height
	 */
	private function compute_dimensions() {
			
		$this->old_width  = imagesx($this->source_image);
		$this->old_height = imagesy($this->source_image);
		
		if (!$this->old_width OR !$this->old_height) {
			comodojo_debug("Invalid source image",'ERROR','image_tools');
			return false;
		}
		
		if (is_integer($this->percentage)) {
			$this->new_width  = $this->old_width  * ($this->percentage/100);
			$this->new_height = $this->old_height * ($this->percentage/100);
			return true;
		}
		elseif (is_int($this->new_width) AND !is_int($this->new_height)) {
			$this->new_height = $this->old_height * ($this->new_width/$this->old_width);
			return true;
		}
		elseif (!is_int($this->new_width) AND is_int($this->new_height)) {
			$this->new_width = $this->old_width * ($this->new_height/$this->old_height);
			return true;
		}
		else {
			comodojo_debug("Cannot compute dimensions",'ERROR','image_tools');
			return false;
		}
		
	}
/********************* PRIVATE METHODS *******************/
	
}

function loadHelper_image_tools() { return false; }
	
?>