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

class uploader extends application {
	
	public function init() {
		$this->add_application_method('get_max_filesize', 'getMaxFilesize', Array(), 'Get server max filesize',false);
	}

	public function getMaxFilesize() {
		$max_post = ini_get('post_max_size');
		$max_file = ini_get('upload_max_filesize');
		return Array(
			'max_post' => $max_post,
			'max_file' => $max_file
		);
	}
	
}

?>
