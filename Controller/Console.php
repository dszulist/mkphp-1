<?php

/**
 * MK_Controller_Console
 *
 * Klasa do obsługi wywoływania aplikacji z lini polecen (CLI)
 *
 * @category    MK_Controller
 * @package     MK_Controller_Console
 * @author    bskrzypkowiak
 */
class MK_Controller_Console {

	/**
	 * Adres remote wbijany dla wywolania metoda CLI
	 * @var string
	 */
	protected $remoteAddress = '127.0.0.1';

	/**
	 * Czy włączyć debugowanie wykonywanych metod?
	 * @var bool
	 */
	private $debug = false;

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
	 * Ustawienie debugowania true/false
	 *
	 * @param bool $debug
	 *
	 * @return \MK_Controller_Console
	 */
	protected function setDebug($debug) {
		$this->debug = (bool) $debug;
		return $this;
	}

	/**
	 * Debugowanie wykonywanego kodu z crona
	 *
	 * @param string $msg
	 */
	protected function debug($msg) {
		if($this->debug) {
			echo $msg . MK_EOL;
		}
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
			echo $headerText . MK_EOL;
		}
		foreach($arr as $k => $v) {
			echo $k . '="' . $v . '"' . MK_EOL;
		}
		if(!empty($footerText)) {
			echo $footerText . MK_EOL;
		}
	}

	/**
	 * Zwraca najważniejsze informacje dotyczace aplikacji (DLA Admina)
	 *     php index.php -mapplogs
	 *
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
	 *
	 * @param array $argv
	 */
	public function appinfo(array $argv) {
		$db = new MK_Db_PDO();
		// Podstawowe informacje APPINFO
		$this->output(array(
			'APP' => strtolower(APP_NAME),
			'DATABASE' => DB_NAME,
			'PASS' => DB_PASS,
			'USER' => DB_USER,
			'DBHOST' => DB_HOST,
			'PORT' => DB_PORT,
			'VERSION' => $db->getAppVersion()
		));
		// Dodatkowe informacje po wpisaniu parametru 'true'
		if(isset($argv[0]) && $argv[0] == 'true') {
			// ### DEVELOPER ###
			$this->output(array(
				'RELEASED' => $db->getReleasedVersion(),
				'MK_DEBUG' => (int) MK_DEBUG,
				'MK_DEVELOPER' => (int) MK_DEVELOPER,
				'MK_TEST' => (int) MK_TEST,
				'MK_ERROR_JS_ENABLED' => (int) MK_ERROR_JS_ENABLED,
			), MK_EOL . '### DEVELOPER ###');

			// ### SESSION ###
			$this->output(array(
				'SESSION_SAVE_HANDLER' => SESSION_SAVE_HANDLER,
				'MK_DIR_SESSION' => MK_DIR_SESSION
			), MK_EOL . '### SESSION ###');

			// ### MTM ###
			$this->output(array(
				'APP_FILE_LOCK' => APP_FILE_LOCK,
				'APP_STATUS_LOG' => APP_STATUS_LOG,
				'MTM_FILE_LIST' => MTM_FILE_LIST,
				'MTM_FILE_LOG' => MTM_FILE_LOG,
				'MTM_FILE_LOCK' => MTM_FILE_LOCK
			), MK_EOL . '### MTM ###');

			// ### LAST 3 UPGRADE COMPLETED TASK ###
			$this->output($this->prepareCompletedTasks($db->getCompletedTask(3), 'UPGRADE_COMPLETED_TASK_'), MK_EOL . '### LAST 3 UPGRADE COMPLETED TASK ###');

			// ### LAST 5 UPGRADE LOGS ###
			$this->output(MK_Upgrade_Logs::getInfo(5, 'LAST_UPGRADE_LOGS_'), MK_EOL . '### LAST 5 UPGRADE LOGS ###');
		}
		exit;
	}

	/**
	 * Uruchamia aktualizacje (dodaje zadanie do kolejki)
	 *     php index.php -mupdate [arg]
	 *
	 * @param array                 $argv - parametry przekazywane w wywołaniu
	 * @param \MK_Controller_Update $update
	 */
	public function execUpdate(array $argv, MK_Controller_Update $update) {
		$type = isset($argv[0]) ? $argv[0] : null;
		$force = isset($argv[1]) && $argv[1] == 'true';

		$optionList = $update->getPatchTaskList();

		if(!isset($optionList[$type]) && !$force) {
			echo MK_EOL,
				"Nieprawidłowy typ aktualizacji: '$type'" . MK_EOL,
			MK_EOL,
				'Wywołanie: php index.php -mupdate [arg]' . MK_EOL,
				' Dostępne argumenty to: ' . MK_EOL;
			foreach($optionList as $k => $v) {
				echo "\t$k - $v" . MK_EOL;
			}
			echo MK_EOL . MK_EOL;
			die;
		} else {
			$update->run(array('type' => $type, 'force' => $force));

			echo "Pomyślnie dodano zadanie do kolejki." . MK_EOL,
				"Aby widzieć postęp wykonaj poniższą komende:" . MK_EOL,
				"\ttail -f " . MTM_FILE_LOCK . MK_EOL,
				"Szczegółowy podgląd zdarzeń (MTM):" . MK_EOL,
				"\ttail -n 25 -f " . MTM_FILE_LOG . MK_EOL,
				"Szczegółowy podgląd zdarzeń (aplikacja):" . MK_EOL,
				"\ttail -n 25 -f " . APP_STATUS_LOG . MK_EOL;
			die;
		}
	}

	/**
	 * Wysyła statystyki z systemu przez Brokera do GO
	 *
	 * @param array $argv - tablica z danymi statystycznymi oraz danymi do połączenia z Brokerem
	 *    Wymagane pola w tablicy:
	 *          broker_login: login do atoryzacji z Brokerem
	 *            broker_password: hasło do autoryzacji z Brokerem
	 *            broker_wsdl: adres wsdl Brokera
	 *            app_version: wersja aplikacji
	 *            released_version: aktualna wersja "stabilna"
	 *            count_actual_users: suma aktywnych użytkowników w systemie
	 *            count_tables_row: lista tabel i suma rekordów w każdej z nich
	 *            completed_tasks:
	 *
	 * @throws Exception
	 */
	protected function sendStats(array $argv) {

		$required = array(
			'broker_login',
			'broker_password',
			'broker_wsdl',
			'patch_version',
			'released_version',
			'count_actual_users',
			'count_tables_row',
			'completed_tasks'
		);

		// sprawdzamy czy przekazano wszystkie potrzebne informacje
		foreach($required as $val) {
			if(!isset($argv[$val])) {
				throw new Exception("Brak wymaganych danych: $val");
			}
		}

		// Nawiązanie połączenia do Brokera i przygotowanie obietków
		$this->debug('Nawiązywanie połączenia z Brokerem...');
		$brokerClient = new MK_Broker_Client($argv['broker_login'], $argv['broker_password'], $argv['broker_wsdl']);
		$brokerClient->connect();
		$sendClientSystemInformations = new sendClientSystemInformations(APP_NAME);

		// patch_version
		$this->debug('Odczytywanie informacji o aktualnej wersji aplikacji...');
		$sendClientSystemInformations->add('patch_version', $argv['patch_version']);

		// released_version
		$this->debug('Odczytywanie informacji o wydanej wersji aplikacji...');
		$sendClientSystemInformations->add('released_version', $argv['released_version']);

		// db_name
		$this->debug('Odczytywanie informacji o nazwie bazy danych...');
		$sendClientSystemInformations->add('db_name', DB_NAME);

		// count_actual_users
		$this->debug('Odczytywanie informacji o ilości aktywnych użytkowników...');
		$sendClientSystemInformations->add('count_actual_users', $argv['count_actual_users']);

		// count_tables_row
		$this->debug('Odczytywanie informacji o ilości wierszy w tabelach...');
		$sendClientSystemInformations->add('count_tables_row', $argv['count_tables_row']);

		// upgrade_completed
		$this->debug('Odczytywanie informacji o 5 ostatnio wykonanych parserach/sqlach...');
		$sendClientSystemInformations->add('upgrade_completed', $this->prepareCompletedTasks($argv['completed_tasks']));

		// patch_createdate
		$this->debug('Odczytywanie informacji o dacie ostatnio uruchomionego parsera/sqla...');
		$sendClientSystemInformations->add('patch_createdate', isset($argv['completed_tasks'][0]) && isset($argv['completed_tasks'][0]['createdate']) ? $argv['completed_tasks'][0]['createdate'] : null);

		// upgrade_logs
		$this->debug('Odczytywanie informacji o 5 ostatnio wykonanych aktualizacjach...');
		$sendClientSystemInformations->add('upgrade_logs', MK_Upgrade_Logs::getInfo());

		// Wysyłanie statystyk do Brokera
		$this->debug('Trwa wysyłanie statystyk do Brokera...');
		/** @noinspection PhpUndefinedMethodInspection */
		$brokerClient->sendClientSystemInformations($sendClientSystemInformations);
		$brokerClient->disconnect();
		$this->debug('Statystyki wysłane do Brokera');
	}

	/**
	 * Funkcja pomocnicza układająca dane na temat ostatnio wykonanych parserów/sqlów
	 *
	 * @param array  $rows
	 * @param string $prefix
	 *
	 * @return array
	 */
	private function prepareCompletedTasks(array $rows, $prefix = '') {
		$completedTaskArray = array();
		foreach($rows as $row) {
			$completedTaskArray["{$prefix}{$row['id']}_APP_VERSION"] = $row['app_version'];
			$completedTaskArray["{$prefix}{$row['id']}_CREATEDATE"] = $row['createdate'];
			$completedTaskArray["{$prefix}{$row['id']}_PATCH_NAME"] = $row['patch_name'];
		}

		return $completedTaskArray;
	}


}