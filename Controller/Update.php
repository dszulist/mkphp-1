<?php

/**
 * MK_Controller_Update
 *
 * Klasa do obsługi aktualizaji
 *
 * @category    MK_Controller
 * @package     MK_Controller_Update
 * @author    bskrzypkowiak
 */
class MK_Controller_Update {

	/**
	 *
	 */
	CONST LOG_TYPE = 0;

	/**
	 * Nazwa aplikacji
	 * @var string
	 */
	private $appName = APP_NAME;

	/**
	 * @var bool
	 */
	private $superAdmin = false;

	/**
	 * RegExp do odczytywania logów z pliku
	 * @var string
	 */
	private $logRegExp = "#(\d+-\d+-\d+ \d+:\d+:\d+) (\w+\.\w+): (.*)#";

	/**
	 * obecna licencja
	 * @var null
	 */
	private $licence = null;

	/**
	 * obecna wersja
	 * @var null
	 */
	private $currentVersion = null;

	/**
	 * dostepne wersje do upgradu
	 * @var null
	 */
	private $allowVersion = null;

	/**
	 * numer najnowszej wersji z rejestru zmian
	 * @var null
	 */
	private $releasedVersion = null;

	/**
	 * lista dostęnych zadań
	 * @var array
	 */
	private $patchTaskList = array(
		'patch' => 'Poprawki',
		'patch_dev' => 'Poprawki niestabilne',
		'patch_rc' => 'Poprawki kandydujące do wersji stabilnej',
		'upgrade' => 'Aktualizacja do wersji '
	);

	/**
	 * @throws MK_Exception
	 */
	public function __construct() {

		$this->preparePatchTaskList();

		//@TODO sprawdzanie czy konto jest z uprawnieniami administratora
		if(!file_exists(MTM_FILE_LIST) || !is_writable(MTM_FILE_LIST)) {
			throw new MK_Exception('Problem z zapisem do pliku.');
		}
	}

	/**
	 * @param $v
	 * @return MK_Controller_Update
	 */
	protected function setAppVersion($v){
		$this->currentVersion = $v;
		return $this;
	}

	/**
	 * @param $v
	 * @return MK_Controller_Update
	 */
	protected function setLicense($v){
		$this->licence = $v;
		return $this;
	}

	/**
	 * @param $v
	 * @return MK_Controller_Update
	 */
	protected function setAllowedVersion($v){
		$this->allowVersion = $v;
		return $this;
	}

	/**
	 * @param $v
	 * @return MK_Controller_Update
	 */
	protected function setReleasedVersion($v){
		$this->releasedVersion = $v;
		return $this;
	}

	/**
	 * @param $v
	 * @return MK_Controller_Update
	 */
	protected function setSuperAdmin($v){
		$this->superAdmin = $v; //UserSingleton::getInstance()->getCurrentUserInstance()->isSuperAdmin();
		return $this;
	}

	/**
	 * @param $v
	 * @return MK_Controller_Update
	 */
	protected function setAppName($v){
		$this->appName = $v;
		return $this;
	}

	/**
	 * Tworzy i zwraca stora do comboboxa z mozliwosciami aktualizacji
	 * W przypadku podania parametru jako true zwróci tablice z opcjami zamiast stora
	 *
	 * @ruleDescription Tworzy i zwraca stora do comboboxa z mozliwosciami aktualizacji
	 * @ruleTextName Tworzy i zwraca stora do comboboxa z mozliwosciami aktualizacji
	 *
	 * @param bool $returnArray
	 *
	 * @internal param \array (default:false) $czy ma zwrócić tablice z kluczami opcji i wartościami opisowymi
	 *
	 * @return Array
	 */
	public function getPatchComboStore($returnArray = false) {
		if($returnArray === true) {
			return $this->patchTaskList;
		}

		$store = array();
		foreach($this->patchTaskList as $key => $val) {
			$store[] = array("name" => $key, "description" => $val);
		}

		return $store;
	}

	/**
	 *  Ustawia dane w tabeli przechowującej możliwości do aktualizacji
	 */
	public function preparePatchTaskList() {
		$this->patchTaskList['upgrade'] .= $this->allowVersion;

		$allowVersion = (int) str_replace('.', '', $this->allowVersion);
		$currentVersion = (int) str_replace('.', '', $this->currentVersion);
		$releasedVersion = (int) str_replace('.', '', $this->releasedVersion);

		if(!($currentVersion < $allowVersion)) {
			unset($this->patchTaskList['upgrade']);
		}

		// przeypadek kiedy jest to niewydana wersja, tzn w APP_NAME_conf jest 0.1.2,
		// a w rejestrze zmian jest 0.1.1
		if($currentVersion == $releasedVersion + 1) {
			// patch_rc
			unset($this->patchTaskList['patch_dev']);
			unset($this->patchTaskList['patch']);
		} else {
			// patch | patch_dev
			unset($this->patchTaskList['patch_rc']);
		}

		if(MK_IS_CLI === false && $this->superAdmin === false) {
			unset($this->patchTaskList['patch_dev']);
			unset($this->patchTaskList['patch_rc']);
		}
	}

	/**
	 * Uruchamia aktualizacje
	 *
	 * @param array $args
	 *
	 * @throws MK_Exception
	 * @return array
	 */
	protected function run(array $args) {
		if(!array_key_exists('type', $args) || empty($args['type'])){
			throw new MK_Exception('Nie podano typu aktualizacji');
		}

		if(!array_key_exists($args['type'], $this->patchTaskList)) {
			throw new MK_Exception('Nie można wykonać żądanej czynności.');
		}
		$phpVersion = floatval(phpversion());

		//@TODO checkupgrade $licence = new SpirbLicence(); $licence->checkUpgrade(); - to trzeba uzupełnić o tą funkjonalność  "checkUpgrade"

		$fh = fopen(MTM_FILE_LIST, 'a');

		$endVersion = $startVersion = str_replace('.', '_', $this->currentVersion);

		$stringData = "apply_madkom_pack {$this->licence} {$this->appName} {$startVersion} {$endVersion} ";
		$msg = 'Uruchomiono mechanizm ';

		switch($args['type']) {
			case 'patch':
				$stringData .= 'stable ' . APP_PATH;
				$msg .= 'wgrywania poprawek stabilnych';
				break;
			case 'patch_rc':
				$stringData .= 'rc_' . date('YmdHis') . ' ' . APP_PATH;
				$msg .= 'wgrywania poprawek kandydujących na stabilne';
				break;
			case 'patch_dev':
				$stringData .= date('YmdHis') . ' ' . APP_PATH;
				$msg .= 'wgrywania poprawek niestabilnych';
				break;
			case 'upgrade':
				$endVersion = str_replace('.', '_', $this->allowVersion);
				$stringData = "apply_madkom_pack {$this->licence} {$this->appName} {$startVersion} {$endVersion} " . APP_PATH;
				$msg .= "aktualizaji do nowej wersji: {$endVersion}";
				break;
		}
		fwrite($fh, "{$stringData} {$phpVersion} \n");
		fclose($fh);

		//@TODO dodawanie do logów : TableLogs::addLogDeprecated(self::LOG_TYPE, 'updateApplication', array( 'type' => $args['type'], 'msg' => $msg ));

		return array(
			"type" => $args['type'],
			"message" => $msg
		);
	}

	/**
	 * Pobiera informacje z pliku do którego dodawane są dane dotyczące bieżącej aktualizacji
	 * @return Array
	 */
	public function getProgress() {
		sleep(5);
		return $this->readProgressFile();
	}

	/**
	 * Odczytuje plik i zwraca wynik w postaci tablicy
	 * @return Array
	 */
	public function readProgressFile() {
		$rows = array();
		if(file_exists(APP_STATUS_LOG)) {
			preg_match_all($this->logRegExp, file_get_contents(APP_STATUS_LOG), $row);
			if(isset($row[3]) && isset($row[3][0])) {
				$lastKey = count($row[3]) - 1;
				for($i = 0; $i <= $lastKey; $i++) {
					if(strstr($row[3][$i], 'DEBUG: ') !== false) {
						continue;
					}
					$rows[] = array(
						'date' => $row[1][$i],
						'description' => $row[3][$i]
					);
				}
			}
		}
		else {
			$rows[] = array(
				'date' => date('Y-m-d H:i:s'),
				'description' => 'W chwili obecnej nie jest wykonywana żadna aktualizacja'
			);
		}
		return $rows;
	}

	/**
	 * Odczytuje plik i zwraca wynik w postaci tablicy
	 *
	 * Pobiera liste informacji z pliku z logami od aktualizacji
	 * zwraca ostatnie 20 logów
	 *
	 * @return array
	 */
	public function getHistory() {
		$maxRecords = 20;
		$rows = array();
		if(is_dir(MK_DIR_UPDATE_LOGS)) {
			$files = scandir(MK_DIR_UPDATE_LOGS);
			rsort($files);
			foreach($files as $file) {
				preg_match_all($this->logRegExp, file_get_contents(MK_DIR_UPDATE_LOGS . DIRECTORY_SEPARATOR . $file), $row);
				if(!isset($row[3]) || !isset($row[3][0])) {
					continue;
				}
				$lastKey = count($row[3]) - 1;
				$rows[] = array(
					'date' => $row[1][$lastKey],
					'description' => $row[3][$lastKey]
				);
				if(--$maxRecords <= 0) {
					break;
				}
			}
		}

		return $rows;
	}

}