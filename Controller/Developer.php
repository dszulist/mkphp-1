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
			MK_Cookie::clear('XDEBUG_SESSION');
			$msg = 'Wyłączono';
		} else {
			$_SESSION['APP_DEBUG'] = true;
			MK_Cookie::set('XDEBUG_SESSION', 'netbeans-xdebug', 0);
			$msg = 'Włączono';
		}

		return array(
			"success" => true,
			"message" => $msg . ' debugowanie'
		);
	}

}

