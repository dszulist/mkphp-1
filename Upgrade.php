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
	 * Konstruktor
	 */
	function __construct()
	{
		try {
			self::setUpgradeBeginTime();
			$this->begin();
		} catch (Exception $e) {
			$msg = "{$e->getMessage()} , Kod: {$e->getCode()}, Plik: {$e->getFile()}, Linia: {$e->getLine()}";
			self::writeToLog($msg);
			self::writeToLog('UPGRADE PRZERWANY!!!');
			$this->transFail();
			// ustawienie stanu aplikacji na działającą
			$this->changeApplicationState('running');
			$this->restoreBackup();
			die("false");
		}
	}

	/**
	 * Uruchamia proces aktualizacji
	 */
	public function begin()
	{
		// ustawienie stanu aplikacji na upgrade
		$this->changeApplicationState('upgrade');
		$this->transStart();

		// ustawienie glownego katalogu do upgradu
		$this->setUpgradeFolder('upgrade');
		// ustawienie katalogu do zapisywania logow
		$this->setUpgradeLogFolder('upgrade' . DIRECTORY_SEPARATOR . 'log');
		// ustawienie katalogu do zapisywania backupu
		$this->setUpgradeBackupFolder('upgrade' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $this->getUpgradeBeginTime());
		// ustawienie katalogu do plikow sql i parserow
		$this->setUpgradeSourceFolder('upgrade' . DIRECTORY_SEPARATOR . 'source');

		// sprawdzenie licencji
		$this->checkLicence();
		self::writeToLog('UPGRADE ROZPOCZĘTY');

		// czyszczenie sessji użytkowników
		$this->clearUserSessions();

		// ustawienie glownego katalogu do upgradu
		$this->proceed();
		self::writeToLog('UPGRADE ZAKOŃCZONY');
		$this->transComplete();

		// ustawienie stanu aplikacji na działającą
		$this->changeApplicationState('running');
		die("true");
	}

	/**
	 * Ustawienie stanu aplikacji
	 *
	 * @param string state ('upgrade', 'running')
	 */
	private function changeApplicationState($state)
	{

		switch ($state) {
			case 'running':
				removeDir('under_construction.txt');
				break;
			case 'upgrade':
				removeDir('under_construction.txt');
				file_put_contents('under_construction.txt', 'upgrade');
				break;
		}
	}

	/**
	 *
	 */
	private function clearUserSessions()
	{
		$session_path = 'temp' . DIRECTORY_SEPARATOR . 'sessions';
		removeDir($session_path);
		@mkdir($session_path, 0755, true);
	}

	/**
	 *
	 * @param $file
	 *
	 * @throws exception
	 * @return bool
	 */
	public function backupFile($file)
	{
		$debug_backtrace = array_shift(debug_backtrace());
		$debug_backtrace = "{$debug_backtrace['file']}: {$debug_backtrace ['line']}: {$debug_backtrace['class']} {$debug_backtrace['type']}{$debug_backtrace['function']}";

		if (!file_exists($file)) {
			self::writeToLog("BACKUP PLIKU: {$file} PLIK NIE ISTNIEJE");
			return true;
		}

		$path = explode(DIRECTORY_SEPARATOR, $file);
		// nazwa pliku
		$file_name = array_pop($path);

		//wzgledna sciezka do pliku oryginalnego
		$path = implode(DIRECTORY_SEPARATOR, $path);
		self::writeToLog("BACKUP PLIKU: {$file} OK {$debug_backtrace}");
		$dstToBackup = $this->getUpgradeBackupFolder() . DIRECTORY_SEPARATOR . $path;

		if (!file_exists($dstToBackup . DIRECTORY_SEPARATOR . $file_name)) {
			if (!is_dir($dstToBackup)) {
				mkdir($dstToBackup, 0777, true);
			}
			$copy = copy($file, $dstToBackup . DIRECTORY_SEPARATOR . $file_name);
			if ($copy == false) {
				throw new exception("BACKUP PLIKU: {$file} ERROR {$debug_backtrace}");
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
		$this->copyDirectory($this->getUpgradeBackupFolder(), '.');
		self::writeToLog('PRZYWRÓCENIE BACKUPU');
	}

	/**
	 *
	 */
	public function backupFolder($folder)
	{
		self::writeToLog("BACKUP KATALOGU: {$folder} OK ");
		$this->copyDirectory($folder, $this->getUpgradeBackupFolder() . DIRECTORY_SEPARATOR . $folder, false);
	}

	/**
	 * @return null
	 */
	private function getUpgradeFolder()
	{
		return MK_Registry::get("upgradeFolder");
	}

	/**
	 * @return null
	 */
	public static function getUpgradeLogFolder()
	{
		return MK_Registry::get("upgradeLogFolder");
	}

	/**
	 * @return null
	 */
	private function getUpgradeBackupFolder()
	{
		return MK_Registry::get("upgradeBackupFolder");
	}

	/**
	 * @return null
	 */
	function getUpgradeSourceFolder()
	{
		return MK_Registry::get("upgradeSourceFolder");
	}

	/**
	 *
	 */
	private function setUpgradeFolder($upgradeFolder)
	{
		if (!is_dir($upgradeFolder)) {
			mkdir($upgradeFolder);
		}
		MK_Registry::get("upgradeFolder", $upgradeFolder);
	}

	/**
	 *
	 */
	private function setUpgradeLogFolder($upgradeLogFolder)
	{
		if (!is_dir($upgradeLogFolder)) {
			mkdir($upgradeLogFolder);
		}
		MK_Registry::get("upgradeLogFolder", $upgradeLogFolder);
	}

	/**
	 *
	 */
	private function setUpgradeBackupFolder($upgradeBackupFolder)
	{
		if (!is_dir($upgradeBackupFolder)) {
			mkdir($upgradeBackupFolder, 0755, true);
		}
		MK_Registry::get("upgradeBackupFolder", $upgradeBackupFolder);
	}

	/**
	 *
	 */
	function setUpgradeSourceFolder($upgradeSourceFolder)
	{
		if (!is_dir($upgradeSourceFolder)) {
			mkdir($upgradeSourceFolder);
		}
		MK_Registry::get("upgradeSourceFolder", $upgradeSourceFolder);
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
	 * @return string
	 */
	public static function getUpgradeBeginTime()
	{
		return MK_Registry::get("upgradeBeginTime");
	}

	/**
	 *
	 */
	public static function writeToLog($text)
	{
		self::prepareLogFile();
		file_put_contents(MK_Registry::get('logFile'), date("Y-m-d H:i:s") . ": " . $text . PHP_EOL, FILE_APPEND);
	}

	/**
	 * Ustawia nazwe pliku z logami
	 */
	public static function prepareLogFile()
	{
		$logFile = MK_Registry::get("logFile");
		if (empty($logFile)){
			MK_Registry::set("logFile", self::getUpgradeLogFolder() . DIRECTORY_SEPARATOR . self::getUpgradeBeginTime() . ".log");
		}
	}

	/**
	 * @return null
	 */
	private function getLogFile()
	{
		return MK_Registry::get('logFile');
	}

	/**
	 * @param $version
	 * @param $licznik
	 *
	 * @return bool
	 * @throws exception
	 */
	private function checkVersion($version, $licznik)
	{

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
			throw new exception("ZŁA WERSJA OCZEKIWANO: {$currentVersion} PODANO {$version}");
		}

		if ($currentVersion != $version) {
			throw new exception("ZŁA WERSJA OCZEKIWANO: {$currentVersion} PODANO {$version}");
		}

		return false;
	}

	/**
	 * @param $source
	 * @param $destination
	 * @param bool $replaceDestinationFile
	 *
	 * @throws exception
	 */
	function copyDirectory($source, $destination, $replaceDestinationFile = true)
	{
		if (is_dir($source)) {
			if (!is_dir($destination)){
				@mkdir($destination, 0755, true);
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

		throw new exception("BŁĄD PODZCZAS KOPIOWANIA: {$source} NIE ISTNIEJE");

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

		$licence = $this->GetOne('SELECT conf_value FROM config WHERE conf_key = ?', array('bip_licence'));
		if (!empty($licence)) {

			$expireDate = substr($licence, 0, 4) . '-' . substr($licence, 4, 2) . '-' . substr($licence, 6, 2);
			if (strtotime($expireDate) < strtotime(date('Y-m-d'))) {
				throw new Exception('Wygasło wsparcie techniczne. Proszę o kontakt z administratorem');
			}
			$generatedLicence = str_replace('-', '', $expireDate) . md5($expireDate . ' ' . exec('hostname') . ' ' . realpath(dirname(__FILE__)));
			if (strlen($licence) != 40 || strcmp($generatedLicence, $licence) != 0) {
				throw new Exception('Błąd krytyczny. Niezgodna sygnatura licencji!');
			}
		}
	}

	/**
	 * @throws exception
	 */
	private function proceed()
	{
		if ($this->getUpgradeSourceFolder()) {
			//pobranie folderów wersji
			$foldersVersion = scandir($this->getUpgradeSourceFolder());
			if (count($foldersVersion) < 3) {
				throw new exception("BRAK SQLi i PARSERÓW DO WYKONANIA");
			}
			$licznik = 1;
			foreach ($foldersVersion as $folderVersion) {
				if (!in_array($folderVersion, array('.', '..'))) {
					//sprawdzenie czy jest poprawna nastepna wersja
					$this->checkVersion(str_replace("_", "", $folderVersion), $licznik);
					$licznik++;
					self::writeToLog("BIEŻĄCA WERSJA {$folderVersion}");
					// zapamiętanie która wersja jest upgradowana,
					// potrzebne to jest do backupów
					MK_Registry::set("currentVersion", str_replace("_", "", $folderVersion));
					//pobranie folderów z datami w wersji
					$foldersInVersion = scandir($this->getUpgradeSourceFolder() . DIRECTORY_SEPARATOR . $folderVersion);
					if (!empty($foldersInVersion)) {
						foreach ($foldersInVersion as $folderDate) {
							if (!in_array($folderDate, array('.', '..'))) {
								//pobranie plikow z folderów z datami w wersji
								$filesInfolderDatePath = $this->getUpgradeSourceFolder() . DIRECTORY_SEPARATOR . $folderVersion . DIRECTORY_SEPARATOR . $folderDate;
								$filesInfolderDate = scandir($filesInfolderDatePath);
								if (!empty($filesInfolderDate)) {
									foreach ($filesInfolderDate as $file) {
										$pathToFile = $filesInfolderDatePath . DIRECTORY_SEPARATOR . $file;
										// zadanie bylo juz wykonane
										$completedUpgradeTaks = $this->getCompletedUpgradeTaks($folderVersion, $folderDate . DIRECTORY_SEPARATOR . $file);
										if (!empty($completedUpgradeTaks)) {
											continue;
										}
										if (is_file($pathToFile)) {
											$fileName = explode(".", $file);
											$extension = array_pop($fileName);
											$fileName = implode(".", $fileName);
											$setCompletedUpgradeTask = false;
											// rozpoznawanie po typie
											switch ($extension) {
												default :
												case 'sql':
													$zawartosc = file_get_contents($pathToFile);
													if (!empty($zawartosc)) {
														$res = $this->Execute($zawartosc);
														if ($res == false) {
															throw new Exception($filesInfolderDatePath . DIRECTORY_SEPARATOR . $file . " " . $this->getErrorMsg());
														} else {
															self::writeToLog("{$pathToFile} OK");
														}
													} else {
														self::writeToLog("{$pathToFile} PUSTA ZAWARTOŚĆ PLIKU");
													}
													// oznaczenie zadania jako wykonane
													$setCompletedUpgradeTask = true;
													unset($zawartosc);
													break;
												case 'php':
													self::writeToLog("{$pathToFile} BEGIN");
													// zapamiętanie scieżki zgrywanego pliku,
													// aby później można było ją wykorzystać w includowanej klasie
													MK_Registry::set("filesInfolderDatePath", $filesInfolderDatePath);
													/** @noinspection PhpIncludeInspection */
													include ($pathToFile);
													// oznaczenie zadania jako wykonane
													$setCompletedUpgradeTask = true;
													self::writeToLog("{$pathToFile} END");
													break;
											}

											if ($setCompletedUpgradeTask) {
												$this->setCompletedUpgradeTask($folderVersion, $folderDate . DIRECTORY_SEPARATOR . $file);
											}

										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

}