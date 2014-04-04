<?php

/**
 * Change User's personal informations
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

class userprofile extends application {
	
	public function init() {
		$this->add_application_method('getUserInfo', 'get_user_info', Array(), 'Display information about logged-in user; no extra parameter required.',false);
		$this->add_application_method('setUserInfo', 'set_user_info', Array("email"), 'Save information about logged-id user. Require at least {email}',false);
		$this->add_application_method('setUserImage', 'set_user_image', Array("image"), 'Set user image. Required parameter {image} should represent a valid image file (w relative path)',false);
	}

	public function get_user_info($params) {
		comodojo_load_resource("users_management");

		try {
			$u = new users_management();
			$result = $u->get_user_extensive(COMODOJO_USER_NAME,64);
		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function set_user_info($params) {
		comodojo_load_resource("users_management");

		try {
			$u = new users_management();
			$result = $u->edit_user(COMODOJO_USER_NAME,$params);
		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function set_user_image($params) {
		comodojo_load_resource("users_management");

		try {
			$u = new users_management();
			$result = $u->set_user_image(COMODOJO_USER_NAME,$params["image"]);
		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}
	
}

?>
