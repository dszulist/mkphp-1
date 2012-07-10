<?php
/**
 * MK_Console_Remote
 *
 * Klasa do zdalnego uruchamiania poleceń za pomocą ssh na podstawie pliku CSV
 * (nie wymaga pełnego wykorzystania mkphp w projekcie)
 *
 * @category MK_Console_Remote
 * @package    MK_Console_Remote
 * @author    dszulist
 */
class MK_Console_Remote
{

	/**
	 * Tablica z $this->SourceCsvFile
	 * @var array
	 */
	private $SourceCsvArray = array();

	/**
	 * Numer kolumny w pliku CSV z nazwą zdalnego hosta
	 * @var integer
	 */
	private $SourceCsvHostColumn;

	/**
	 * Numer kolumny w pliku CSV z nazwą zdalnego usera
	 * @var integer
	 */
	private $SourceCsvHostUser;

	/**
	 * Numer kolumny w pliku CSV z hasłem do zdalnego hosta
	 * @var integer
	 */
	private $SourceCsvHostPass;

	/**
	 * Lista zdalnych poleceń do wykonania
	 * @var array
	 */
	private $SshCommandsArray = array();

	/**
	 * Lista plików do przesłania na serwer (array(array('src_file_path' => '', 'dst_file_path' => ''))
	 * @var array
	 */
	private $ScpFilesArray = array();

	/**
	 * Plik gdzie będą rejestrowane
	 * @var string
	 */
	private $LogFile = '';

	/**
	 * Aktualny czas
	 * @var string
	 */
	private $now;

	/**
	 * Uruchamianie tylko z linii poleceń
	 * (tylko z linii poleceń skrypt można skutecznie zatrzymać przez CTRL+C)
	 */
	public function __construct()
	{

		if (!MK_IS_CLI) {
			die('CMD Line Only');
		}
		// włączenie na sztywno wyświetlania błędów
		header("Content-type: text/html; charset=utf-8");

		$this->now = date('Y-m-d H:i:s');
	}

	/**
	 * Ustawienie pliku logu dla całej sesji wykonywania zdalnych polecen (polecam tail -f na tym pliku)
	 *
	 * @param $logFile
	 *
	 * @throws Exception
	 */
	public function setLogFile($logFile)
	{
		try {
			if (false === file_put_contents($logFile, '', FILE_APPEND)) {
				throw new Exception('Zapis do pliku logu nie jest możliwy');
			}

			$this->LogFile = $logFile;
		}
		catch (Exception $e) {
			die($e->getMessage());
		}
	}

	/**
	 * Ustawienie pliku CSV do zczytywnia danych
	 *
	 * @param string $csvFile
	 * @param bool $ignoreFirstLine (ignorowanie pierwszej linii z pliku)
	 * @param string $endLine (znak końca linii w pliku csv)
	 * @param string $delimiter (odzielenie pól)
	 * @param string $enclosure (znak rozpoczęcia/zakończenia pola)
	 * @param string $escape
	 *
	 * @throws Exception
	 */
	public function setSourceCsvFile($csvFile, $ignoreFirstLine, $endLine = PHP_EOL, $delimiter = ',', $enclosure = '"', $escape = '\\')
	{
		try {

			$SourceCsvContent = file_get_contents($csvFile);
			if (false === $SourceCsvContent) {
				throw new Exception('Podany plik nie istnieje (nie można pobrać jego zawartości): ' . $csvFile);
			}

			$SourceCsvArray = array();
			$SourceCsvArrayTmp = explode($endLine, $SourceCsvContent);
			if (empty($SourceCsvArrayTmp)) {
				throw new Exception('Nie powiodło parsowanie pliku CSV (1)');
			}

			// usunięcie pierwszej linni z pliku
			if (true === $ignoreFirstLine) {
				unset($SourceCsvArrayTmp[0]);
			}

			// parsowanie każdej linii pliku
			foreach ($SourceCsvArrayTmp as $csvLine) {
				if (empty($csvLine)) continue;

				$csvLineArray = str_getcsv($csvLine, $delimiter, $enclosure, $escape);
				if (empty($csvLineArray)) {
					throw new Exception('Nie powiodło się parsowanie pliku CSV (2)');
				}

				$SourceCsvArray[] = $csvLineArray;
			}

			$this->SourceCsvArray = $SourceCsvArray;
		}
		catch (Exception $e) {
			$this->registerMsg($e->getMessage(), true, true);
		}
	}

	/**
	 * Zapisywanie informacji do logów oraz ewentualne ich wyświetlanie i zatrzymanie skryptu
	 *
	 * @param $msg
	 * @param bool $print
	 * @param bool $die
	 */
	private function registerMsg($msg, $print = false, $die = false)
	{
		$msg = '[' . $this->now . '] ' . $msg . PHP_EOL;

		$sts = file_put_contents($this->LogFile, $msg, FILE_APPEND);
		if (false === $sts) {
			die('Nie powiódł się zapis do pliku logu: ' . $this->LogFile . ', komunikatu: ' . $msg);
		}

		if (true === $print) {
			echo $msg;
		}

		if (true === $die) {
			die;
		}
	}

	/**
	 * Ustawia nazwę zdalnego hosta na podstawie przekazanego numeru kolumny (1 - n..)
	 *
	 * @param int $SourceCsvHostColumn
	 */
	public function setSourceCsvHostColumn($SourceCsvHostColumn)
	{
		if (empty($SourceCsvHostColumn) || !is_int($SourceCsvHostColumn)) {
			$this->registerMsg('Nie przekazano nazwy zdalnego hosta', true, true);
		}
		$SourceCsvHostColumn = $SourceCsvHostColumn - 1;
		$this->SourceCsvHostColumn = $SourceCsvHostColumn;
	}

	/**
	 * Ustawia hasło zdalnego użytkownika hosta na podstawie przekazanego numeru kolumny (1 - n..)
	 *
	 * @param int $SourceCsvHostPass
	 */
	public function setSourceCsvHostPass($SourceCsvHostPass)
	{
		if (empty($SourceCsvHostPass) || !is_int($SourceCsvHostPass)) {
			$this->registerMsg('Nie przekazano hasła zdalnego użytkownika hosta', true, true);
		}
		$SourceCsvHostPass = $SourceCsvHostPass - 1;
		$this->SourceCsvHostPass = $SourceCsvHostPass;
	}

	/**
	 * Ustawia nazwę użytkownika zdalnego hosta na podstawie przekazanego numeru kolumny (1 - n..)
	 *
	 * @param int $SourceCsvHostUser
	 */
	public function setSourceCsvHostUser($SourceCsvHostUser)
	{
		if (empty($SourceCsvHostUser) || !is_int($SourceCsvHostUser)) {
			$this->registerMsg('Nie przekazano nazwy użytkownika zdalnego hosta', true, true);
		}
		$SourceCsvHostUser = $SourceCsvHostUser - 1;
		$this->SourceCsvHostUser = $SourceCsvHostUser;
	}

	/**
	 * Dodanie do listy plików do wysłania na zdalny serwer
	 * (UWAŻAJ CO I GDZIE PRZESYŁASZ, NIE ZASTĄP ISTNIEJĄCEGO PLIKU NIEŚWIADOMIE)
	 *
	 * @param string $srcFilePath
	 * @param string $dstFilePath
	 *
	 * @return array
	 */
	public function setScpFilesArray($srcFilePath, $dstFilePath)
	{
		if (empty($srcFilePath) || empty($dstFilePath)) {
			$this->registerMsg('Przekazano nie poprawne dane do przesłania pliku', true, true);
		}

		$this->ScpFilesArray[] = array('src_file_path' => $srcFilePath, 'dst_file_path' => $dstFilePath);
	}

	/**
	 * Dodanie poleceń jakie będą wykonane na zdalnym serwerze
	 *
	 * @param string $cmd
	 *
	 * @return array
	 */
	public function setSshCommandsArray($cmd)
	{
		if (empty($cmd)) {
			$this->registerMsg('Przekazano nie poprawne polecenie do wykonania', true, true);
		}
		$this->SshCommandsArray[] = $cmd;
	}


	/**
	 * Uruchomienie poleceń na zdalnych serwerach
	 *
	 * @param bool $printOutput (wypisanie wyniku zdalnych poleceń)
	 * @param bool $exitOnRemoteError (zatrzymanie wykonywania poleceń jeśli wystąpi błąd na 1 serwerze)
	 */
	public function exec($printOutput, $exitOnRemoteError)
	{
		try {

			// walidacja ustawionej nazwy hosta, usera i hasła
			// (walidacja powinna być przed uruchomieniem całości)
			$this->validateInputParams();

			// uruchomienie poleceń
			foreach ($this->SourceCsvArray as $HostInfo) {

				// run cmd
				foreach ($this->SshCommandsArray as $cmd) {

					$this->execSsh(
						$HostInfo[$this->SourceCsvHostColumn],
						$HostInfo[$this->SourceCsvHostUser],
						$HostInfo[$this->SourceCsvHostPass],
						$cmd, $printOutput, $exitOnRemoteError);
				}

				// send file
				foreach ($this->ScpFilesArray as $filesArray) {

					$this->execScp(
						$HostInfo[$this->SourceCsvHostColumn],
						$HostInfo[$this->SourceCsvHostUser],
						$HostInfo[$this->SourceCsvHostPass],
						$filesArray, $printOutput, $exitOnRemoteError);
				}

				// debug
				break;
			}

			echo 'true' . PHP_EOL;
		}
		catch (Exception $e) {
			echo 'false' . PHP_EOL;
			$this->registerMsg('Exception: ' . $e->getMessage(), $printOutput, $exitOnRemoteError);
		}

	}

	/**
	 * Uruchomienie polecenia na zdalnym serwerze
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $cmd
	 * @param bool $printOutput
	 * @param bool $exitOnRemoteError
	 *
	 * @return array
	 * @throws Exception
	 */
	private function execSsh($host, $user, $pass, $cmd, $printOutput, $exitOnRemoteError)
	{
		$cmd = 'sshpass -p "' . $pass . '" ' . 'ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no ' .
			$user . '@' . $host . ' "' . $cmd . '" ';
		exec($cmd, $output, $return_var);
		$output = implode(PHP_EOL, $output);

		if (0 != $return_var) {
			$output = 'Uruchomienie zdalnego polecenia zakończyło się błędem (return: ' . $return_var .
				', polecenie: ' . $cmd . ', wynik polecenia: ' . $output;
		}
		$this->registerMsg('COMMAND: ' . $cmd, $printOutput, false);
		$this->registerMsg('OUTPUT: ' . $output, $printOutput, false);

		if (0 != $return_var && true === $exitOnRemoteError) {
			throw new Exception('Przerywam działanie.');
		}
	}

	/**
	 * Przesłanie pliku na zdalny serwer
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param array $filesArray array('src_file_path' => '', 'dst_file_path' => '')
	 * @param bool $printOutput
	 * @param bool $exitOnRemoteError
	 *
	 * @throws Exception
	 */
	private function execScp($host, $user, $pass, $filesArray, $printOutput, $exitOnRemoteError)
	{
		$cmd = 'sshpass -p "' . $pass . '" ' . 'scp -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no ' .
			$filesArray['src_file_path'] . $user . '@' . $host . ':' . $filesArray['dst_file_path'] . ' ';
		exec($cmd, $output, $return_var);
		$output = implode(PHP_EOL, $output);

		if (0 != $return_var) {
			$output = 'Przesłanie pliku na zdalny serwer zakończyło się błędem (return: ' . $return_var .
				', polecenie: ' . $cmd . ', wynik polecenia: ' . $output;
		}
		$this->registerMsg('COMMAND: ' . $cmd, $printOutput, false);
		$this->registerMsg('OUTPUT: ', $output, $printOutput, false);

		if (true === $exitOnRemoteError) {
			throw new Exception('Przerywam działanie.');
		}
	}


	/**
	 * Walidacja ustawionych numerów kolumn w pliku CSV oraz plików do przesłania
	 * @throws Exception
	 */
	private function validateInputParams()
	{
		foreach ($this->SourceCsvArray as $sourceCsvLineArray) {

			// wyszukanie nazwy hosta
			if (!is_int($this->SourceCsvHostColumn) || $this->SourceCsvHostColumn == 0) {
				throw new Exception('Podano nie poprawny numer kolumny dla zdalnego hosta ' . $this->SourceCsvHostColumn);
			}
			$key = $this->SourceCsvHostColumn - 1;
			if (false === isset($sourceCsvLineArray[$key])) {
				throw new Exception('Nie istnieje kolumna z podanym hostem w pliku csv, podano: ' . $this->SourceCsvHostColumn);
			}

			// wyszukanie nazwy usera
			if (!is_int($this->SourceCsvHostUser) || $this->SourceCsvHostUser == 0) {
				throw new Exception('Podano nie poprawny numer kolumny dla zdalnego użytkownika');
			}
			$key = $this->SourceCsvHostUser - 1;
			if (false === isset($sourceCsvLineArray[$key])) {
				throw new Exception('Nie istnieje kolumna z podanym hostem w pliku csv, podano: ' . $this->SourceCsvHostUser);
			}

			// wyszukanie hasła usera
			if (!is_int($this->SourceCsvHostPass) || $this->SourceCsvHostPass == 0) {
				throw new Exception('Podano nie poprawny numer kolumny dla hasła zdalnego użytkownika');
			}
			$key = $this->SourceCsvHostPass - 1;
			if (false === isset($sourceCsvLineArray[$key])) {
				throw new Exception('Nie istnieje kolumna z podanym hostem w pliku csv, podano: ' . $this->SourceCsvHostPass);
			}

		}

		// walidacja plików do przesłania
		foreach ($this->ScpFilesArray as $fileArray) {
			if (false === is_readable($fileArray['src_file_path'])) {
				throw new Exception('Nie istnieje źródłowy plik do przesłania: ' . $fileArray['src_file_path']);
			}
		}
	}
}
