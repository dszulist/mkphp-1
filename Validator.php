<?php
/**
 * Validator
 *
 * Weryfikacja danych
 *
 * @todo przerobi� chociaż cze�� na u�ycie Klas z Zend_Validator
 * 
 * @category	Mkphp
 * @package	Validator
 */
class Validator {

    /**
     * Sprawdza czy podany klucz istnieje w podanej tablicy
     *
     * @param string $argName - szukany klucz
     * @param array $args - tabela w ktorej sukamy klucza
     */
    public static function isDefined($argName, array $args) {
        return (array_key_exists($argName, $args));
    }

    /**
     * Sprawdza czy podany klucz istnieje w podanej tablicy i czy jest wiekszy od 0
     *
     * @param string $argName - szukany klucz
     * @param array $args - tabela w ktorej sukamy klucza
     */
    public static function isNotEmpty($argName, array $args) {
        return (self::isDefined($argName, $args) && !empty($args[$argName]));
    }

    /**
     *
     * Sprawdza czy podany klucz istnieje w podanej tablicy i czy wartosc w tablicy dla tego klucza iset numeric
     *
     * @param string $argName
     * @param array $args
     */
    public static function isNumeric($argName, array $args) {
        return (self::isDefined($argName, $args) && is_numeric($args[$argName]));
    }

    /**
     *
     * Sprawdza czy podany klucz istnieje w podanej tablicy i czy wartosc w tablicy dla tego klucza jest typu integer
     *
     * @param string $argName
     * @param array $args
     */
    public static function integerArgument($argName, array $args) {
        return (self::isDefined($argName, $args) && is_numeric($args[$argName]));
    }

    /**
     *
     * Sprawdza czy podany argument jest integere'm i jest większy od zera
     *
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
     *
     * Sprawdza czy podany argument jest float'em i jest większy od zera
     *
     * @param string $argName - nazwa sprawdzanego argumentu
     * @param array $args - tablica, której powinien znajdować się parametr
     * @param boolean $canBeZero - czy wartość może być zerem
     *
     * @return boolean
     */
    public static function positiveFloatArgument($argName, array $args, $canBeZero = false) {

        $isValid = self::isDefined($argName, $args);

        if ($isValid === true && (((float) $args[$argName] <= 0 && $canBeZero === false)
                || ((float) $args[$argName] < 0 && $canBeZero === true))) {

            $isValid = false;
        }


        return $isValid;
    }

    /**
     *
     * Sprawdza czy podany string istnieje w tablicy argumentów i czy jest odpowiedniej długości
     *
     * @param string $argName - nazwa sprawdzanego argumentu
     * @param array $args - tablica, której powinien znajdować się parametr
     * @param int $min - minimalna długość parametru, jeżeli nie będzie podany nie będzie sprawdzany
     * @param int $max - maksymalna długość parametru, jeżeli nie będzie podany nie będzie sprawdzany
     *
     * @return boolean
     */
    public static function stringArgument($argName, array $args, $min = null, $max = null) {

        $isValid = self::isDefined($argName, $args);

        if ($isValid === true && !is_string($args[$argName])) {
            $isValid = false;
        }

        if ($isValid === true && $min !== null && mb_strlen($args[$argName]) < $min) {
            $isValid = false;
        }

        if ($isValid === true && $max !== null && mb_strlen($args[$argName]) > $max) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     *
     * Sprawdza czy podany argument znajduje się w podanej tablicy
     *
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
     *
     * Sprawdza czy podany argument jest prawidłową datą
     *
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
     *
     * Sprawdza poprawność kodu pocztowego
     *
     * @param String $postcode
     */
    public static function postCode($postcode) {
        return!!preg_match('/^[0-9]{2}-?[0-9]{3}$/Du', $postcode);
    }

    /**
     *
     * Sprawdza poprawność peselu
     *
     * @param String $pesel
     */
    public static function pesel($pesel) {
        return!!preg_match("/^[0-9]{11}$/", $pesel);
    }

    /**
     *
     * Sprawdza poprawność adresu email
     *
     * @param String $email
     */
    public static function email($argName='email', $args) {
        $emailValidator = new Zend_Validate_EmailAddress();

        return $emailValidator->isValid($args[$argName]);
    }

    /**
     *
     * Sprawdza poprawność loginu
     *
     * @param String $login
     */
    public static function login($login) {
        return!!preg_match("/^[A-Za-z0-9_\-]{4,}$/", $login);
    }

    /**
     *
     * Wyrażenie regularne wg którego musi być zbudowana wartość w kolumnie pkwiu.
     *
     * Przykładowe wartości: 12.45.27.6, 90.22.36.8
     *
     * @param String $pkiuw
     */
    public static function pkiuw($pkiuw) {
        return!!preg_match('/^(\d{2}\.{1}){3}\d{1}$/', $pkiuw);
    }

}