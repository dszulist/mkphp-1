<?php

/**
 * Round fractions up
 * BC MATH (Binary Calculator - numbers of any size and precision, represented as strings)
 *
 * @param string $number
 *
 * @return string
 */
function bcceil($number)
{
    $number = rtrim($number, '.0');
    if(strpos($number, '.') !== false) {
        if($number[0] != '-') {
            return bcadd($number, 1, 0);
        }
        return bcsub($number, 0, 0);
    }
    return $number;
}

/**
 * Round fractions down
 * BC MATH (Binary Calculator - numbers of any size and precision, represented as strings)
 *
 * @param string $number
 *
 * @return string
 */
function bcfloor($number)
{
    $number = rtrim($number, '.0');
    if(strpos($number, '.') !== false) {
        if($number[0] != '-') {
            return bcadd($number, 0, 0);
        }
        return bcsub($number, 1, 0);
    }
    return $number;
}

/**
 * Rounds a bc-string-value half up
 * BC MATH (Binary Calculator - numbers of any size and precision, represented as strings)
 * $number = '120.00';  bcround($number, 2) = 120.00;
 * $number = '120.5555';  bcround($number, 2) = 120.56;
 * $number = '120,5555';  bcround($number, 2) = 120.56;
 *
 * @param string $number np 120.00 , 120.5555, 10,9989393
 * @param integer $precision (default:0)
 *
 * @return string
 */
function bcround($number, $precision = 0)
{
    if(strpos($number, ',') !== false) {
        $number = str_replace(array ('.', ','), array ('', '.'), $number);
    }
    if(false !== ($pos = strpos($number, '.')) && (strlen($number) - $pos - 1) > $precision) {
        $zeros = str_repeat("0", $precision);
        return bcadd($number, "0.{$zeros}5", $precision);
    } else {
        return $number;
    }
}

/**
 * Funkcja bezpieczeństwa - sprawdzenie czy plik istnieje w projekcie
 *
 * @param string $filePath
 * @param string $appPath
 *
 * @return boolean
 */
function file_exists_in_app($filePath, $appPath = '')
{
    if(empty($appPath)) {
        if(!defined('APP_PATH')) {
            trigger_error('Undefined argument $appPath in function ' . __FUNCTION__ . '() OR constant APP_PATH', E_USER_ERROR);
        }
        $appPath = APP_PATH;
    }
    return strcmp($appPath, substr(realpath($filePath), 0, strlen($appPath))) === 0;
}

/**
 * Sprawdzamy i tworzymy katalogi wymagane przez aplikację
 *
 * @param string $dirPath
 */
function validate_directory($dirPath)
{
    if(!empty($dirPath) && $dirPath !== '..' && $dirPath !== '.') {
        if(!file_exists($dirPath) || !is_dir($dirPath)) {
            if(!mkdir($dirPath, MK_CHMOD_DIR, true)) {
                exit("Nie można utworzyć katalogu {$dirPath}");
            }
        }
    }
}

/**
 * Blokuje dostęp do katalogu za pomocą pliku .hteccess.
 *
 * Sprawdza czy istnieje plik .htaccess w podanym katalogu jeżeli nie to :
 * Sprawdza czy istnieje podany katalog jeżeli nie to tworzy go i tworzy w nim plik .htaccess
 * Jeżeli utworzy plik htacces zwróci true, w przeciwnym wypadku false
 *
 * @param string $dirPath
 *
 * @return bool
 */
function block_directory_htaccess($dirPath)
{
    $file = $dirPath . DIRECTORY_SEPARATOR . '.htaccess';
    if(!file_exists($file)) {
        validate_directory($dirPath);
        file_put_contents($file, "Order Deny,Allow " . PHP_EOL . "Deny from all " . PHP_EOL . "Allow from 127.0.0.1 " . PHP_EOL);
        return true;
    }
    return false;
}

/**
 * Podgląd danych w wybranej metodzie print_r/var_dump
 *
 * @param mixed $data - dane do wyświetlenia, może być string/array/object
 * @param boolean $throwException (default: true) - domyślnie wyrzuca wyjątek MK_Exception()
 * @param string $method (default: print_r) - domyślnie zwraca wynik przy użyciu funkcji print_r
 *
 * @throws MK_Exception
 */
function printr($data, $throwException = true, $method = 'print_r')
{
    switch ($method) {
        case 'var_dump':
            ob_start();
            var_dump($data);
            $output = ob_get_contents();
            ob_end_clean();
            break;
        case 'var_export':
            ob_start();
            var_export($data);
            $output = ob_get_contents();
            ob_end_clean();
            break;
        default:
            $output = '<pre>' . print_r($data, true) . '</pre>';
            break;
    }

    if($throwException) {
        throw new MK_Exception($output);
    } else {
        echo $output;
    }
}

/**
 * Usuwa katalog rekurencyjnie z jego plikami
 *
 * @param $dir
 *
 * @return bool
 */
function removeDir($dir)
{
    if(!file_exists($dir)) {
        return true;
    }

    if(!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if($item == '.' || $item == '..') {
            continue;
        }
        if(!removeDir($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return @rmdir($dir);
}

/**
 * Alternatywna definicja funkcji z PHP >= 5.2.6
 */
if(!function_exists('sys_get_temp_dir')) {
    function sys_get_temp_dir()
    {
        if($temp = getenv('TMP')) {
            return $temp;
        }
        if($temp = getenv('TEMP')) {
            return $temp;
        }
        if($temp = getenv('TMPDIR')) {
            return $temp;
        }
        $temp = tempnam(__FILE__, '');
        if(file_exists($temp)) {
            unlink($temp);
            return dirname($temp);
        }
        return null;
    }
}