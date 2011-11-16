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
	private $_sendDelay = 0;
	private $_reportUrl = 'https://logs.madkom.pl/report.php';
	private $_reportAuth = 'aplikacja:Cziayu48B';

	/**
	 * Ustawienie ścieżki do aplikacji (konstruktor)
	 *
	 * @param string $appPath
	 */
	public function __construct($appPath) {
		$this->_appPath = realpath($appPath);
		$this->_dirErrors = $this->_appPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'errors';
		$this->_dirErrorsUpload = $this->_dirErrors . DIRECTORY_SEPARATOR . 'upload';
		$this->_fileReportLock = $this->_dirErrors . DIRECTORY_SEPARATOR . 'report.lock';
		$this->_fileReportZip = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . 'report.zip';

		// Sprawdzenie struktury katalogów raportów błędów
		if (!file_exists($this->_dirErrorsUpload) || !is_dir($this->_dirErrorsUpload)) {
			if (!@mkdir($this->_dirErrorsUpload, 0775, true)) {
				die('Nie można utworzyć katalogu do przechowania raportów błędów do uploadu');
			}
		}
	}

	/**
	 * Zapisanie komunikatu błędu do odpowiedniego pliku w folderze z raportami błędów
	 * Po zapisaniu wiadomości nastąpi próba wysłania pliku na serwer logs.madkom.pl
	 *
	 * @param string $type - 'php', 'js', 'sql' itp.
	 * @param string $devMessage - komunikat błędu do zapisania
	 * @return string
	 */
	public function saveToFile($type, &$devMessage) {
		$errorFile = $this->_dirErrors . DIRECTORY_SEPARATOR . strtolower($type) . '.log';
		error_log($devMessage . "\n#" . str_repeat("\t#", 20) . "\n\n", 3, $errorFile);
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
		// Odczytanie listy aktualnych plików z raportami błędów i przeniesienie ich do folderu "upload"
		$filePaths = glob($this->_dirErrors . DIRECTORY_SEPARATOR . '*.log');
		if (count($filePaths) > 0) {
			// Sprawdzenie czy wszystkie wysyłane pliki istnieją
			foreach ($filePaths as $filePathSrc) {
				if (!file_exists($filePathSrc)) {
					return $this->sendPackageFailure('Plik nie istnieje - ' . $filePathSrc);
				}
				$filePathDest = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . basename($filePathSrc);
				if (file_exists($filePathDest)) {
					if (!file_put_contents($filePathDest, file_get_contents($filePathSrc), FILE_APPEND)) {
						return $this->sendPackageFailure('Nie udało się przenieść zawartości pliku: ' . $filePathSrc);
					}
					unlink($filePathSrc);
				} else {
					if (!rename($filePathSrc, $filePathDest)) {
						return $this->sendPackageFailure('Nie udało się przenieść pliku: ' . $filePathSrc);
					}
				}
			}
		}

		// Odczytanie listy plików do wysłania
		$fileUploadPaths = glob($this->_dirErrorsUpload . DIRECTORY_SEPARATOR . '*.log');
		if (count($fileUploadPaths) == 0) {
			return true;
		}

		// Spakowanie plików do wysłania
		$zip = new ZipArchive();
		if ($zip->open($this->_fileReportZip, ZIPARCHIVE::OVERWRITE) !== true) {
			return $this->sendPackageFailure('Nie udało się utworzyć archiwum: ' . $this->_fileReportZip);
		}
		foreach ($fileUploadPaths as $filePath) {
			$zip->addFile($filePath, basename($filePath));
		}
		$zip->close();
		if (!file_exists($this->_fileReportZip)) {
			return $this->sendPackageFailure('Niespodziewany błąd. Nie udało się utworzyć archiwum: ' . $this->_fileReportZip);
		}
	}

	/**
	 * Usunięcie lock-a i wyświetlenie komunikatu błędu
	 *
	 * @param string $message
	 */
	public function sendPackageFailure($message, $delLogs=false) {
		$this->_clearFiles($delLogs);
		echo $message . PHP_EOL;
		return false;
	}

	/**
	 * Usunięcie wysłanych plików z logami, archiwum zip oraz lock-a
	 * 
	 * @param boolean $delLogs
	 */
	private function _clearFiles($delLogs=true) {
		if( $delLogs) {
			$fileUploadPaths = glob($this->_dirErrorsUpload . DIRECTORY_SEPARATOR . '*.log');
			foreach ($fileUploadPaths as $filePath) {
				if (file_exists($filePath)) {
					unlink($filePath);
				}
			}
		}
		if (file_exists($this->_fileReportZip)) {
			unlink($this->_fileReportZip);
		}
		if (file_exists($this->_fileReportLock)) {
			unlink($this->_fileReportLock);
		}
	}

	/**
	 * Wysłanie zapytania POST-em do logs.madkom.pl (cURL)
	 *
	 */
	private function _sendRequest() {
		$appInfo = MK_AppInfo::load($this->_appPath);
		$postData = array(
			'hostname' => exec('hostname'),
			'appname' => $appInfo['APP'],
			'dirname' => basename($this->_appPath),
			'dbname' => $appInfo['DATABASE']
		);

		$zipFileSize = filesize($this->_fileReportZip);
		if ($zipFileSize >= 10485760) { // 10MB = 10*1024*1024
			$newReportZip = $this->_dirErrorsUpload . DIRECTORY_SEPARATOR . date('Ymd-His').'_overweight_report.zip';
			$newReportLog = $this->_fileReportZip.' => '.$newReportZip;
			rename($this->_fileReportZip, $newReportZip);
			$this->_clearFiles();
			$this->saveToFile('overweight', $newReportLog);
			return 'Rozmiar pliku przekroczył 10MB!';
		} else {
			$postData['archive'] = "@" . $this->_fileReportZip;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_reportUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
//		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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
			return $this->sendPackageFailure('Istnieje lock');
		}

		if( $this->_prepareZipFile() ) {
			return true;
		}

		file_put_contents($this->_fileReportLock, date('Y-m-d H:i:s') . ' :: ' . $this->_fileReportZip);
		if (!file_exists($this->_fileReportLock)) {
			return $this->sendPackageFailure('Nie udało się utworzyć lock-a');
		}

		if ($this->_sendDelay > 0) {
			sleep($this->_sendDelay);
		}

		$results = $this->_sendRequest();
		if ($results !== 'true') {
			return $this->sendPackageFailure($results);
		}

		$this->_clearFiles();
		return true;
	}

}