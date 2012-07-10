<?php

/**
 * MK_Utils_ParseJsb2
 *
 * Klasa do tworzenia plików jsb2 wykorzystywanych przez plugin do Eclipse (Spket) w celu podpowiadania składni frameworków JavaScript
 *
 * @category    MK_Utils
 * @package        MK_Utils_ParseJsb2
 * @todo jest pare metod uzytych ze SPirbController nalezy sie ich pozbyc
 */
class MK_Utils_ParseJsb2
{

	/**
	 * @var
	 */
	private $_scriptPath;

	/**
	 * @var array
	 */
	private $_returnMessage;

	/**
	 * @var
	 */
	public $fileContent;

	/**
	 *
	 */
	public function __construct()
	{
		$this->_returnMessage = array();
		// Tworzenie pliku 'js/mkjs/mk.jsb2' dla ścieżki 'js/mkjs/mk/'
		$this->createJsb2File('js/mkjs/Mk.jsb2', 'js/mkjs/mk/', 'Madkom JS (Mk.*)', 'Mk.all.js');
		// Tworzenie pliku 'js/mkjs/libs/extjs/ext.jsb2' dla ścieżki 'js/mkjs/libs/extjs/'
		$this->createJsb2File('js/mkjs/libs/extjs/Ext.ux.jsb2', 'js/mkjs/libs/extjs/ux/', 'Ext JS (Ext.ux.*)', 'Ext.ux.all.js');
		// Tworzenie pliku 'js/spirb/spirb.jsb2' dla ścieżki 'js/spirb/'
		$this->createJsb2File('js/spirb/Spirb.jsb2', 'js/spirb/', 'SPiRB JS (Spirb.*)', 'Spirb.all.js');
		$this->returnJsonMessage();
	}

	public function foobar()
	{

	}

	/**
	 * Wyświetlenie komunikatu w jsonie
	 */
	public function returnJsonMessage()
	{
		if (count($this->_returnMessage)) {
			$retArray = $this->returnSuccessMessage(implode('<br/>', $this->_returnMessage));
		} else {
			$retArray = $this->returnErrorMessage('Nie utworzono żadnego pliku .jsb2');
		}

		exit(json_encode($retArray));
	}

	/**
	 *
	 * Odczytanie rozszerzenia danego pliku
	 *
	 * @param $fileName
	 *
	 * @internal param string $filename
	 * @return string
	 */
	public function getFileExtension($fileName)
	{
		$pi = pathinfo($fileName);
		return isset($pi['extension']) ? $pi['extension'] : '';
	}

	/**
	 *
	 * Odczytanie ścieżki skryptu (do pliku .jsb2)
	 *
	 * @param string $jsb2File
	 *
	 * @return string
	 */
	private function _getScriptPath($jsb2File)
	{
		$pi = pathinfo($jsb2File);
		return isset($pi['dirname']) ? $pi['dirname'] . '/' : '';
	}

	/**
	 *
	 * Sprawdzenie czy dany plik jest z roszerzeniem *.js
	 *
	 * @param $fileName
	 *
	 * @internal param string $filename
	 * @return Boolean
	 */
	private function _isJs($fileName)
	{
		if ($this->getFileExtension($fileName) == 'js') {
			return true;
		}
		return false;
	}

	/**
	 *
	 * Dopisanie pliku JS do zawartości .jsb2 (linia "fileIncludes")
	 *
	 * @param $fileName
	 * @param $filePath
	 *
	 * @internal param string $filename
	 * @return string
	 */
	private function _createJsb2Line($fileName, $filePath)
	{
		$filePath = str_replace($this->_scriptPath, '', $filePath);
		$jsb2Line = "\t\t\t" . '{' . PHP_EOL;
		$jsb2Line .= "\t\t\t\t" . '"text": "' . $fileName . '",' . PHP_EOL;
		$jsb2Line .= "\t\t\t\t" . '"path": "' . $filePath . '"' . PHP_EOL;
		$jsb2Line .= "\t\t\t" . '},' . PHP_EOL;
		return $jsb2Line;
	}

	/**
	 *
	 * Odczytanie listy plików (*.js)
	 *
	 * @param $dirPath
	 *
	 * @internal param string $path
	 */
	public function getJsFiles($dirPath)
	{
		$di = new DirectoryIterator($dirPath);
		foreach ($di as $fileInfo) {
			$path = $fileInfo->getPath();
			$fileName = $fileInfo->getFilename();
			$filePath = $path . '/' . $fileName;

			if (!$fileInfo->isDot() && $fileInfo->isDir() && $fileName != '.svn')
				$this->getJsFiles($filePath);
			if ($this->_isJs($fileName))
				$this->fileContent .= $this->_createJsb2Line($fileName, $path . '/');
		}
	}

	/**
	 *
	 * Tworzenie pliku jsb2
	 *
	 * @param $jsb2File
	 * @param $jsPath
	 * @param $pkgsName
	 * @param $pkgsFile
	 *
	 * @internal param string $path
	 */
	public function createJsb2File($jsb2File, $jsPath, $pkgsName, $pkgsFile)
	{

		// Pobranie listy plików *.js
		$this->fileContent = '';
		$this->_scriptPath = $this->_getScriptPath($jsb2File);
		$this->getJsFiles($jsPath);

		// Przygotowanie zawartości pliku .jsb2
		$jsb2 = '{' . PHP_EOL;
		$jsb2 .= "\t" . '"projectName": "Ext JS",' . PHP_EOL;
		$jsb2 .= "\t" . '"generatedDate": "' . date("Y-m-d H:i:s") . '",' . PHP_EOL;
		$jsb2 .= "\t" . '"pkgs": [{' . PHP_EOL;
		$jsb2 .= "\t\t" . '"name": "' . $pkgsName . '",' . PHP_EOL;
		$jsb2 .= "\t\t" . '"file": "' . $pkgsFile . '",' . PHP_EOL;
		$jsb2 .= "\t\t" . '"isDebug": "true",' . PHP_EOL;
		$jsb2 .= "\t\t" . '"fileIncludes": [' . PHP_EOL;
		$jsb2 .= substr($this->fileContent, 0, -2) . PHP_EOL;
		$jsb2 .= "\t\t" . ']' . PHP_EOL;
		$jsb2 .= "\t" . '}]' . PHP_EOL;
		$jsb2 .= '}' . PHP_EOL;

		// Generowanie pliku .jsb2
		//echo "<pre>{$jsb2}</pre>"; return false;
		file_put_contents($jsb2File, $jsb2);
		$this->_returnMessage[] = "Utworzono plik '{$jsb2File}' dla ścieżki '{$jsPath}'";
	}

}