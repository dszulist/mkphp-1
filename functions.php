<?php

/**
 * Round fractions up
 * BC MATH (Binary Calculator - numbers of any size and precision, represented as strings)
 *
 * @param string $number
 * @return string
 */
function bcceil($number) {
	$number = rtrim($number, '.0');
	if (strpos($number, '.') !== false) {
		if ($number[0] != '-') {
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
 * @return string
 */
function bcfloor($number) {
	$number = rtrim($number, '.0');
	if (strpos($number, '.') !== false) {
		if ($number[0] != '-') {
			return bcadd($number, 0, 0);
		}
		return bcsub($number, 1, 0);
	}
	return $number;
}

/**
 * Rounds a bc-string-value half up
 * BC MATH (Binary Calculator - numbers of any size and precision, represented as strings)
 *
 * @param string $number
 * @param integer $precision (default:0)
 * @return string
 */
function bcround($number, $precision = 0) {
	$number = rtrim($number, '.0');
	if (strpos($number, '.') !== false) {
		if ($number[0] != '-') {
			return bcadd($number, '0.' . str_repeat('0', $precision) . '5', $precision);
		}
		return bcsub($number, '0.' . str_repeat('0', $precision) . '5', $precision);
	}
	return $number;
}

/**
 * Funkcja bezpieczeństwa - sprawdzenie czy plik istnieje w projekcie
 *
 * @param string $filePath
 * @param string $appPath
 * @return boolean
 */
function file_exists_in_app($filePath, $appPath = '') {
	if (empty($appPath)) {
		if (!defined('APP_PATH')) {
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
function validate_directory($dirPath) {
	if (!file_exists($dirPath) || !is_dir($dirPath)) {
		if (!@mkdir($dirPath, MK_CHMOD_DIR, true)) {
			exit('Nie można utworzyć katalogu ' . $dirPath);
		}
	}
}

/**
 * Podgląd danych w wybranej metodzie print_r/var_dump
 *
 * @param mixed $data - dane do wyświetlenia, może być string/array/object
 * @param boolean $throwException (default: true) - domyślnie wyrzuca wyjątek MK_Exception()
 * @param string $method (default: print_r) - domyślnie zwraca wynik przy użyciu funkcji print_r
 * @throws MK_Exception
 */
function printr($data, $throwException=true, $method='print_r') {

	switch ($method) {
		case 'var_dump':
			ob_start();
			var_dump($data);
			$dump = ob_get_contents();
			ob_end_clean();
			break;
		case 'var_export':
			ob_start();
			var_export($data);
			$dump = ob_get_contents();
			ob_end_clean();
			break;
		default:
			$dump = print_r($data, true);
			break;
	}

	$output = "<pre>{$dump}</pre>";

	if ($throwException) {
		throw new MK_Exception($output);
	} else {
		echo $output;
	}
}