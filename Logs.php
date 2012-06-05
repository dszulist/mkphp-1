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

	/**
	 * Przekazana w konstruktorze ścieżka do aplikacji (domyślnie powinno być APP_PATH)
	 * @var string
	 */
	private $appPath;

	/**
	 * Ścieżka do folderu, w którym będą zapisywane logi błędów aplikacji
	 * @var string
	 */
	private $dirErrors;

	/**
	 * Ścieżka do folderu, w którym będą przechowywane logi do wysłania na logs.madkom.pl
	 * @var string
	 */
	private $dirErrorsUpload;

	/**
	 * Ścieżka do archiwum z logami (z folderu upload) do wysłania na logs.madkom.pl
	 * @var string
	 */
	private $fileReportZip;

	/**
	 * LOCK dla generowanego archiwum zip i wysyłania do logs.madkom.pl
	 * Jest tworzony w momencie kopiowania plików do folderu upload i generowania archiwum.
	 * Jest usuwany, gdy logs.madkom.pl odpowie false/true
	 * @var string
	 */
	private $fileReportLock;

	/**
	 * Czy debugować poszczególne kroki (zarówno po stronie klienta [aplikacji] jak i odpowiedzi serwera logs.madkom.pl)
	 * @var string
	 */
	private $debug = false;

	/**
	 * Opóźnienie wysyłanej paczki, żeby wszystkie aplikacje na raz nie wysyłały archiwum z logami.
	 * Podanie wartości "0" spowoduje, że będzie wygenerowany losowo czas pomiędzy 0 a 59 sekund.
	 * @var string
	 */
	private $sendDelay = 0;

	/**
	 * Adres URL do wysłania archiwum ZIP POST-em (logs.madkom.pl)
	 * @var string
	 */
	private $reportUrl = 'https://logs.madkom.pl/report.php';

	/**
	 * Login i hasło dostępu do $this->reportUrl (logs.madkom.pl)
	 * @var string
	 */
	private $reportAuth = 'aplikacja:Cziayu48B';

	/**
	 * Ustawienie ścieżki do aplikacji (konstruktor)
	 *
	 * @param string $appPath
	 * @param bool   $debug
	 */
	public function __construct($appPath, $debug = false) {
		$this->debug = $debug;

		$this->debug('Ustawienie ścieżki do aplikacji: ' . $appPath);
		$this->appPath = realpath($appPath);
		$this->dirErrors = $this->appPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'errors';
		$this->dirErrorsUpload = $this->dirErrors . DIRECTORY_SEPARATOR . 'upload';
		$this->fileReportLock = $this->dirErrors . DIRECTORY_SEPARATOR . 'report.lock';
		$this->fileReportZip = $this->dirErrorsUpload . DIRECTORY_SEPARATOR . 'report.zip';

		// Sprawdzenie struktury katalogów raportów błędów
		$this->debug('Przygotowanie struktury katalogów');
		if(!file_exists($this->dirErrorsUpload) || !is_dir($this->dirErrorsUpload)) {
			if(!@mkdir($this->dirErrorsUpload, 0775, true)) {
				$this->debug('Nie można utworzyć katalogu do przechowania raportów błędów do uploadu', true);
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
	 * @param string $md5
	 *
	 * @return string
	 */
	public function saveToFile($type, &$devMessage, $md5='') {
		$errorFile = $this->dirErrors . DIRECTORY_SEPARATOR . strtolower($type) . '.log';
		if(empty($md5)) {
			$md5 = md5($devMessage);
		}

		// Czy błąd się powtórzył?
		if(file_exists($errorFile) && is_writable($errorFile)) {
			$fewBytes = 100;
			$fr = fopen($errorFile, 'r+');
			if($fr) {
				if(fseek($fr, -$fewBytes, SEEK_END) === 0) {
					$fpos = ftell($fr);
					$fewLines = '';
					while(!feof($fr)) {
						$fewLines .= fgets($fr, $fewBytes);
					}
					if(preg_match("#([0-9]+)\t@\t{$md5}#", $fewLines, $matches)) {
						fseek($fr, $fpos);
						ftruncate($fr, $fpos + $fewBytes);
						fputs($fr, str_replace($matches[0], (intval($matches[1]) + 1) . "\t@\t" . $md5, $fewLines));
						fclose($fr);
						return;
					}
				}
			}
		}

		// Jest to nowy błąd
		list($usec, $sec) = explode(' ', microtime());
		$msgHeader = "#^#\t" . date("Y-m-d H:i:s", $sec) . "\t" . bcadd($sec, $usec, 8) . "\t\"{$type}\"\t#^#\n";
		$msgFooter = "\n#$#$#\t" . "1\t@\t" . $md5 . "\t#$#$#" . "\n\n\n\n";
		error_log($msgHeader . $devMessage . $msgFooter, 3, $errorFile);
	}

	/**
	 * Debugowanie (wiadomość)
	 *
	 * @param string  $message
	 * @param boolean $force (default:false)
	 */
	private function debug($message, $force = false) {
		if($force === true || $this->debug === true) {
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
		$logsPath = $this->dirErrors . DIRECTORY_SEPARATOR . '*.log';
		$filePaths = glob($logsPath);
		$filePathCount = count($filePaths);
		$this->debug("Odczytanie listy aktualnych plików z raportami błędów (count: {$filePathCount})");
		if($filePathCount > 0) {
			// Sprawdzenie czy wszystkie wysyłane pliki istnieją
			foreach($filePaths as $filePathSrc) {
				if(!file_exists($filePathSrc)) {
					$this->debug("Plik nie istnieje - {$filePathSrc}");
					return false;
				}
				$filePathDest = $this->dirErrorsUpload . DIRECTORY_SEPARATOR . basename($filePathSrc);
				if(file_exists($filePathDest)) {
					if(!file_put_contents($filePathDest, file_get_contents($filePathSrc), FILE_APPEND)) {
						$this->debug("Nie udało się przenieść zawartości pliku: {$filePathSrc}");
						return false;
					}
					unlink($filePathSrc);
				} else {
					if(!rename($filePathSrc, $filePathDest)) {
						$this->debug("Nie udało się przenieść pliku: {$filePathSrc}");
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
		$logsPath = $this->dirErrorsUpload . DIRECTORY_SEPARATOR . '*.log';
		$fileUploadPaths = glob($logsPath);
		if(count($fileUploadPaths) == 0) {
			$this->debug('Brak plików ' . $logsPath . ' do wysłania');
			$this->debug('Usuwam lock: ' . $this->fileReportLock);
			unlink($this->fileReportLock);
			return true;
		}

		// Spakowanie plików do wysłania
		$zip = new ZipArchive();
		if($zip->open($this->fileReportZip, ZIPARCHIVE::OVERWRITE) !== true) {
			$this->debug('Nie udało się utworzyć archiwum: ' . $this->fileReportZip);
			return false;
		}
		foreach($fileUploadPaths as $filePath) {
			$this->debug('Dodawanie pliku do archiwum: ' . $filePath . ' [' . filesize($filePath) . ' B]');
			$zip->addFile($filePath, basename($filePath));
		}
		$zip->close();
		if(!file_exists($this->fileReportZip)) {
			$this->debug('Niespodziewany błąd. Nie udało się utworzyć archiwum: ' . $this->fileReportZip);
			return false;
		}
		$this->debug('Utworzono archiwum ZIP: ' . $this->fileReportZip . ' [' . filesize($this->fileReportZip) . ' B]');

		return true;
	}

	/**
	 * Usunięcie wysłanych plików z logami, archiwum zip oraz lock-a
	 *
	 * @param boolean $delLogs
	 */
	private function _clearFiles($delLogs = true) {
		if($delLogs) {
			$logsPath = $this->dirErrorsUpload . DIRECTORY_SEPARATOR . '*.log';
			$this->debug("Usuwam pliki: {$logsPath}");
			$fileUploadPaths = glob($logsPath);
			foreach($fileUploadPaths as $filePath) {
				if(file_exists($filePath)) {
					unlink($filePath);
				}
			}
		}
		if(file_exists($this->fileReportZip)) {
			$this->debug("Usuwam archiwum: {$this->fileReportZip}");
			unlink($this->fileReportZip);
		}
		if(file_exists($this->fileReportLock)) {
			$this->debug("Usuwam lock: {$this->fileReportLock}");
			unlink($this->fileReportLock);
		}
	}

	/**
	 * Wysłanie zapytania POST-em do logs.madkom.pl (cURL)
	 *
	 * @return mixed|string
	 */
	private function _sendRequest() {
		$this->debug('Odczytanie informacji o aplikacji (appinfo)');
		$appInfo = MK_AppInfo::load($this->appPath);
		$postData = array(
			'hostname' => exec('hostname'),
			'appname' => $appInfo['APP'],
			'dirname' => basename($this->appPath),
			'dbname' => $appInfo['DATABASE']
		);

		$zipFileSize = filesize($this->fileReportZip);
		if($zipFileSize >= 10485760) { // 10MB = 10*1024*1024
			$newReportZip = $this->dirErrorsUpload . DIRECTORY_SEPARATOR . date('Ymd-His') . '_overweight_report.zip';
			$newReportLog = $this->fileReportZip . ' => ' . $newReportZip;
			$this->debug($newReportLog);
			rename($this->fileReportZip, $newReportZip);
			$this->_clearFiles();
			$this->saveToFile('overweight', $newReportLog);
			return "Rozmiar pliku {$this->fileReportZip} przekroczył 10MB !";
		} else {
			$postData['archive'] = "@" . $this->fileReportZip;
		}

		$this->debug('Inicjowanie połączenia z logs.madkom.pl');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->reportUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 600);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->reportAuth);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept-Language: pl,en-us;q=0.7,en;q=0.3',
			'Accept-Charset: ISO-8859-2,utf-8;q=0.7,*;q=0.7'
		));
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		$results = curl_exec($ch);
		curl_close($ch);
		$this->debug('Zamknięcie połączenia z logs.madkom.pl');

		return $results;
	}

	/**
	 * Ustawianie opóźnienia w wysłaniu POST-a do logs.madkom.pl
	 *
	 * @param int|\integer $seconds
	 */
	public function setDelay($seconds = 0) {
		$this->sendDelay = ($seconds > 0) ? $seconds : rand(0, 59);
	}

	/**
	 * Wysłanie plików z raportami błędów do logs.madkom.pl
	 * Spakowanie wszystkich plików z błędami do zip-a.
	 *
	 * @internal param array $filePaths
	 *
	 * @return boolean
	 */
	public function sendPackage() {
		if(file_exists($this->fileReportLock)) {
			// Sprawdzenie czy lock istnieje dłużej jak 24h = 60*60*24 = 86400
			if(time() - filemtime($this->fileReportLock) > 86400) {
				$this->debug('Lock istnieje dłużej jak 24h. Usuwam i próbuję wysłać paczkę');
				unlink($this->fileReportLock);
			} else {
				$this->debug("Istnieje lock: {$this->fileReportLock}");
				return false;
			}
		}

		$this->debug("Utworzenie lock-a: {$this->fileReportLock}");
		file_put_contents($this->fileReportLock, date('Y-m-d H:i:s') . ' :: ' . $this->fileReportZip);

		// Sprawdzenie czy uda się przygotować pliki do wysłania
		if(!$this->_prepareLogFiles() || !$this->_prepareZipFile()) {
			$this->debug("Usuwam lock: {$this->fileReportLock}");
			unlink($this->fileReportLock);
			return false;
		}

		// Brak plików do wysłania, lock nie istnieje
		if(!file_exists($this->fileReportLock)) {
			return true;
		}

		if($this->sendDelay > 0) {
			$this->debug("Usypiam skrypt na okres {$this->sendDelay}s");
			sleep($this->sendDelay);
		}

		$results = $this->_sendRequest();
		$this->debug('Odpowiedź serwera: ' . PHP_EOL . $results);
		if($results !== 'true') {
			$this->debug("Usuwam lock: {$this->fileReportLock}");
			unlink($this->fileReportLock);
			return false;
		}

		$this->debug('Udało się wysłać paczkę');
		$this->_clearFiles();
		return true;
	}

}