<?php

/**
 * Validator
 *
 * Weryfikacja danych
 *
 * @todo przerobić chociaż cześć na użycie Klas z Zend_Validator
 *
 * @category MK
 * @package	MK_Validator
 */
class MK_Validator {

	/**
	 * Sprawdza czy podany klucz istnieje w podanej tablicy
	 *
	 * @static
	 * @param string $argName - szukany klucz
	 * @param array $args - tabela w ktorej sukamy klucza
     * @return bool
     */
	public static function isDefined($argName, array $args) {
		return isset($args[$argName]);
	}

	/**
	 * Sprawdza czy podany klucz istnieje w podanej tablicy i czy jest wiekszy od 0
	 *
	 * @static
	 * @param string $argName - szukany klucz
	 * @param array $args - tabela w ktorej sukamy klucza
     * @return bool
     */
	public static function isNotEmpty($argName, array $args) {
		return (self::isDefined($argName, $args) && !empty($args[$argName]));
	}

	/**
	 * Sprawdza czy podany klucz istnieje w podanej tablicy i czy wartosc w tablicy dla tego klucza iset numeric
	 *
	 * @static
	 * @param string $argName
	 * @param array $args
     * @return bool
     */
	public static function isNumeric($argName, array $args) {
		return (self::isDefined($argName, $args) && is_numeric($args[$argName]));
	}

	/**
	 * Sprawdza czy podany klucz istnieje w podanej tablicy i czy wartosc w tablicy dla tego klucza jest typu integer
	 *
	 * @static
	 * @param string $argName
	 * @param array $args
     * @return bool
     */
	public static function integerArgument($argName, array $args) {
		return (self::isDefined($argName, $args) && is_numeric($args[$argName]));
	}

	/**
	 * Sprawdza czy podany argument jest integere'm i jest większy od zera
	 *
	 * @static
	 * @param string $argName - nazwa sprawdzanego argumentu
	 * @param array $args - tablica, której powinien znajdować się parametr
	 * @param boolean $canBeZero - czy wartość może być zerem
	 *
	 * @return boolean
	 */
	public static function positiveIntegerArgument($argName, array $args, $canBeZero = false) {

		$isValid = self::isDefined($argName, $args);

		if ($isValid === true && (((int) $args[$argName] < 1 && $canBeZero === false) || ((int) $args[$argName] < 0 && $canBeZero === true))) {
			$isValid = false;
		}

		return $isValid;
	}

	/**
	 * Sprawdza czy podany argument jest float'em i jest większy od zera
	 *
	 * @static
	 * @param string $argName - nazwa sprawdzanego argumentu
	 * @param array $args - tablica, której powinien znajdować się parametr
	 * @param boolean $canBeZero - czy wartość może być zerem
	 *
	 * @return boolean
	 */
	public static function positiveFloatArgument($argName, array $args, $canBeZero = false) {
		$isValid = self::isDefined($argName, $args);
		if ($isValid === true) {
			$compareValue = bccomp(str_replace(',', '.', $args[$argName]), 0); // Większe od zera = 1, równe zero = 0
			$isValid = (($canBeZero === false && $compareValue == 1) || ($canBeZero === true && $compareValue >= 0)) ? true : false;
		}
		return $isValid;
	}

	/**
	 * Sprawdza czy podany string istnieje w tablicy argumentów i czy jest odpowiedniej długości
	 *
	 * @static
	 * @param string $argName - nazwa sprawdzanego argumentu
	 * @param array $args - tablica, której powinien znajdować się parametr
	 * @param int $min - minimalna długość parametru, jeżeli nie będzie podany nie będzie sprawdzany
	 * @param int $max - maksymalna długość parametru, jeżeli nie będzie podany nie będzie sprawdzany
	 *
	 * @return boolean
	 */
	public static function stringArgument($argName, array $args, $min = null, $max = null) {

		if( !self::isDefined($argName, $args) ) {
            return false;
        }

		if ($min !== null && mb_strlen($args[$argName]) < $min) {
            return false;
		}

		if ($max !== null && mb_strlen($args[$argName]) > $max) {
            return false;
		}

		return true;
	}

	/**
	 * Sprawdza czy podany argument znajduje się w podanej tablicy
	 *
	 * @static
	 * @param string $argName - nazwa sprawdzanego argumentu
	 * @param array $args - tablica argumentów w której znajduje się interesujący nas argument
	 * @param array $haystack - tablica, w której jest sprawdzane czy istnieje podany argument
	 *
	 * @return boolean
	 */
	public static function inArrayArgument($argName, array $args, array $haystack) {

		$isValid = self::isDefined($argName, $args);

		if ($isValid === true && !in_array($args[$argName], $haystack)) {
			$isValid = false;
		}

		return $isValid;
	}

	/**
	 * Sprawdza czy podany argument jest prawidłową datą
	 *
	 * @static
	 * @param string $argName - nazwa sprawdzanego argumentu
	 * @param array $args - tablica argumentów w której znajduje się interesujący nas argument
	 *
	 * @return boolean
	 */
	public static function validDate($argName, array $args) {

		$isValid = self::isDefined($argName, $args);

		if ($isValid === true && !is_string($args[$argName]))
			$isValid = false;

		if ($isValid === true) {
			if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $args[$argName], $date)) {
				$isValid = (count($date) === 4) && checkdate($date[2], $date[3], $date[1]);
			} else if (preg_match('#^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$#i', $args[$argName], $date)) {
				$isValid = (count($date) === 1) && is_numeric(strtotime($args[$argName]));
			} else {
				$isValid = false;
			}
		}

		return $isValid;
	}

	/**
	 * Sprawdza czy podany argument jest prawidłową datą i czy mieści się w podanym przedziale
	 *
	 * @static
	 * @param string $argName - nazwa sprawdzanego argumentu
	 * @param array $args - tablica argumentów w której znajduje się interesujący nas argument
	 * @param string $dateFrom - prawidłowy początek przedziału dla daty, jeżeli nie będzie podany nie będzie sprawdzany
	 * @param string $dateTo - prawidłowy koniec przedziału dla daty, jeżeli nie będzie podany nie będzie sprawdzany
	 *
	 * @return boolean
	 */
	public static function dateBetweenDates($argName, array $args, $dateFrom = null, $dateTo = null) {

		$isValid = self::isDefined($argName, $args);

		$timeArg = strtotime($args[$argName]);
		if ($timeArg == -1 || $timeArg === false) {
			$isValid = false;
		}

		if ($dateFrom !== null) {
			$timeFrom = strtotime($dateFrom);
			if ($timeArg < $timeFrom) {
				$isValid = false;
			}
		}

		if ($dateTo !== null) {
			$timeTo = strtotime($dateTo);
			if ($timeArg > $timeTo) {
				$isValid = false;
			}
		}

		return $isValid;
	}

	/**
	 * Sprawdza poprawność kodu pocztowego
	 *
	 * @static
	 * @param String $postcode
     * @return bool
     */
	public static function postCode($postcode) {
		return!!preg_match('/^[0-9]{2}-?[0-9]{3}$/Du', $postcode);
	}

	/**
	 * Sprawdza poprawność peselu
	 *
	 * @static
	 * @param String $pesel
     * @return bool
     */
	public static function pesel($pesel) {
		return!!preg_match("/^[0-9]{11}$/", $pesel);
	}

    /**
     * Sprawdza poprawność adresu email
     *
     * @static
     * @param string $argName
     * @param $args
     * @internal param \String $email
     * @return bool
     */
	public static function email($argName='email', $args) {
		$emailValidator = new Zend_Validate_EmailAddress();

		return $emailValidator->isValid($args[$argName]);
	}

	/**
	 * Sprawdza poprawność loginu
	 *
	 * @static
	 * @param String $login
     * @return bool
     */
	public static function login($login) {
		return!!preg_match("/^[A-Za-z0-9_\-]{4,}$/", $login);
	}

	/**
	 * Wyrażenie regularne wg którego musi być zbudowana wartość w kolumnie pkwiu.
	 *
	 * Przykładowe wartości: 12.45.27.6, 90.22.36.8
	 *
	 * @static
	 * @param String $pkiuw
     * @return bool
     */
	public static function pkiuw($pkiuw) {
		return!!preg_match('/^(\d{2}\.{1}){3}\d{1}$/', $pkiuw);
	}

	/**
	 * Jeżeli podany ciąg to poprawny url lub ip to zwróci true w przeciwnym razie false
	 *  poprawne: http://costamcostam, 192.168.0.1
	 *
	 * @static
	 * @param String $val - adres url lub ip
	 * @return bool
	 */
	public static function urlOrIp($val){
		return (filter_var($val, FILTER_VALIDATE_URL) || filter_var($val, FILTER_VALIDATE_IP));
	}

}