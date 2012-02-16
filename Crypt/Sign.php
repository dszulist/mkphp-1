<?php
/**
 * MK_Crypt_Sign
 *
 * Umożliwia podpisywanie plików, za pomocą aplikacji JAVA ;)
 *
 * @category MK
 * @package	MK_Crypt_Sign
 * @author bskrzypkowiak
 */
class MK_Crypt_Sign {

	/**
	 * Scieżka do pliku jar
	 * @var #DMK_PATH|string
	 */
	protected $jarFilePath = MK_PATH;

	/**
	 * Rodzaj podpisu (dsig|xades|xadest)
	 * @var string
	 */
	private $type = '-dsig';

	/**
	 * Hasło do keystore
	 * @var string
	 */
	private $kspass = '';

	/**
	 * Alias w keystore
	 * @var string
	 */
	private $keyalias = '';

	/**
	 * Port do HSM'a
	 * @var string
	 */
	private $hsmslot = '';

	/**
	 * Plik źródłowy, który chcemy podpisać
	 * @var string
	 */
	private $input = '';

	/**
	 * Plik wyjściowy podpisu
	 * @var string
	 */
	private $output = '';

	/**
	 * Plik pkcs12 którym będziemy podpisywać
	 * @var string
	 */
	private $pkcs12 = '';

	/**
	 * Adres to serwera ze znacznikiem czasu (url)
	 * @var string
	 */
	private $timeserver = '';

	/**
	 * Lista plików dłączonych do podpisu
	 * @var string
	 */
	private $reflist = '';

	/**
	 * Scieżka do tymczasowego katalogu
	 * @var string
	 */
	private $tempDir = DIR_TEMP;

	/**
	 * Lista utworzonych plików tymczaoswych
	 * @var array
	 */
	private $tempFileList = array();

	/**
	 * Ustawia scieżki do plików i katalogów
	 * Sprawdza i tworzy katalog tymczasowy jeżeli nie istnieje
	 *
	 * @throw Exception
	 */
	public function __construct(){
	    $this->jarFilePath .= DIRECTORY_SEPARATOR . 'Crypt' . DIRECTORY_SEPARATOR . 'Sign' . DIRECTORY_SEPARATOR . 'jar' . DIRECTORY_SEPARATOR . 'Signer.jar';
		$this->tempDir .= DIRECTORY_SEPARATOR . 'MK_Crypt_Sign' . DIRECTORY_SEPARATOR . uniqid();

		validate_directory($this->tempDir);
	}

	/**
	 * Uruchamia proces podpisywania
	 *
	 * @throw Exception
	 * @return string
	 */
	public function run(){
		
		$output = null;

		try {
			if(APP_DEBUG){
				if(empty($this->pkcs12) && empty($this->hsmslot)){
					throw new Exception('Nie podano magaznu certyfikacji. Użyj: usePkcs12(); | useHSM();');
				}

				if(empty($this->input)){
					throw new Exception('Nie podano żródła do podpisania. Użyj: setInput();');
				}

				if(empty($this->kspass)){
					throw new Exception('Nie podano hasła. Użyj: setKeyStorePassword();');
				}

				if(empty($this->keyalias)){
					throw new Exception('Nie podano aliasu. Użyj: setKeyStoreAlias();');
				}
			}
			$cmd = EXEC_JAVA .
				   " -Xmx512m -jar {$this->jarFilePath} -sign " .
				   "{$this->type} {$this->kspass} {$this->keyalias} {$this->pkcs12} {$this->hsmslot} {$this->input} {$this->output} {$this->timeserver} {$this->reflist} 2>&1";

			exec($cmd, $output, $returnCode);
			
			if ($returnCode != '0'){
				throw new Exception("Błąd polecenia: '{$cmd}' Wynik: {$output}");
			}

			if(!empty($this->output)){
				$output = file_get_contents(str_replace('-out ', '', $this->output));
			}
			else {
				$output = file_get_contents(str_replace(array('-in ', "'"), '', $this->input));
			}

		}
		catch (Exception $e){
			throw new Exception("Nie można podpisać pliku. " . (APP_DEBUG) ? MK_EOL . $e->getMessage() : '');
		}

		return $output;
	}

	/**
	 * Sprawdza czy podany typ jest prawidłowy
	 *
	 * @param String $val
	 * @return bool
	 */
	private function isValidType($val){
		return in_array($val, array('dsig', 'xades', 'xadest'));
	}

	/**
	 * Tworzy tymczasowy plik i zwraca sciezke do niego
	 *
	 * @param string $fileName
	 * @param string $content
	 *
	 * @return string - scieżka do utworzonego pliku
	 */
	private function createTempFile($fileName, $content){
		$fileName = $this->tempDir . DIRECTORY_SEPARATOR . $fileName;
		file_put_contents($fileName, $content);
		$this->tempFileList[] = $fileName;
		return $fileName;
	}

	/**
	 * Ustawia typ/rodzaj podpisu
	 *
	 * @param String $val
	 *
	 * @throw Exception
	 * @return MK_Crypt_Sign
	 */
	public function setType($val){
		if(!$this->isValidType($val)){
			throw new Exception('Podano nieprawidłowy typ podpisu');
		}
		$this->type = "-{$val}";
		return $this;
	}

	/**
	 * Ustawia hasło dostępu do miejsa w którym jest przechowywany klucz
	 *
	 * @param String $val
	 * @return MK_Crypt_Sign
	 */
	public function setKeyStorePassword($val){
		$this->kspass = "-kspass {$val}";
		return $this;
	}

	/**
	 * Ustawia nazwe klucza
	 *
	 * @param String $val
	 * @return MK_Crypt_Sign
	 */
	public function setKeyStoreAlias($val){
		$this->keyalias = "-keyalias {$val}";
		return $this;
	}

	/**
	 * Ustawia miejsce przetrzymywania podpisów na HSM, oraz ustawia nr slotu HSM w którym się znajduje
	 *
	 * @param integer $slot
	 * @return MK_Crypt_Sign
	 */
	public function useHSM($slot){
		$this->hsmslot = "-hsm -slot {$slot}";
		$this->pkcs12 = '';
		return $this;
	}

	/**
	 * Ustawia żródło do pliku pksc12
	 *
	 * @param String $val
	 * @return MK_Crypt_Sign
	 */
	public function usePKCS12($val){
		$this->pkcs12 = "-pkcs12 {$val}";
		$this->hsmslot = '';
		return $this;
	}

	/**
	 * Ustawia ardes do serwera znacznika czasu
	 *
	 * @param String $val (URL lub IP)
	 * @return MK_Crypt_Sign
	 */
	public function useTimeStampServer($val){

		if(!MK_Validator::urlOrIp($val)){
			throw new Exception("Niepoprawny adres serwera znacznika czasu. {$val}");
		}
		$this->timeserver = "-tsaurl {$val}";
		return $this;
	}

	/**
	 * Ustawia ścieżke do pliku który chemy podpisać, lub tworzy odpowiedni plik na podstawie treści przekazanej do funkcji
	 *
	 * @param String $val - sciezka do pliku lub jego zawartość
	 * @param bool $fromFile - czy z pliku czy z treści
	 *
	 * @throw Exception
	 * @return MK_Crypt_Sign
	 */
	public function setInput($val, $fromFile=false){

		if($fromFile === false){
			$val = $this->createTempFile(uniqid() . '.tosig', $val);
		}
		else if(!file_exists($val)){
			throw new Exception("Plik do podpisania ({$val}) nie istnieje.");
		}

		$this->input = "-in '{$val}'";

		return $this;
	}

	/**
	 * Ustawia scieżke do pliku w którym ma zostać zapisany podpis
	 *
	 * @param String $val
	 * @return MK_Crypt_Sign
	 */
	public function setOutput($val){
		$this->output = '-out ' . $this->tempDir . DIRECTORY_SEPARATOR . $val;
		return $this;
	}

	/**
	 * Ustawia liste plików dołączanych do podpisu
	 *
	 * @param Mixed $val - sciezka do pliku \ sciezki do plików oddzielone przecinkami \ scieżki do plików w tablicy \ nazwy plików w kluczach i treść w wartościach tablicy
	 * @return MK_Crypt_Sign
	 */
	public function setRefList($val){
		if(empty($val)){
			return $this;
		}
		if(is_array($val)){
			if(count(array_filter(array_keys($val), 'is_string')) == count($val)){
				$tmp = array();
				foreach ($val as $fileName => $content) {
					$tmp[] = $this->createTempFile($fileName, $content);
				}
				$val = $tmp;				
			}
			$val = implode(",", $val);
		}

		$this->reflist = "-reflist {$val}";
		return $this;
	}

	/**
	 * Destruktor klasy ustawia właściwości na stan początkowy
	 * Usuwa pliki stworzone na potrzebe podpisu
	 *
	 * @throw Exception
	 */
	public function __destruct(){

		$this->type = '-dsig';
		$this->kspass = '';
		$this->keyalias = '';
		$this->hsmslot = '';
		$this->input = '';
		$this->output = '';
		$this->pkcs12 = '';
		$this->timeserver = '';
		$this->reflist = '';

		try {
			foreach($this->tempFileList as $fileName){
				unlink($fileName);
			}
			unset($fileName);
			rmdir($this->tempDir);
		}
		catch(Exception $e){
			throw new Exception("Nie udało się usunąć plików tymczasowych podpisu");
		}
	}

}
