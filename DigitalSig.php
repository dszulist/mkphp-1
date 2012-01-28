<?php
/**
 * MK_DigitalSig
 *
 * @TODO klasa do Opisania - brak komentarzy
 *
 * @category MK
 * @package	MK_DigitalSig
 */
class MK_DigitalSig {
	
	private $toSign = '';
	private $pfxFile = '';
	private $password = '';
    private $fileName = '';

    /**
     * Ścieżka do jar'a podpisującego
     * @var string
     */
    private $pathToJarSign = 'DigitalSig/DigitalSignature.jar';

    /**
     * Ścieżka do javy na srv
     * @var string
     */
    private $pathToJava = EXEC_JAVA;

    /**
     * Alias dla klucza (urzędu w certyfikacie (potrzebne do odczytania dancyh z certyfikatu))
     * @var string
     */
    private $keyAlias = '';

    /**
     * Zawiera weentualne komunikaty błędu podczas podpisywania
     * @var string
     */
    private $errorMsg = '';

    /**
     * Konstruktor
     *
     * @param null $toSign
     * @param null $pfxFile
     * @param null $password
     */
    public function __construct($toSign = null, $pfxFile = null, $password = null){
        $this->signingXmlBufforDirectory = MK_DIR_TEMP . DIRECTORY_SEPARATOR . 'signingXmlBuffor';

		if ($toSign !== null){
            $this->toSign = str_replace('"','\"',$toSign); //todo addslashes() ?
        }

		if ($pfxFile !== null){
            $this->pfxFile = $pfxFile;
        }
		if ($password !== null) {
            $this->password = $password;
        }

	}

    /**
     *
     * @throws Exception
     */
	private function checkParameters(){

        if (!is_dir($this->signingXmlBufforDirectory)){
            if (!mkdir($this->signingXmlBufforDirectory, 0775)){
                throw new Exception('Nie udało się stworzyć katalogu do przechowywania wersji roboczych podpisywanych plików xml');
            }
        }

		if (empty($this->toSign)){
			throw new Exception('Nie przekazano danych do podpisu');
		}
		
		if (empty($this->pfxFile)){
			throw new Exception('Nie przekazano pliku z kluczami');
		}
		
		if (empty($this->password)){
			throw new Exception('Nie przekazano hasła do klucza prywatnego');
		}

        if (empty($this->fileName)){
			throw new Exception('Nie przekazano nazwy pliku tymczasowego');
		}
		
		if(empty($this->pathToJarSign)){
			throw new Exception('Nie przekazano ścieżki do pliku jar');
		}
		
		if(empty($this->pathToJava)){
			throw new Exception('Nie przekazano ścieżki do javy na serwerze');
		}
		
		if(empty($this->keyAlias)){
			throw new Exception('Nie przekazano aliasu dla klucza');
		}
	}

    private function clear($tempFileName){
        if (is_file($tempFileName)){
            unlink($tempFileName);
        }
    }

    public function setTosig($canonizedXML){
        $this->toSign = $canonizedXML;
        return $this;
    }

    public function setFilename($name){
        $this->fileName = $name;
        return $this;
    }

    /**
     * Ustawia ściężkę do pliku JAR
     *
     * @param string $pathToJarSign
     * @return \MK_DigitalSig
     */
    public function setPathToJarSign($pathToJarSign){
    	$this->pathToJarSign = $pathToJarSign;
        return $this;
    }
    
    /**
     * Ustawia ścieżkę do javy na serwerze
     *
     * @param string $pathToJava
     * @return \MK_DigitalSig
     */
    public function setPathToJava($pathToJava){
    	$this->pathToJava = $pathToJava;
        return $this;
    }
    
    /**
     * Ustawia alias dla klucza
     *
     * @param string $keyAlias
     * @return \MK_DigitalSig
     */
    public function setKeyAlias($keyAlias){
    	$this->keyAlias = $keyAlias;
        return $this;
    }
    
    /**
     * Zwraca komunikat błędu jeśli wystąpił
     *
     * @return string
     */
    public function getErrorMsg(){
    	return $this->errorMsg;
    }
	
    /**
     * Podpisanie XMLa
     * TODO sparametryzować pozostale opcje do jar'a
     * 
     * @return string|bool
     */
	public function sign(){
	
		$bool = false;
		
		try {
			
			$this->checkParameters();
	        
			$tempFileName = $this->signingXmlBufforDirectory . DIRECTORY_SEPARATOR . $this->fileName;
	        if (file_put_contents($tempFileName, $this->toSign) === false){
	            throw new Exception('Nie udało się zapisać pliku tymczasowego do podpisu: ' . $tempFileName);
            }

			exec($this->pathToJava . ' -jar "' . $this->pathToJarSign . '" -in "' .$tempFileName. '" -sign -dsig -p12 "' .$this->pfxFile. '" -p12pass "' .$this->password. '" -keyalias '.$this->keyAlias.' 2>&1', $output, $returnCode);
			
			if ($returnCode != '0'){
				throw new Exception('Niepowiodło się podpisanie dokumentu, output:'.PHP_EOL.$output);
			}
			
			$output = file_get_contents($tempFileName);
	        $this->clear($tempFileName);
	        
			return $output;
		}
		catch (Exception $e){
			$this->errorMsg = $e->getMessage();
			$bool = false;
		}
		
		return $bool;
	}
}
