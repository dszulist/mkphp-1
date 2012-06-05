<?php

/**
 * MK_Upgrade
 *
 * Klasa posiada metody do wykonywania aktualizacji
 *
 * Kroki postępowania
 *  1. podmiana kodu na kod do wersji w ktorej sie aktualizuje
 *  2. ustawienie uprawnien
 *  3. ustawienie configa dla aplikacji
 *  1. przenosimy ten plik do katalogu głównego aplikacji
 *  2. pozostałem katalogi z plikami przenosimy do katalogu upgrade/source aplikacji
 *
 * @category MK
 * @package    MK_Upgrade
 */
Class MK_Upgrade extends MK_Db_PDO
{

	/**
	 * Zmienna do okreslania czy ma byc uruchamiane patchowanie jesli false to jest to upgrade
	 * @var bool
	 */
	private $patch = false;

	/**
	 * Sprawdzenie czy jest developerem
	 * @var bool
	 */
	public $isDeveloper = false;

	/**
	 * Ignorowanie folderów/nazw
	 * @var bool
	 */
	public $ignoreDir = array('.', '..', '.svn', '.idea');

	/**
	 * Konstruktor
	 */
	public function __construct()
	{
		$this->isDeveloper = (defined('MK_DEVELOPER') && MK_DEVELOPER == true) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'developer');
		try {
			self::setUpgradeBeginTime();
			$this->begin();
		} catch (Exception $e) {
			self::writeToLog('UPGRADE PRZERWANY!!!');
			self::writeToLog("{$e->getMessage()}, Kod: {$e->getCode()}, Plik: {$e->getFile()}, Linia: {$e->getLine()}");
			self::writeToLog('Cofanie transakcji sql');
			$this->transFail();
			// ustawienie stanu aplikacji na działającą
			self::writeToLog('Ustawianie aplikacji w stan: running');
			$this->changeApplicationState('running');
			self::writeToLog('Przywracanie backupu');
			$this->restoreBackup();
			exit('false');
		}
	}

	/**
	 * Uruchamia proces aktualizacji
	 */
	public function begin()
	{
		// ustawienie glownego katalogu do upgradu
		$this->setUpgradeFolder(APP_PATH . DIRECTORY_SEPARATOR . 'upgrade');
		// ustawienie katalogu do zapisywania logow
		$this->setUpgradeLogFolder(APP_PATH . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . 'log');
		// ustawienie katalogu do zapisywania backupu
		$this->setUpgradeBackupFolder(APP_PATH . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . MK_Registry::get("upgradeBeginTime"));
		// ustawienie katalogu do plikow sql i parserow
		$this->setUpgradeSourceFolder(APP_PATH . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . 'source');

		self::writeToLog('URUCHAMIANIE PROCESU AKTUALIZACJI');
		self::writeToLog('Ustanowienie połączenia do bazy danych');
		parent::__construct();
		self::writeToLog('Rozpoczynanie transakcji sql (BEGIN)');
		$this->transStart();

		// ustawienie stanu aplikacji na upgrade
		self::writeToLog('Ustawianie aplikacji w stan: upgrade');
		$this->changeApplicationState('upgrade');

		// sprawdzenie licencji
		$this->checkLicence();

		// czyszczenie sessji użytkowników
		self::writeToLog('Czyszczenie sesji użytkowników');
		$this->clearUserSessions();

		self::writeToLog('UPGRADE ROZPOCZĘTY');
		$this->proceed();

		$this->clearDbComments();

		self::writeToLog('Zamykanie transakcji sql (COMMIT)');
		$this->transComplete();

		// ustawienie stanu aplikacji na działającą
		self::writeToLog('Ustawianie aplikacji w stan: running');
		$this->changeApplicationState('running');

		self::writeToLog('UPGRADE ZAKOŃCZONY');
		die("true");
	}

	/**
	 * Ustawienie stanu aplikacji
	 *
	 * @param string state ('upgrade', 'running')
	 */
	private function changeApplicationState($state)
	{
		switch($state) {
			case 'running':
				removeDir(APP_FILE_LOCK);
				break;
			case 'upgrade':
				removeDir(APP_FILE_LOCK);
				file_put_contents(APP_FILE_LOCK, 'upgrade');
				break;
		}
	}

	/**
	 * Czyszczenie sesji użytkowników
	 */
	private function clearUserSessions()
	{
		session_destroy();
		removeDir(MK_DIR_SESSION);
		@mkdir(MK_DIR_SESSION, MK_CHMOD_DIR, true);
	}

	/**
	 * Usunięcie komentarzy z kolumn i tabel
	 * @throws Exception
	 * @return
	 */
	private function clearDbComments() {
		if($this->isDeveloper === true) {
			self::writeToLog('Pomijanie usuwania komentarzy (developer)');
			self::writeToLog('Nadanie uprawnień dla schematu, tabeli/widoku, sekwencji oraz funkcji/triggera (user:spirb)');
			$this->Execute("SELECT grant_user_all('spirb', 'public'), grant_user_all('spirb', 'public_logs');");
			return;
		}
		self::writeToLog('Usuwanie komentarzy w bazie danych');

		$metaTables = $this->MetaTables('TABLES', true);
		foreach($metaTables as $table) {
			$this->Execute("COMMENT ON TABLE {$table} IS 'brak'");
			$metaColumns = $this->MetaColumnNames($table, true, true);
			foreach($metaColumns as $columnName) {
				$this->Execute("COMMENT ON COLUMN {$columnName} IS 'brak'");
			}
		}
	}

	/**
	 * Backup pojedynczego pliku
	 *
	 * @param $file
	 *
	 * @throws Exception
	 * @return bool
	 */
	public static function backupFile($file)
	{
		$debugBacktrace = debug_backtrace();
		$debugBacktrace = array_shift($debugBacktrace);
		$backtrace = $debugBacktrace['file'] . ": " . $debugBacktrace['line'] . ": " . $debugBacktrace['class'] . " " . $debugBacktrace['type'] . $debugBacktrace['function'];

		if (!file_exists($file)) {
			self::writeToLog("BACKUP PLIKU: {$file} PLIK NIE ISTNIEJE");
			return true;
		}

		$path = explode(DIRECTORY_SEPARATOR, $file);
		// nazwa pliku
		$file_name = array_pop($path);

		//wzgledna sciezka do pliku oryginalnego
		$path = implode(DIRECTORY_SEPARATOR, $path);
		self::writeToLog("BACKUP PLIKU: {$file} OK {$backtrace}");
		$dstToBackup = MK_Registry::get("upgradeBackupFolder") . DIRECTORY_SEPARATOR . $path;

		if (!file_exists($dstToBackup . DIRECTORY_SEPARATOR . $file_name)) {
			if (!is_dir($dstToBackup)) {
				mkdir($dstToBackup, 0777, true);
			}
			$copy = copy($file, $dstToBackup . DIRECTORY_SEPARATOR . $file_name);
			if ($copy == false) {
				throw new Exception("BACKUP PLIKU: {$file} ERROR {$backtrace}");
			}
			return true;
		}
		return false;
	}

	/**
	 *
	 */
	private function restoreBackup()
	{
		self::writeToLog('PRZYWRÓCENIE BACKUPU');
		$this->copyDirectory(MK_Registry::get("upgradeBackupFolder"), '.');
	}

	/**
	 * @param $folder
	 */
	public function backupFolder($folder)
	{
		self::writeToLog("BACKUP KATALOGU: {$folder} OK ");
		$this->copyDirectory($folder, MK_Registry::get("upgradeBackupFolder") . DIRECTORY_SEPARATOR . $folder, false);
	}

	/**
	 * @param $upgradeFolder
	 */
	private function setUpgradeFolder($upgradeFolder)
	{
		if (!is_dir($upgradeFolder)) {
			mkdir($upgradeFolder);
		}
		MK_Registry::set("upgradeFolder", $upgradeFolder);
	}

	/**
	 * @param $upgradeLogFolder
	 */
	private function setUpgradeLogFolder($upgradeLogFolder)
	{
		if (!is_dir($upgradeLogFolder)) {
			mkdir($upgradeLogFolder);
		}
		MK_Registry::set("upgradeLogFolder", $upgradeLogFolder);
	}

	/**
	 * @param $upgradeBackupFolder
	 */
	private function setUpgradeBackupFolder($upgradeBackupFolder)
	{
		if (!is_dir($upgradeBackupFolder)) {
			mkdir($upgradeBackupFolder, MK_CHMOD_DIR, true);
		}
		MK_Registry::set("upgradeBackupFolder", $upgradeBackupFolder);
	}

	/**
	 * @param $upgradeSourceFolder
	 */
	function setUpgradeSourceFolder($upgradeSourceFolder)
	{
		if (!is_dir($upgradeSourceFolder)) {
			mkdir($upgradeSourceFolder);
		}
		MK_Registry::set("upgradeSourceFolder", $upgradeSourceFolder);
	}

	/**
	 * Ustawia wartość w rejestrze przechowującą czas uruchomienia upgradu w formacie "Ymd_His"
	 * @return string
	 */
	public static function setUpgradeBeginTime()
	{
		MK_Registry::set("upgradeBeginTime", date("Ymd_His"));
	}

	/**
	 * @param $text
	 */
	public static function writeToLog($text)
	{
		self::prepareLogFile();
		file_put_contents(MK_Registry::get('logFile'), date("Y-m-d H:i:s") . " upgrade.php: " . $text . PHP_EOL, FILE_APPEND);
	}

	/**
	 * Ustawia nazwe pliku z logami
	 */
	public static function prepareLogFile()
	{
		if (MK_Registry::isRegistered("logFile") == false){
			MK_Registry::set("logFile", MK_Registry::get("upgradeLogFolder") . DIRECTORY_SEPARATOR . "status.log");
		}
	}

	/**
	 * @param $version
	 * @param $licznik
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function checkVersion($version, $licznik)
	{
		if($this->isDeveloper === true) {
			return true;
		}

		$currentVersion = str_replace(".", "", $this->GetOne("SELECT get_app_version() as get_app_version"));
		$currentVersion++;
		if ($licznik == 1) {
			// wgranie patchy
			if ($currentVersion == $version) {
				return true;
			}
			// rozpoczęcie upgradu
			if (($currentVersion - 1) == $version) {
				return true;
			}
			throw new Exception("ZŁA WERSJA OCZEKIWANO: {$currentVersion} PODANO {$version}");
		}

		if ($currentVersion != $version) {
			throw new Exception("ZŁA WERSJA OCZEKIWANO: {$currentVersion} PODANO {$version}");
		}

		return false;
	}

	/**
	 * @param $source
	 * @param $destination
	 * @param bool $replaceDestinationFile
	 *
	 * @throws Exception
	 */
	function copyDirectory($source, $destination, $replaceDestinationFile = true)
	{
		if (is_dir($source)) {
			if (!is_dir($destination)){
				@mkdir($destination, MK_CHMOD_DIR, true);
			}
			$directory = dir($source);
			while (FALSE !== ($readdirectory = $directory->read())) {
				if ($readdirectory == '.' || $readdirectory == '..') {
					continue;
				}
				$PathDir = $source . DIRECTORY_SEPARATOR . $readdirectory;
				if (is_dir($PathDir)) {
					$this->copyDirectory($PathDir, $destination . DIRECTORY_SEPARATOR . $readdirectory, $replaceDestinationFile);
					continue;
				}
				$bool = true;
				// czy ma podmienic
				if ($replaceDestinationFile == false) {
					if (!is_file($destination . DIRECTORY_SEPARATOR . $readdirectory)) {
						$bool = copy($PathDir, $destination . DIRECTORY_SEPARATOR . $readdirectory);
					}
				} else {
					$bool = copy($PathDir, $destination . DIRECTORY_SEPARATOR . $readdirectory);
				}
				if ($bool == false) {
					self::writeToLog("BŁĄD PODZCZAS KOPIOWANIA PLIKU: {$PathDir}  => {$destination}" . DIRECTORY_SEPARATOR . $readdirectory);
				}
			}
			$directory->close();
		}
		elseif (is_file($source)) {
			$bool = true;
			if ($replaceDestinationFile == false) {
				if (!is_file($destination)) {
					$bool = copy($source, $destination);
				}
			} else {
				$bool = copy($source, $destination);
			}

			if ($bool == false) {
				self::writeToLog("BŁĄD PODZCZAS KOPIOWANIA PLIKU: {$source}  => {$destination}");
			}
		}
		else {
		    throw new Exception("BŁĄD PODZCZAS KOPIOWANIA: {$source} NIE ISTNIEJE");
		}


	}

	/**
	 * Pobranie listy taskow, ktore byly wykonane dla konkretnej wersji
	 *
	 * @param string $version
	 * @param string $patch_name
	 *
	 * @return integer
	 */
	private function getCompletedUpgradeTaks($version = '', $patch_name = '')
	{
		if (!$this->tableExist('upgrade_completed_task')) {
			$this->Execute('CREATE TABLE upgrade_completed_task(
						  id serial NOT NULL,
						  app_version character varying(10) NOT NULL,
						  patch_name character varying(50),
						  createdate timestamp without time zone DEFAULT now(),
						  CONSTRAINT upgrade_completed_task_pk PRIMARY KEY (id)
						)
						WITHOUT OIDS;');
		}

		$sql = 'SELECT COUNT(*) as ilosc
					FROM upgrade_completed_task
					WHERE app_version = ? AND patch_name = ?';

		return $this->GetOne($sql, array($version, $patch_name));
	}

	/**
	 * ustawienie zadania jako wykonane
	 *
	 * @param $version
	 * @param $patch_name
	 *
	 * @throws Exception
	 */
	private function setCompletedUpgradeTask($version, $patch_name)
	{
		$res = $this->Execute('INSERT INTO upgrade_completed_task(app_version, patch_name) VALUES (?,?)', array($version, $patch_name));
		if ($res == false) {
			throw new Exception("BLAD PODCZAS DODAWANIA WYKONANEGO ZADANIA DO BAZY: {$this->getErrorMsg()}");
		}
	}

	/**
	 * Sprawdzanie licencji
	 * @todo uzyc z klasy z licencjami
	 * @throws Exception
	 */
	private function checkLicence()
	{
		if($this->isDeveloper === true) {
			self::writeToLog('Pomijanie weryfikacji licencji (developer)');
			return;
		}
		self::writeToLog('Weryfikacja licencji');

		// Myk, dopóki nie będzie jednej tablicy konfiguracyjnej dla wszystkich aplikacji
		switch(strtolower(APP_NAME)) {
			default: $licence = $this->GetOne('SELECT conf_value FROM system_config WHERE conf_key = ?', array('licence')); break;
			case 'broker': $licence = $this->GetOne('SELECT conf_value FROM config WHERE conf_key = ?', array('bip_licence')); break;
			case 'spirb': $licence = $this->GetOne('SELECT config_value FROM swpirb_config WHERE symbol = ?', array('spirb_licence')); break;
			// (...)
		}
		// MYK

		if (!empty($licence)) {
			$expireDate = substr($licence, 0, 4) . '-' . substr($licence, 4, 2) . '-' . substr($licence, 6, 2);
			if (strtotime($expireDate) < strtotime(date('Y-m-d'))) {
				throw new Exception('Wygasło wsparcie techniczne. Proszę o kontakt z administratorem');
			}
			$generatedLicence = str_replace('-', '', $expireDate) . md5($expireDate . ' ' . exec('hostname') . ' ' . APP_PATH);
			if (strlen($licence) != 40 || strcmp($generatedLicence, $licence) != 0) {
				throw new Exception('Błąd krytyczny. Niezgodna sygnatura licencji!');
			}
		}
	}

	/**
	 * @throws Exception
	 */
	private function proceed()
	{
		if(MK_Registry::get("upgradeSourceFolder")) {
			//pobranie folderów wersji
			$foldersVersion = scandir(MK_Registry::get("upgradeSourceFolder"));
			if(count($foldersVersion) < 3) {
				self::writeToLog("BRAK SQLi i PARSERÓW DO WYKONANIA");
				return;
			}
			$licznik = 1;
			foreach($foldersVersion as $folderVersion) {
				if(in_array($folderVersion, $this->ignoreDir)) {
					continue;
				}
				//sprawdzenie czy jest poprawna nastepna wersja
				$this->checkVersion(str_replace("_", "", $folderVersion), $licznik);
				$licznik++;
				self::writeToLog("Bieżąca wersja: " . $folderVersion);
				// zapamiętanie która wersja jest upgradowana,
				// potrzebne to jest do backupów
				MK_Registry::set("currentVersion", str_replace("_", "", $folderVersion));
				//pobranie folderów z datami w wersji
				$foldersInVersion = scandir(MK_Registry::get("upgradeSourceFolder") . DIRECTORY_SEPARATOR . $folderVersion);
				if(empty($foldersInVersion)) {
					continue;
				}
				foreach($foldersInVersion as $folderDate) {
					if(in_array($folderDate, $this->ignoreDir)) {
						continue;
					}
					//pobranie plikow z folderów z datami w wersji
					$filesInfolderDatePath = MK_Registry::get("upgradeSourceFolder") . DIRECTORY_SEPARATOR . $folderVersion . DIRECTORY_SEPARATOR . $folderDate;
					$filesInfolderDate = scandir($filesInfolderDatePath);
					if(empty($filesInfolderDate)) {
						continue;
					}
					foreach($filesInfolderDate as $file) {
						$pathToFile = $filesInfolderDatePath . DIRECTORY_SEPARATOR . $file;
						// zadanie bylo juz wykonane
						$completedUpgradeTaks = $this->getCompletedUpgradeTaks($folderVersion, $folderDate . DIRECTORY_SEPARATOR . $file);
						if(!empty($completedUpgradeTaks) || !is_file($pathToFile)) {
							continue;
						}
						$fileName = explode(".", $file);
						$extension = array_pop($fileName);
						$fileName = implode(".", $fileName);
						$setCompletedUpgradeTask = false;
						self::writeToLog("BEGIN ({$extension}): {$pathToFile}");
						// rozpoznawanie po typie
						switch($extension) {
							default :
							case 'sql':
								$zawartosc = file_get_contents($pathToFile);
								if(!empty($zawartosc)) {
									$affectedRows = $this->Execute($zawartosc);
								} else {
									self::writeToLog("PUSTA ZAWARTOŚĆ PLIKU {$pathToFile}");
								}
								// oznaczenie zadania jako wykonane
								$setCompletedUpgradeTask = true;
								unset($zawartosc);
								break;
							case 'php':
								// zapamiętanie scieżki zgrywanego pliku,
								// aby później można było ją wykorzystać w includowanej klasie
								MK_Registry::set("filesInfolderDatePath", $filesInfolderDatePath);
								/** @noinspection PhpIncludeInspection */
								include ($pathToFile);
								// oznaczenie zadania jako wykonane
								$setCompletedUpgradeTask = true;
								break;
						}
						self::writeToLog("END ({$extension}): {$pathToFile}");
						if($setCompletedUpgradeTask) {
							$this->setCompletedUpgradeTask($folderVersion, $folderDate . DIRECTORY_SEPARATOR . $file);
						}
					}
				}
			}
		}
	}
}