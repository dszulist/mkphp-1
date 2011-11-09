<?php

/**
 * MK_Controller_Console
 *
 * Klasa do obsługi wywoływania aplikacji z lini polecen (CLI)
 *
 * @category	MK_Controller
 * @package		MK_Controller_Console
 * @author		bskrzypkowiak
 */
class MK_Controller_Console {

	// adres remote wbijany dla wywolania metoda CLI
	private $_remoteAddress = '127.0.0.1';

	public function __construct() {
		$this->_setServerVariables();
	}

	/**
	 *  Ustawiam Remote_addr w przypadku gdy uruchamiam skrypt z konsoli
	 */
	private function _setServerVariables() {
		putenv("REMOTE_ADDR=$this->_remoteAddress");
		$_SERVER['SERVER_ADDR'] = 'localhost';
		$_SERVER['REMOTE_ADDR'] = $this->_remoteAddress;
		$_SERVER['HTTP_USER_AGENT'] = 'madkom_console';
		$_SERVER['REQUEST_URI'] = 'localhost';
	}

	/**
	 * Zwraca najważniejsze informacje dotyczace aplikacji (DLA Admina)
	 *
	 * 	php index.php -mappinfo
	 */
	public function appinfo($die=true) {
		echo "APP=" . APP_NAME . PHP_EOL;
		echo "DATABASE=" . DB_NAME . PHP_EOL;
		echo "PASS=" . DB_PASS . PHP_EOL;
		echo "USER=" . DB_USER . PHP_EOL;
		echo "DBHOST=" . DB_HOST . PHP_EOL;
		echo "PORT=" . DB_PORT . PHP_EOL;
		$db = new MK_Db_PDO();
		echo "VERSION=" . $db->GetOne('SELECT get_app_version()') . PHP_EOL;
		if ($die) {
			die();
		}
	}

}