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

	/**
	 * Adres remote wbijany dla wywolania metoda CLI
	 * @var string
	 */
	private $remoteAddress = '127.0.0.1';

	/**
	 * Konstruktor
	 */
	public function __construct() {
		// Ustawiam Remote_addr w przypadku gdy uruchamiam skrypt z konsoli
		putenv("REMOTE_ADDR=$this->remoteAddress");
		$_SERVER['HTTP_HOST'] = exec('hostname');
		$_SERVER['SERVER_ADDR'] = 'localhost';
		$_SERVER['REMOTE_ADDR'] = $this->remoteAddress;
		$_SERVER['HTTP_USER_AGENT'] = 'madkom_console';
		$_SERVER['REQUEST_URI'] = 'localhost';
	}

	/**
	 * Wyświetlenie na ekranie konsoli wiersza KEY="VALUE"
	 *
	 * @param array  $arr
	 * @param string $headerText
	 * @param string $footerText
	 */
	private function output(array $arr = array(), $headerText = '', $footerText = '') {
		if(!empty($headerText)) {
			echo $headerText . PHP_EOL;
		}
		foreach($arr as $k=>$v) {
			echo $k . '="' . $v . '"' . PHP_EOL;
		}
		if(!empty($footerText)) {
			echo $footerText . PHP_EOL;
		}
	}

    /**
     * Zwraca najważniejsze informacje dotyczace aplikacji (DLA Admina)
     *     php index.php -mapplogs
     * @param array $argv
     */
	public function applogs(array $argv) {
		$debug = (isset($argv[0]) && $argv[0] == 'true');
		$logs = new MK_Logs(APP_PATH, $debug);
		exit($logs->sendPackage() ? 'true' : 'false');
	}

    /**
     * Zwraca najważniejsze informacje dotyczace aplikacji (DLA Admina)
     *     php index.php -mappinfo
	 * Szczegółowy raport z dodatkowymi informacjami
     *     php index.php -mappinfo true
     * @param array $argv
     */
	public function appinfo(array $argv) {
		$db = new MK_Db_PDO();
		$this->output(array(
			'APP' => strtolower(APP_NAME),
			'DATABASE' => DB_NAME,
			'PASS' => DB_PASS,
			'USER' => DB_USER,
			'DBHOST' => DB_HOST,
			'PORT' => DB_PORT,
			'VERSION' => $db->getAppVersion()
		));
		if(isset($argv[0]) && $argv[0] == 'true') {
			$this->output(array(
				'RELEASED' => $db->getReleasedVersion(),
				'MK_DEBUG' => (int) MK_DEBUG,
				'MK_DEVELOPER' => (int) MK_DEVELOPER,
				'MK_TEST' => (int) MK_TEST,
				'MK_ERROR_JS_ENABLED' => (int) MK_ERROR_JS_ENABLED,
			), PHP_EOL . '### DEVELOPER ###');
			$this->output(array(
				'SESSION_SAVE_HANDLER' => SESSION_SAVE_HANDLER,
				'MK_DIR_SESSION' => MK_DIR_SESSION
			), PHP_EOL . '### SESSION ###');
			$this->output(array(
				'APP_FILE_LOCK' => APP_FILE_LOCK,
				'APP_STATUS_LOG' => APP_STATUS_LOG,
				'MTM_FILE_LIST' => MTM_FILE_LIST,
				'MTM_FILE_LOG' => MTM_FILE_LOG,
				'MTM_FILE_LOCK' => MTM_FILE_LOCK
			), PHP_EOL . '### MTM ###');
		}
		exit;
	}

}