<?php

/**
 * MK_Soap_Client_LiveDocX
 * 
 * Klasa do obsługi webservice LiveDOcX
 * Obsługa konwersji dokumentów z możliwością konwertowania całego katalogu
 *
 * @category	MK_Soap
 * @package		MK_Soap_Client_LiveDocX
 * @author		bskrzypkowiak
 */
class MK_Soap_Client_LiveDocX {

	private $_sourceDirectory = '';
	private $_destinationDirectory = '';
	private $_sourceFileExtension = 'docx';
	private $_destinationFileExtension = 'html';
	private $_writeToDb = false;

	//DIR_TEMP . DIRECTORY_SEPARATOR

	public function __construct() {
		$this->mailMerge = new Zend_Service_LiveDocx_MailMerge();
	}

	/**
	 * Utworzenie i zwrocenie połączenia do webservice
	 * 
	 * @param String $login
	 * @param String $password
	 * @return LiveDocX 
	 */
	public function connect($login='xxx', $password='xxx') {
		$this->mailMerge->setUsername($login)->setPassword($password);
		return $this;
	}

	/**
	 * Ustawianie katalogu docelowego gdzie wyladuja przekonwertowane pliki
	 * 
	 * @param String $value
	 * @return LiveDocX 
	 */
	public function setDestDir($value) {
		//@TODO isdir i tworzenie wrazie czego
		$this->_destinationDirectory = $value;
		return $this;
	}

	/**
	 * Ustawianie katalogu źródłowego
	 * 
	 * @param String $value
	 * @return LiveDocX 
	 */
	public function setSourceDir($value) {
		//@TODO isdir
		$this->_sourceDirectory = $value;
		return $this;
	}

	/**
	 * Ustawianie czy zapis ma być w pliku czy w bazie
	 * 
	 * @param String $destination 
	 */
	public function setDestination($destination='filesystem') {
		switch ($destination) {
			case 'filesystem' : $this->_writeToDb = false;
			case 'database' : $this->_writeToDb = true;
		}
	}

	/**
	 * Funkcja testująca
	 */
	public function testMe() {
		$this->connect()->convertDir(DIR_TEMP);
	}

	/**
	 * Funkcja konwertuje wszystkie pliki z podanego katalogu do ustawionego formatu wyjscuiowego
	 * 	 
	 * @param String $srcDir
	 * @param String $destDir 
	 */
	public function convertDir($srcDir, $destDir='html') {

		$this->setSourceDir($srcDir)
				->setDestDir($srcDir . DIRECTORY_SEPARATOR . $destDir);


		$iterator = new DirectoryIterator($this->_sourceDirectory);
		foreach ($iterator as $fileinfo) {
			if ($fileinfo->isFile()) {
				$file = $fileinfo->getFilename();
				//echo $fileinfo->getFilename() . "\n";
				if ($this->canConvert($file)) {
					$this->createDocument($file, $file);
				}
			}
		}
	}

	/**
	 * Dokonuje konwersji na poszczególnym dokumencie
	 * 
	 * @param String $sourceFileName
	 * @param String $destinationFileName
	 * @return LiveDocX 
	 */
	public function createDocument($sourceFileName, $destinationFileName='') {
		if (empty($destinationFileName)) {
			$destinationFileName = $this->getFileInfo($sourceFileName, 'filename');
		}
		if (!is_dir($this->_destinationDirectory)) {
			mkdir($this->_destinationDirectory);
		}

		$sourceFile = $this->_sourceDirectory . DIRECTORY_SEPARATOR . $sourceFileName;
		$destinationFile = $this->_destinationDirectory . DIRECTORY_SEPARATOR . $destinationFileName . '.' . $this->_destinationFileExtension;

		if ($this->canConvert($sourceFile)) {
			$this->mailMerge->setLocalTemplate($sourceFile);

			$this->mailMerge->assign(null);  // must be called as of phpLiveDocx 1.2
			$this->mailMerge->createDocument();
			$fileContent = $this->mailMerge->retrieveDocument($this->_destinationFileExtension);

			if ($this->_writeToDb) {
				$this->_helpDb = new HelpDb();
				$this->_helpDb->setValue($fileContent);
			} else {
				file_put_contents($destinationFile, $fileContent);
			}
		}

		return $this;
	}

	/**
	 * Sprawdza czy możliea jest konwersja podanego pliku (w zależnośći od rozszerzenia)
	 * 
	 * @param String $fileName
	 * @return Boolean 
	 */
	public function canConvert($fileName) {
		return ($this->getFileInfo($fileName) == $this->_sourceFileExtension);
	}

	/**
	 * Zwraca informację o pliku
	 * 
	 * @param String $filename
	 * @param String $val
	 * @return String 
	 */
	public function getFileInfo($filename, $val='extension') {
		$path_info = pathinfo($filename);
		return $path_info[$val];
	}

}
