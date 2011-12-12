<?php

/**
 * MK_Logs
 *
 * Obsługa zapisywania plików z błędami i wysyłania ich do logs.madkom.pl
 *
 * Last modified:
 *
 * @category MK
 * @package	MK_Logs
 * @author jkonefal
 */
class MK_Logs {

	private $_appPath;
	private $_dirErrors;
	private $_dirErrorsUpload;
	private $_fileReportZip;
	private $_fileReportLock;
	private $_debug = false;
	private $_sendDelay = 0;
	private $_reportUrl = 'https://logs.madkom.pl/report.php';
	private $_reportAuth = 'aplikacja:Cziayu48B';

	/**
	 * Ustawienie ścieżki do aplikacji (konstruktor)
	 *
	 * @param string $appPath
	 */
	public function __construct($appPath, $debug=false) {
		$this->_debug = $debug;

		$this->_debug('Ustawienie ścieżki do aplikacji: ' . $appPath);
		$this->_appPath = realpath($appPath);
		$this->_dirErrors = $this->_appPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'errors';
		$this->_dirErrorsUpload = $this->_dirErrors . DIRECTORY_SEPARATOR . 'upload';
		$this->_fileReportLock = $this->_dirErrors . DIRECTORY_SEPARATOR . 'report.lock';
		$this->_fileReportZip = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . 'report.zip';

		// Sprawdzenie struktury katalogów raportów błędów
		$this->_debug('Przygotowanie struktury katalogów');
		if (!file_exists($this->_dirErrorsUpload) || !is_dir($this->_dirErrorsUpload)) {
			if (!@mkdir($this->_dirErrorsUpload, 0775, true)) {
				$this->_debug('Nie można utworzyć katalogu do przechowania raportów błędów do uploadu', true);
				exit;
			}
		}
	}

	/**
	 * Zapisanie komunikatu błędu do odpowiedniego pliku w folderze z raportami błędów
	 * Po zapisaniu wiadomości nastąpi próba wysłania pliku na serwer logs.madkom.pl
	 *
	 * @param string $type - 'php', 'exception', 'db', 'js' itp.
	 * @param string $devMessage - komunikat błędu do zapisania
	 * @return string
	 */
	public function saveToFile($type, &$devMessage) {
		$errorFile = $this->_dirErrors . DIRECTORY_SEPARATOR . strtolower($type) . '.log';
		$msgMd5 = md5($devMessage);

		// Czy błąd się powtórzył?
		if (file_exists($errorFile) && is_writable($errorFile)) {
			$fewBytes = 100;
			$fr = fopen($errorFile, 'r+');
			if ($fr) {
				if (fseek($fr, -$fewBytes, SEEK_END) === 0) {
					$fpos = ftell($fr);
					$fewLines = '';
					while (!feof($fr)) {
						$fewLines .= fgets($fr, $fewBytes);
					}
					if (preg_match("#([0-9]+)\t@\t{$msgMd5}#", $fewLines, $matches)) {
						fseek($fr, $fpos);
						ftruncate($fr, $fpos + $fewBytes);
						fputs($fr, str_replace($matches[0], (intval($matches[1]) + 1) . "\t@\t" . $msgMd5, $fewLines));
						fclose($fr);
						return;
					}
				}
			}
		}

		// Jest to nowy błąd
		list($usec, $sec) = explode(' ', microtime());
		$msgHeader = "#^#\t" . date("Y-m-d", $sec) . "\t" . bcadd($usec, $sec, 8) . "\t\"{$type}\"\t#^#\n";
		$msgFooter = "#$#$#\t" . "1\t@\t" . $msgMd5 . "\t#$#$#" . "\n\n\n\n";
		error_log($msgHeader . $devMessage . $msgFooter, 3, $errorFile);
	}

	/**
	 * Debugowanie (wiadomość)
	 *
	 * @param string $message
	 * @param boolean $force (default:false)
	 */
	private function _debug($message, $force=false) {
		if ($force === true || $this->_debug === true) {
			echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
		}
	}

	/**
	 * Przygotowanie plików z raportami błędów do wysłania.
	 * Przeniesienie w/w plików do folderu "upload" lub dopisanie do nich zawartości.
	 *
	 * @return boolean
	 */
	private function _prepareLogFiles() {
		// Odczytanie listy aktualnych plików z raportami błędów i przeniesienie ich do folderu "upload"
		$logsPath = $this->_dirErrors . DIRECTORY_SEPARATOR . '*.log';
		$filePaths = glob($logsPath);
		$filePathCount = count($filePaths);
		$this->_debug('Odczytanie listy aktualnych plików z raportami błędów (count: ' . $filePathCount . ')');
		if ($filePathCount > 0) {
			// Sprawdzenie czy wszystkie wysyłane pliki istnieją
			foreach ($filePaths as $filePathSrc) {
				if (!file_exists($filePathSrc)) {
					$this->_debug('Plik nie istnieje - ' . $filePathSrc);
					return false;
				}
				$filePathDest = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . basename($filePathSrc);
				if (file_exists($filePathDest)) {
					if (!file_put_contents($filePathDest, file_get_contents($filePathSrc), FILE_APPEND)) {
						$this->_debug('Nie udało się przenieść zawartości pliku: ' . $filePathSrc);
						return false;
					}
					unlink($filePathSrc);
				} else {
					if (!rename($filePathSrc, $filePathDest)) {
						$this->_debug('Nie udało się przenieść pliku: ' . $filePathSrc);
						return false;
					}
				}
			}
		}
		return true;
	}

	/**
	 * Przygotowanie plików z raportami błędów do wysłania.
	 * Przeniesienie w/w plików do folderu "upload" lub dopisanie do nich zawartości.
	 * Odczytanie listy plików do wysłania i spakowania ich do ZIP-a.
	 * Zwrócenie ścieżki do ZIP-a
	 *
	 * @return string
	 */
	private function _prepareZipFile() {
		$logsPath = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . '*.log';
		$fileUploadPaths = glob($logsPath);
		if (count($fileUploadPaths) == 0) {
			$this->_debug('Brak plików ' . $logsPath . ' do wysłania');
			$this->_debug('Usuwam lock: ' . $this->_fileReportLock);
			unlink($this->_fileReportLock);
			return true;
		}

		// Spakowanie plików do wysłania
		$zip = new ZipArchive();
		if ($zip->open($this->_fileReportZip, ZIPARCHIVE::OVERWRITE) !== true) {
			$this->_debug('Nie udało się utworzyć archiwum: ' . $this->_fileReportZip);
			return false;
		}
		foreach ($fileUploadPaths as $filePath) {
			$this->_debug('Dodawanie pliku do archiwum: ' . $filePath . ' [' . filesize($filePath) . ' B]');
			$zip->addFile($filePath, basename($filePath));
		}
		$zip->close();
		if (!file_exists($this->_fileReportZip)) {
			$this->_debug('Niespodziewany błąd. Nie udało się utworzyć archiwum: ' . $this->_fileReportZip);
			return false;
		}
		$this->_debug('Utworzono archiwum ZIP: ' . $this->_fileReportZip . ' [' . filesize($this->_fileReportZip) . ' B]');

		return true;
	}

	/**
	 * Usunięcie wysłanych plików z logami, archiwum zip oraz lock-a
	 *
	 * @param boolean $delLogs
	 */
	private function _clearFiles($delLogs=true) {
		if ($delLogs) {
			$logsPath = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . '*.log';
			$this->_debug('Usuwam pliki: ' . $logsPath);
			$fileUploadPaths = glob($logsPath);
			foreach ($fileUploadPaths as $filePath) {
				if (file_exists($filePath)) {
					unlink($filePath);
				}
			}
		}
		if (file_exists($this->_fileReportZip)) {
			$this->_debug('Usuwam archiwum: ' . $this->_fileReportZip);
			unlink($this->_fileReportZip);
		}
		if (file_exists($this->_fileReportLock)) {
			$this->_debug('Usuwam lock: ' . $this->_fileReportLock);
			unlink($this->_fileReportLock);
		}
	}

	/**
	 * Wysłanie zapytania POST-em do logs.madkom.pl (cURL)
	 *
	 */
	private function _sendRequest() {
		$this->_debug('Odczytanie informacji o aplikacji (appinfo)');
		$appInfo = MK_AppInfo::load($this->_appPath);
		$postData = array(
			'hostname' => exec('hostname'),
			'appname' => $appInfo['APP'],
			'dirname' => basename($this->_appPath),
			'dbname' => $appInfo['DATABASE']
		);

		$zipFileSize = filesize($this->_fileReportZip);
		if ($zipFileSize >= 10485760) { // 10MB = 10*1024*1024
			$newReportZip = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . date('Ymd-His') . '_overweight_report.zip';
			$newReportLog = $this->_fileReportZip . ' => ' . $newReportZip;
			$this->_debug($newReportLog);
			rename($this->_fileReportZip, $newReportZip);
			$this->_clearFiles();
			$this->saveToFile('overweight', $newReportLog);
			return 'Rozmiar pliku ' . $this->_fileReportZip . ' przekroczył 10MB !';
		} else {
			$postData['archive'] = "@" . $this->_fileReportZip;
		}

		$this->_debug('Inicjowanie połączenia z logs.madkom.pl');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_reportUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 600);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->_reportAuth);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept-Language: pl,en-us;q=0.7,en;q=0.3',
			'Accept-Charset: ISO-8859-2,utf-8;q=0.7,*;q=0.7'
		));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		$results = curl_exec($ch);
		curl_close($ch);
		$this->_debug('Zamknięcie połączenia z logs.madkom.pl');

		return $results;
	}

	/**
	 * Ustawianie opóźnienia w wysłaniu POST-a do logs.madkom.pl
	 *
	 * @param itneger $seconds
	 */
	public function setDelay($seconds=0) {
		$this->_sendDelay = ($seconds > 0) ? $seconds : rand(0, 59);
	}

	/**
	 * Wysłanie plików z raportami błędów do logs.madkom.pl
	 * Spakowanie wszystkich plików z błędami do zip-a.
	 *
	 * @param array $filePaths
	 *
	 * @return boolean
	 */
	public function sendPackage() {
		if (file_exists($this->_fileReportLock)) {
			// Sprawdzenie czy lock istnieje dłużej jak 24h = 60*60*24 = 86400
			if (time() - filemtime($this->_fileReportLock) > 86400) {
				$this->_debug('Lock istnieje dłużej jak 24h. Usuwam i próbuję wysłać paczkę');
				unlink($this->_fileReportLock);
			} else {
				$this->_debug('Istnieje lock: ' . $this->_fileReportLock);
				return false;
			}
		}

		$this->_debug('Utworzenie lock-a: ' . $this->_fileReportLock);
		file_put_contents($this->_fileReportLock, date('Y-m-d H:i:s') . ' :: ' . $this->_fileReportZip);

		// Sprawdzenie czy uda się przygotować pliki do wysłania
		if (!$this->_prepareLogFiles() || !$this->_prepareZipFile()) {
			$this->_debug('Usuwam lock: ' . $this->_fileReportLock);
			unlink($this->_fileReportLock);
			return false;
		}

		// Brak plików do wysłania, lock nie istnieje
		if (!file_exists($this->_fileReportLock)) {
			return true;
		}

		if ($this->_sendDelay > 0) {
			$this->_debug('Usypiam skrypt na okres ' . $this->_sendDelay . 's');
			sleep($this->_sendDelay);
		}

		$results = $this->_sendRequest();
		$this->_debug('Odpowiedź serwera: ' . PHP_EOL . $results);
		if ($results !== 'true') {
			$this->_debug('Usuwam lock: ' . $this->_fileReportLock);
			unlink($this->_fileReportLock);
			return false;
		}

		$this->_debug('Udało się wysłać paczkę');
		$this->_clearFiles();
		return true;
	}

}