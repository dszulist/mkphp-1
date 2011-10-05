<?php
/**
 * MoneyOperations
 *
 * Zawiera f-cje do operacji na pieni¹dzach
 *
 * @category	Mkphp
 * @package	MoneyOperations
 * @author      lwinnicki
 */
class MoneyOperations {

    /**
     * S³owny zapis kwot - tablica z wyrazami
     */
    private static $_words = array(
        'minus',
        array('zero', 'jeden', 'dwa', 'trzy', 'cztery', 'piêæ', 'szeœæ', 'siedem', 'osiem', 'dziewiêæ'),
        array('dziesiêæ', 'jedenaœcie', 'dwanaœcie', 'trzynaœcie', 'czternaœcie', 'piêtnaœcie', 'szesnaœcie', 'siedemnaœcie', 'osiemnaœcie', 'dziewiêtnaœcie'),
        array('dziesiêæ', 'dwadzieœcia', 'trzydzieœci', 'czterdzieœci', 'piêædziesi¹t', 'szeœædziesi¹t', 'siedemdziesi¹t', 'osiemdziesi¹t', 'dziewiêædziesi¹t'),
        array('sto', 'dwieœcie', 'trzysta', 'czterysta', 'piêæset', 'szeœæset', 'siedemset', 'osiemset', 'dziewiêæset'),
        array('tysi¹c', 'tysi¹ce', 'tysiêcy'),
        array('milion', 'miliony', 'milionów'),
        array('miliard', 'miliardy', 'miliardów'),
        array('bilion', 'biliony', 'bilionów'),
        array('biliard', 'biliardy', 'biliardów'),
        array('trylion', 'tryliony', 'trylionów'),
        array('tryliard', 'tryliardy', 'tryliardów'),
        array('kwadrylion', 'kwadryliony', 'kwadrylionów'),
        array('kwintylion', 'kwintyliony', 'kwintylionów'),
        array('sekstylion', 'sekstyliony', 'sekstylionów'),
        array('septylion', 'septyliony', 'septylionów'),
        array('oktylion', 'oktyliony', 'oktylionów'),
        array('nonylion', 'nonyliony', 'nonylionów'),
        array('decylion', 'decyliony', 'decylionów')
    );

    /**
     * Wylicza wartoœæ brutto
     *
     * @param float $netto		- wartoœæ netto
     * @param float $tax		- procentowa wartoœæ podatku
     * @param float $quantity	- iloœæ
     * @return float
     */
    public static function calculateBrutto($netto, $tax, $quantity=1) {
        $netto = (float) $netto;
        $tax = (float) $tax;
        $quantity = (float) $quantity;

        $brutto = $quantity * $netto * ( 1 + ( $tax / 100 ) );

        return round($brutto, 2);
    }

    /**
     * Odmiana s³owa dla podanej liczby, np. ciastko/ciastka/ciastek
     *
     * Przyk³ad u¿ycia:
     *  echo '16 '.MoneyOperations::varietyVerbal(array('punkt','punkty','punktów'), 16);
     *  // Wynik: "16 punktów"
     *  echo '103 '.MoneyOperations::varietyVerbal(array('ciastko','ciastka','ciastek'), 103);
     *  // Wynik: "103 ciastka"
     *
     * @param Array $wordsArray
     * @param Integer $number
     * @return String
     */
    public static function varietyVerbal($wordsArray, $number) {
        $txt = ($number == 1) ? $wordsArray[0] : $wordsArray[2];
        $unit = (int) substr($number, -1);
        $rest = $number % 100;
        if (($unit > 1 && $unit < 5) & !($rest > 10 && $rest < 20)) {
            $txt = $wordsArray[1];
        }
        return $txt;
    }

    /**
     * Odmiana wartoœci liczbowej trzycyfrowej (mniejszej ni¿ 1000) na jej s³own¹ postaæ.
     * Wykorzystywane g³ównie w metodzie verbal()
     *
     * @param Integer $number
     * @return String
     */
    private static function _lessVariety($number) {
        $txt = '';

        $abs = abs((int) $number);
        if ($abs == 0) {
            return self::$_words[1][0];
        }

        $unit = $abs % 10;
        $tens = ($abs % 100 - $unit) / 10;
        $hundreds = ($abs - $tens * 10 - $unit) / 100;

        if ($hundreds > 0) {
            $txt .= self::$_words[4][$hundreds - 1] . ' ';
        }

        if ($tens > 0) {
            if ($tens == 1) {
                $txt .= self::$_words[2][$unit] . ' ';
            } else {
                $txt .= self::$_words[3][$tens - 1] . ' ';
            }
        }

        if ($unit > 0 && $tens != 1) {
            $txt .= self::$_words[1][$unit] . ' ';
        }

        return $txt;
    }

    /**
     * G³ówna metoda zamieniaj¹ca dowoln¹ liczbê na jej postaæ s³own¹.
     *
     * Przyk³ad u¿ycia:
     *  echo MoneyOperations::verbal(103);
     *  // Wynik: "sto trzy"
     *  echo MoneyOperations::verbal('12345');
     *  // Wynik: "dwanaœcie tysiêcy trzysta czterdzieœci piêæ"
     *  echo MoneyOperations::verbal('123456789');
     *  // Wynik: "sto dwadzieœcia trzy miliony czterysta piêædziesi¹t szeœæ tysiêcy siedemset osiemdziesi¹t dziewiêæ"
     *
     * @param Mixed $_number (zarówno Integer jak i String)
     * @param Boolean $fractionNumeric - w przypadku wyst¹pienia wartoœci po przecinku (grosze) wyœwietli podsumowanie
     *  numeryczne 'xx/100' (true) lub s³owne 'dwanaœcie groszy' (false)
     * @return String
     */
    public static function verbal($number, $fractionNumeric=false) {
        $txt = '';

        $number = floatval($number);
        $tmpNumber = floor($number);
        $fraction = round($number - $tmpNumber, 2) * 100;

        if ($tmpNumber < 0) {
            $tmpNumber *= -1;
            $txt = self::$_words[0] . ' ';
        }

        if ($tmpNumber == 0) {
            $txt = self::$_words[1][0] . ' ';
        }

        settype($tmpNumber, 'string');
        $txtSplit = str_split(strrev($tmpNumber), 3);
        $txtSplitCount = count($txtSplit) - 1;

        for ($i = $txtSplitCount; $i >= 0; $i--) {
            $tmpNumber = (int) strrev($txtSplit[$i]);
            if ($tmpNumber > 0) {
                if ($i == 0) {
                    $txt .= self::_lessVariety($tmpNumber) . ' ';
                } else {
                    $txt .= $tmpNumber > 1 ? self::_lessVariety($tmpNumber) . ' ' : '';
                    $txt .= self::varietyVerbal(self::$_words[4 + $i], $tmpNumber) . ' ';
                }
            }
        }

        $txt .= self::varietyVerbal(array('z³oty', 'z³ote', 'z³otych'), $tmpNumber) . ' ';
        $txt .= 'i ' . ( $fractionNumeric ? $fraction . '/100 ' : self::_lessVariety($fraction) . ' ' ) . self::varietyVerbal(array('grosz', 'grosze', 'groszy'), $fraction) . ' ';

        return trim($txt);
    }

}