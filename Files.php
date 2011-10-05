<?php
/**
 * Files
 *
 * Klasa do obsługi plików/katalogów
 * Bazowane na zalacznik_helper z DOcFlow
 *
 * @category	Mkphp
 * @package	Files
 * @author	bskrzypkowiak
 */
class Files {

    /**
     * metoda, ktora zwraca dzien, miesiac lub rok
     * z daty wg wzoru YYYY-mm-dd hh:mm:ss
     *
     * metoda wykorzystywana przy tworzeniu sciezki dostepu
     * do zalacznikow
     */
    function splitDate($attachment_date, $format_date) {

        $subdate = explode('-', $attachment_date, 3);
        unset($attachment_date);
        switch ($format_date) {
            //day
            case 'd':
                return substr($subdate[2], 0, 2);
                break;
            //month
            case 'm':
                return $subdate[1];
                break;
            //year
            case 'y':
                return $subdate[0];
                break;
        }
    }

    /**
     * zamienia date utworzenia zalacznika
     * na odpowiednia sciezke dostepu
     * wg schematu
     *
     * '/' .$rok .'/' . $miesiac. '/' .$dzien .'/'
     */
    function parseCreatedateToPath($data_utworzenia_zal) {
        //dodane ze wzgledu na mozliwosc przekazania DBTimeStamp
        //zwracanego z apostrofami na poczatku i koncu ciagu daty
        $data_utworzenia_zal = str_replace("'", "", $data_utworzenia_zal);
        //poczatek obslugi daty utworzenia zalacznika jako elementu sciezki dostepu
        $dzien = $this->splitDate($data_utworzenia_zal, 'd');
        $miesiac = $this->splitDate($data_utworzenia_zal, 'm');
        $rok = $this->splitDate($data_utworzenia_zal, 'y');
        $sciezka_zal_data_utworzenia = DIRECTORY_SEPARATOR . $rok . DIRECTORY_SEPARATOR . $miesiac . DIRECTORY_SEPARATOR . $dzien . DIRECTORY_SEPARATOR;

        return $sciezka_zal_data_utworzenia;
        //koniec obslugi daty utworzenia zalacznika jako elementu sciezki dostepu
    }

    /**
     * metoda tworzy katalogi dla zalacznikow lub plikow tymczasowych
     * wedlug podanych sciezek
     */
    function createDirsByCreatedate($glowny_kat, $data_utworzenia_zal) {

        //dodane ze wzgledu na mozliwosc przekazania DBTimeStamp
        //zwracanego z apostrofami na poczatku i koncu ciagu daty
        $data_utworzenia_zal = str_replace("'", "", $data_utworzenia_zal);
        //poczatek obslugi daty utworzenia zalacznika jako elementu sciezki dostepu
        $dzien = $this->splitDate($data_utworzenia_zal, 'd');
        $miesiac = $this->splitDate($data_utworzenia_zal, 'm');
        $rok = $this->splitDate($data_utworzenia_zal, 'y');
        $files_dest_dir = $glowny_kat . DIRECTORY_SEPARATOR . $rok . DIRECTORY_SEPARATOR . $miesiac . DIRECTORY_SEPARATOR . $dzien;
        if (!@file_exists($files_dest_dir)) {
            if (!@file_exists($glowny_kat)) {
                @mkdir($glowny_kat, 0775);
            }
            if (!@file_exists($glowny_kat . DIRECTORY_SEPARATOR . $rok)) {
                @mkdir($glowny_kat . DIRECTORY_SEPARATOR . $rok, 0775);
            }
            if (!@file_exists($glowny_kat . DIRECTORY_SEPARATOR . $rok . DIRECTORY_SEPARATOR . $miesiac)) {
                @mkdir($glowny_kat . DIRECTORY_SEPARATOR . $rok . DIRECTORY_SEPARATOR . $miesiac, 0775);
            }
            if (!@file_exists($glowny_kat . DIRECTORY_SEPARATOR . $rok . DIRECTORY_SEPARATOR . $miesiac . DIRECTORY_SEPARATOR . $dzien)) {
                @mkdir($glowny_kat . DIRECTORY_SEPARATOR . $rok . DIRECTORY_SEPARATOR . $miesiac . DIRECTORY_SEPARATOR . $dzien, 0775);
            }
        }

        unset($dzien);
        unset($miesiac);
        unset($rok);
    }

    /**
     * 	Wykrywa kodowanie zawartości plików tekstowych
     * 	z wykorzystaniem polecenia uniksowego file
     * 	TODO - w PHP 5.3 lub PHP 5.2 wykorzystać rozszerzenie FileInfo
     * 	w PHP 5.2 jest instalowane z PECLa, w PHP 5.3 jest standardowym rozszerzeniem
     * 	@var string filePath
     *
     * 	$return string
     */
    public function detectTextFileContentEncoding($filePath) {
        $detectedCharset = null;
        ob_start();
        @passthru('file -bi ' . $filePath);
        $output[] = ob_get_contents();
        ob_end_clean();

        //pobierz pierwszy wiersz wyniku
        //wyciagnij wszystkie dane po charset=
        if (count($output) > 0) {
            $firstLine = $output[0];
            $pattern = '/charset=([^ ]*)/';
            $searchingResult = preg_match($pattern, $firstLine, $matches);
            if ($searchingResult != FALSE) {
                if (isset($matches[1])) {
                    $detectedCharset = trim($matches[1]);
                    //polecenie file nie wykrywa kodowania CP1250
                    //natomiast wypisuje unknown
                    if ($detectedCharset == 'unknown') {
                        $detectedCharset = 'windows-1250';
                    }
                }
            }
        }
        return strtolower($detectedCharset);
    }
    

    /**
     * czy nazwa danego zalacznika juz istnieje(czy nie jest zdublowana)
     * 
     * @param string name (nazwa zalacznika)
     * @param date create_date	(data utworzenia zalacznika)
     * @param string directory (glowny katalog)
     * @param array $sfile_array (tablica z plikami - sesyjna)
     * @param string form_uid (uid formularza)
     */
    function isFileNameDuplicated($name, $create_date, $directory, array $sfile_array, $form_uid) {

        $file = $directory . $this->parseCreatedateToPath($create_date) . $form_uid . DIRECTORY_SEPARATOR . $name;
        if (is_file($file)) {
            return true;
        }
        else {
            return $this->multi_array_search($name, $sfile_array);
        }
    }

    function multi_array_search($search_value, $the_array) {
        if (is_array($the_array)) {
            foreach ($the_array as $key => $value) {
                $result = $this->multi_array_search($search_value, $value);
                if ($result == true) {
                    return true;
                }
            }
            return false;
        } else {
            if (strcmp($search_value, $the_array) == 0) {
                return true;
            }
            else
                return false;
        }
    }

    /**
     * konwert zalacznika do JPG
     *
     * @param string typ
     */
    function convertFileToJPG($path, $type) {
        switch ($type) {
            case 'tiff': case 'tif':
                if (strpos($_SERVER["SERVER_SOFTWARE"], 'Win')) {
                    define('CONVERT_IMG_BIN_DIR', 'C:\\usr\\ImageMagick\\');
                }
                if ((strpos($_SERVER["SERVER_SOFTWARE"], 'Debian')) || (strpos($_SERVER["SERVER_SOFTWARE"], 'Linux'))) {
                    define('CONVERT_IMG_BIN_DIR', '/usr/bin/');
                }
                if (!is_dir(CONVERT_IMG_BIN_DIR)) {
                    return false;
                }
                if (file_exists(getcwd() . DIRECTORY_SEPARATOR . $path)) {
                    if (system(CONVERT_IMG_BIN_DIR . "convert " . getcwd() . DIRECTORY_SEPARATOR . $path . " " . getcwd() . DIRECTORY_SEPARATOR . $path . ".jpg") === false
                            || system(!CONVERT_IMG_BIN_DIR . "convert " . getcwd() . DIRECTORY_SEPARATOR . $path . ".jpg " . getcwd() . DIRECTORY_SEPARATOR . $path) === false){
                        return false;
                    }
                    else {
                        unlink($path . ".jpg");
                    }
                }
                break;
            case 'gif' :
                $im = imagecreatefromgif($path);
                $x = imagesx($im);
                $y = imagesy($im);
                $png = imagecreatetruecolor($x, $y);
                $bg = imagecolorallocate($png, 255, 255, 255);
                imagefill($png, 0, 0, $bg);
                imagecopyresized($png, $im, 0, 0, 0, 0, $x, $y, $x, $y);
                imagejpeg($png, $path);
                if (!isset($im)){
                    return false;
                }
                break;
            case 'png' :
                $im = imagecreatefrompng($path);
                $x = imagesx($im);
                $y = imagesy($im);
                $png = imagecreatetruecolor($x, $y);
                $bg = imagecolorallocate($png, 255, 255, 255);
                imagefill($png, 0, 0, $bg);
                imagecopyresized($png, $im, 0, 0, 0, 0, $x, $y, $x, $y);
                imagejpeg($png, $path);
                if (!isset($im)) {
                    return false;
                }
                break;
            default:
                return true;
        }
        
        $_FILES['plik']['name'] = substr($_FILES['plik']['name'], 0, -(strlen($type))) . "jpg";
        
        return true;
    }

    /**
     * Stworzenie pliku Zip z plikami z danego dokumentu
     *
     * @param array $arrayPdf - lista wraz z zawartoscia plikow dokumentow
     * @return string - sciezka to tymczasowego pliku
     */
    public static function packAllContents(array $arrayDocs) {

        if (empty($arrayDocs)){
            return false;
        }

        $tmpFileZipFolder = DIR_TEMP . DIRECTORY_SEPARATOR . uniqid('', TRUE);

        if (!file_exists($tmpFileZipFolder)) {
            mkdir($tmpFileZipFolder);
        }
        $tmpFileZip = $tmpFileZipFolder . DIRECTORY_SEPARATOR . uniqid('') . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($tmpFileZip, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE);

        if ($res === true) {
            foreach ($arrayDocs as $fileToPack) {
                $info = new SplFileInfo($fileToPack);
                if (!$zip->addFile($fileToPack, $info->getFilename())) {
                    throw new Exception('Nie powiodło się stworzenie paczki');
                }
            }
            $zip->close();
            return $tmpFileZip;
        } else {
            throw new Exception('Nie powiodło się stworzenie paczki');
        }
    }

    /**
     * Zapis tymczasowego pliku xmlowego
     */
    public static function saveTempXMLFile($fileNameToSave, $dataToSave) {

        $tempPath = '';
        try {

            //utworzenie ścieżki wg schematu
            //temp/webservice/adres_ip_klienta/'DocflowWebServiceService.wsdl'
            //$_SERVER["REMOTE_ADDR"]
            $tempWebServiceDefinitionFolder = DIR_TEMP . DIRECTORY_SEPARATOR . 'webservice' . DIRECTORY_SEPARATOR . uniqid('', TRUE);

            if (!file_exists($tempWebServiceDefinitionFolder)) {
                mkdir($tempWebServiceDefinitionFolder, 0775, TRUE);
            }

            $tempPath = $tempWebServiceDefinitionFolder . DIRECTORY_SEPARATOR . $fileNameToSave;

            $xml = new SimpleXMLElement(
                            $dataToSave,
                            LIBXML_COMPACT, FALSE);

            //zapisanie danych do pliku
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            $dom->save($tempPath);
            $xml = null;
        } catch (DOMException $ex) {
            throw new Exception("Wystąpił problem z generowaniem pliku eksportu danych", 1002);
        } catch (Exception $ex) {
            throw new Exception("Wystąpił problem z generowaniem pliku eksportu danych", 1002);
        }
        return $tempPath;
    }

    /**
     * Metoda służąca do usuwania tymczasowego pliku
     * razem z plikiem
     */
    public static function removeTemporaryFileWithParentDirectory($pathToTemporaryPath) {
        try {
            $tempWebServiceDefinitionPathFileInfo = new SplFileInfo($pathToTemporaryPath);
            $temporaryFileParentDirectory = $tempWebServiceDefinitionPathFileInfo->getPath();
            $tempWebServiceDefinitionPathFileInfo = null;

            //var_dump($temporaryFileParentDirectory);die;
            if (file_exists($pathToTemporaryPath)) {
                unlink($pathToTemporaryPath);
                rmdir($temporaryFileParentDirectory);
            }
        } catch (Exception $ex) {
            throw new Exception("Wystąpił problem z usuwaniem tymczasowego pliku", 1001);
        }
    }

}