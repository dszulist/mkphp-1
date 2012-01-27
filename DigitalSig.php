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
    
    private $pathToJarSign = '';
    private $pathToJava = '';
    private $keyAlias = '';
    
    public $_errorMsg = '';
    
    CONST TEMP = 'temp';

    /**
     * Konstruktor
     *
     * @param null $toSign
     * @param null $pfxFile
     * @param null $password
     */
    public function __construct($toSign = null, $pfxFile = null, $password = null){
        $this->signingXmlBufforDirectory = self::TEMP . DIRECTORY_SEPARATOR . 'signingXmlBuffor';

		if ($toSign !== null){
            $this->toSign = str_replace('"','\"',$toSign);
        }
		if ($pfxFile !== null){
            $this->pfxFile = $pfxFile;
        }
		if ($password !== null) {
            $this->password = $password;
        }

	}

    /**
     * @param $name
     * @param $value
     */
	public function set($name, $value){
	
		if (isset($this->{$name})){
			$this->{$name} = $value;
		}
		
	}
	
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
    
    /**
     * Ustawia ściężkę do pliku JAR
     *
     * @param string $pathToJarSign
     */
    public function setPathToJarSign($pathToJarSign){
    	$this->pathToJarSign = $pathToJarSign;
    }
    
    /**
     * Ustawia ścieżkę do javy na serwerze
     *
     * @param string $pathToJava
     */
    public function setPathToJava($pathToJava){
    	$this->pathToJava = $pathToJava;
    }
    
    /**
     * Ustawia alias dla klucza
     *
     * @param string $keyAlias
     */
    public function setKeyAlias($keyAlias){
    	$this->keyAlias = $keyAlias;
    }
    
    /**
     * Zwraca komunikat błędu jeśli wystąpił
     *
     * @return string
     */
    public function getErrorMsg(){
    	return $this->_errorMsg;
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
			$this->_errorMsg = $e->getMessage();
			$bool = false;
		}
		
		return $bool;
	}
}