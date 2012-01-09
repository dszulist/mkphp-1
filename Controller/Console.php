<?php

/**
 * MK_Controller_Console
 *
 * Klasa do obsługi wywoływania aplikacji z lini polecen (CLI)
 *
 * @category	MK_Controller
 * @package     MK_Controller_Console
 * @author	bskrzypkowiak
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
		$_SERVER['HTTP_HOST'] = exec('hostname');
		$_SERVER['SERVER_ADDR'] = 'localhost';
		$_SERVER['REMOTE_ADDR'] = $this->_remoteAddress;
		$_SERVER['HTTP_USER_AGENT'] = 'madkom_console';
		$_SERVER['REQUEST_URI'] = 'localhost';
	}

    /**
     * Zwraca najważniejsze informacje dotyczace aplikacji (DLA Admina)
     *
     *     php index.php -mappinfo
     * @param array $argv
     */
	public function appinfo(array $argv) {
		echo "APP=" . strtolower(APP_NAME) . PHP_EOL;
		echo "DATABASE=" . DB_NAME . PHP_EOL;
		echo "PASS=" . DB_PASS . PHP_EOL;
		echo "USER=" . DB_USER . PHP_EOL;
		echo "DBHOST=" . DB_HOST . PHP_EOL;
		echo "PORT=" . DB_PORT . PHP_EOL;
		$db = new MK_Db_PDO();
		echo "VERSION=" . $db->getAppVersion() . PHP_EOL;
		exit();
	}

    /**
     * Zwraca najważniejsze informacje dotyczace aplikacji (DLA Admina)
     *
     *     php index.php -mapplogs
     * @param array $argv
     */
	public function applogs(array $argv) {
		$debug = (isset($argv[0]) && $argv[0] == 'true') ? true : false;
		$logs = new MK_Logs(APP_PATH, $debug);
		exit($logs->sendPackage() ? 'true' : 'false');
	}

}