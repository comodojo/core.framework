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

class userprofile extends application {
	
	public function init() {
		$this->add_application_method('get_user_info', 'getUserInfo', Array(), 'No description yet, sorry.',false);
		$this->add_application_method('set_user_info', 'setUserInfo', Array("email"), 'No description yet, sorry.',false);
		$this->add_application_method('set_user_image', 'setUserImage', Array("image"), 'No description yet, sorry.',false);
	}

	public function getUserInfo($params) {
		comodojo_load_resource("users_management");

		try {
			$u = new users_management();
			$result = $u->get_user_extensive(COMODOJO_USER_NAME,64);
		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function setUserInfo($params) {
		comodojo_load_resource("users_management");

		try {
			$u = new users_management();
			$result = $u->edit_user(COMODOJO_USER_NAME,$params);
		} catch (Exception $e) {
			throw $e;
		}

		return $result;

	}

	public function setUserImage($params) {
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
