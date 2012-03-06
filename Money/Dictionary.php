<?php

/**
 * MK_Money_Dictionary
 *
 * Klasa zawiera metody odostepniajace mozliwosc tlumaczenia
 * liczb na ich słowne odpowiedniki
 *
 * @category	MK_Money
 * @package		MK_Money_Dictionary
 */
class MK_Money_Dictionary {

	/**
	 * Słowny zapis kwot - tablica z wyrazami
	 */
	private static $_words = array(
		'minus',
		array('zero', 'jeden', 'dwa', 'trzy', 'cztery', 'pięć', 'sześć', 'siedem', 'osiem', 'dziewięć'),
		array('dziesięć', 'jedenaście', 'dwanaście', 'trzynaście', 'czternaście', 'piętnaście', 'szesnaście', 'siedemnaście', 'osiemnaście', 'dziewiętnaście'),
		array('dziesięć', 'dwadzieścia', 'trzydzieści', 'czterdzieści', 'pięćdziesiąt', 'sześćdziesiąt', 'siedemdziesiąt', 'osiemdziesiąt', 'dziewięćdziesiąt'),
		array('sto', 'dwieście', 'trzysta', 'czterysta', 'pięćset', 'sześćset', 'siedemset', 'osiemset', 'dziewięćset'),
		array('tysiąc', 'tysiące', 'tysięcy'),
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
	 * Odmiana słowa dla podanej liczby, np. ciastko/ciastka/ciastek
	 *
	 * Przykład użycia:
	 *  echo '16 '.MK_Money_Dictionary::varietyVerbal(array('punkt','punkty','punktów'), 16);
	 *  // Wynik: "16 punktów"
	 *  echo '103 '.MK_Money_Dictionary::varietyVerbal(array('ciastko','ciastka','ciastek'), 103);
	 *  // Wynik: "103 ciastka"
	 *
	 * @param Array   $wordsArray
	 * @param Integer $number
	 *
	 * @return String
	 */
	public static function varietyVerbal($wordsArray, $number) {
		$txt = ($number == 1) ? $wordsArray[0] : $wordsArray[2];
		$unit = (int) substr($number, -1);
		$rest = $number % 100;
		if(($unit > 1 && $unit < 5) & !($rest > 10 && $rest < 20)) {
			$txt = $wordsArray[1];
		}
		return $txt;
	}

	/**
	 * Odmiana wartości liczbowej trzycyfrowej (mniejszej niż 1000) na jej słowną postać.
	 * Wykorzystywane głównie w metodzie verbal()
	 *
	 * @param Integer $number
	 *
	 * @return String
	 */
	private static function _lessVariety($number) {
		$txt = '';

		$abs = abs((int) $number);
		if($abs == 0) {
			return self::$_words[1][0];
		}

		$unit = $abs % 10;
		$tens = ($abs % 100 - $unit) / 10;
		$hundreds = ($abs - $tens * 10 - $unit) / 100;

		if($hundreds > 0) {
			$txt .= self::$_words[4][$hundreds - 1] . ' ';
		}

		if($tens > 0) {
			if($tens == 1) {
				$txt .= self::$_words[2][$unit] . ' ';
			} else {
				$txt .= self::$_words[3][$tens - 1] . ' ';
			}
		}

		if($unit > 0 && $tens != 1) {
			$txt .= self::$_words[1][$unit] . ' ';
		}

		return $txt;
	}

	/**
	 * Główna metoda zamieniająca dowolną liczbę na jej postać słowną.
	 *
	 * Przykład użycia:
	 *  echo MK_Money_Dictionary::verbal(103);
	 *  // Wynik: "sto trzy"
	 *  echo MK_Money_Dictionary::verbal('12345');
	 *  // Wynik: "dwanaście tysięcy trzysta czterdzieści pięć"
	 *  echo MK_Money_Dictionary::verbal('123456789');
	 *  // Wynik: "sto dwadzieścia trzy miliony czterysta pięćdziesiąt sześć tysięcy siedemset osiemdziesiąt dziewięć"
	 *
	 * @param         $number
	 * @param Boolean $fractionNumeric - w przypadku wystąpienia wartości po przecinku (grosze) wyświetli podsumowanie
	 *  numeryczne 'xx/100' (true) lub słowne 'dwanaście groszy' (false)
	 *
	 * @internal param \Mixed $_number (zarówno Integer jak i String)
	 * @return String
	 */
	public static function verbal($number, $fractionNumeric = false) {
		$txt = '';

		$number = floatval($number);
		$tmpNumber = floor($number);
		$fraction = round($number - $tmpNumber, 2) * 100;

		if($tmpNumber < 0) {
			$tmpNumber *= -1;
			$txt = self::$_words[0] . ' ';
		}

		if($tmpNumber == 0) {
			$txt = self::$_words[1][0] . ' ';
		}

		settype($tmpNumber, 'string');
		$txtSplit = str_split(strrev($tmpNumber), 3);
		$txtSplitCount = count($txtSplit) - 1;

		for($i = $txtSplitCount; $i >= 0; $i--) {
			$tmpNumber = (int) strrev($txtSplit[$i]);
			if($tmpNumber > 0) {
				if($i == 0) {
					$txt .= self::_lessVariety($tmpNumber) . ' ';
				} else {
					$txt .= $tmpNumber > 1 ? self::_lessVariety($tmpNumber) . ' ' : '';
					$txt .= self::varietyVerbal(self::$_words[4 + $i], $tmpNumber) . ' ';
				}
			}
		}

		$txt .= self::varietyVerbal(array('złoty', 'złote', 'złotych'), $tmpNumber) . ' ';
		$txt .= 'i ' . ($fractionNumeric ? $fraction . '/100 ' : self::_lessVariety($fraction) . ' ') . self::varietyVerbal(array('grosz', 'grosze', 'groszy'), $fraction) . ' ';

		return trim($txt);
	}

}