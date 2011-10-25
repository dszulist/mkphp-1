<?php

/**
 * MK_Controller_Developer
 *
 * Klasa do obsługi opcji developerskich
 *
 * @category	MK_Controller
 * @package		MK_Controller_Developer
 * @author		bskrzypkowiak
 */
class MK_Controller_Developer {

	/**
	 * Włączaja/Wyłącza debbuging firephp oraz xdebug
	 * 
	 * @return Array
	 */
	public function enableDebug() {
		$msg = '';
		if (array_key_exists('APP_DEBUG', $_SESSION) && $_SESSION['APP_DEBUG'] === true) {
			unset($_SESSION['APP_DEBUG']);
			setcookie('XDEBUG_SESSION', '', time() - 86400, COOKIES_PATH);
			$msg = 'Wyłączono';
		} else {
			$_SESSION['APP_DEBUG'] = true;
			setcookie('XDEBUG_SESSION', 'netbeans-xdebug', 0, COOKIES_PATH);
			$msg = 'Włączono';
		}

		return array(
			"success" => true,
			"message" => $msg . ' debugowanie'
		);
	}

}

