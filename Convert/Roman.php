<?php

/**
 * MK_Convert_Roman
 *
 * Convertion of Roman Numeral
 *
 * @version "just for fun"
 * @author Huda M Elmatsani <justhuda at netscape dot net>
 * @category MK_Convert
 * @licence "up2u"
 *
 * Example
 * echo MK_Convert_Roman::result("2727"); // "MMDCCXXVII"
 * echo MK_Convert_Roman::result("MMDCCXXVII"); // 2727
 * overscore on roman numeral is represented by underscore prefix
 *
 * */
class MK_Convert_Roman {

	public static $number;
	public static $numrom;
	public static $romovr;
	public static $result;

	/**
	 * Konwersja rzymskie=>numeryczne i na odwrót (automatyczny wybór)
	 *
	 * @param mixed $number
	 * @return mixed
	 * @throws Exception
	 */
	public static function result($number) {
		self::$number = $number;

		self::$numrom = array(
			"I" => 1, "A" => 4, "V" => 5, "B" => 9,
			"X" => 10, "E" => 40, "L" => 50, "F" => 90,
			"C" => 100, "G" => 400, "D" => 500, "H" => 900,
			"M" => 1000, "J" => 4000, "P" => 5000, "K" => 9000,
			"Q" => 10000, "N" => 40000, "R" => 50000, "W" => 90000,
			"S" => 100000, "Y" => 400000, "T" => 500000, "Z" => 900000,
			"U" => 1000000);

		self::$romovr = array(
			"/_V/" => "/P/", "/_X/" => "/Q/", "/_L/" => "/R/", "/_C/" => "/S/",
			"/_D/" => "/T/", "/_M/" => "/U/", "/IV/" => "/A/", "/IX/" => "/B/",
			"/XL/" => "/E/", "/XC/" => "/F/", "/CD/" => "/G/", "/CM/" => "/H/",
			"/M_V/" => "/J/", "/MQ/" => "/K/", "/QR/" => "/N/", "/QS/" => "/W/",
			"/ST/" => "/Y/", "/SU/" => "/Z/");

		if (is_numeric($number)) {
			if (self::$number > 0) {
				self::$result = self::convertRom();
			} else {
				throw new MK_Exception("Unsupported number: " . self::$number);
			}
		} else {
			self::$result = self::convertNum();
		}

		return self::$result;
	}

	/**
	 * Konwersja na numeryczne
	 *
	 * @return string
	 */
	public static function convertNum() {
		$number = self::$number;
		$numrom = self::$numrom;
		$romovr = self::$romovr;

		$number = preg_replace(array_keys($romovr), array_values($romovr), $number);
		$split_rom = preg_split('//', strrev($number), -1, PREG_SPLIT_NO_EMPTY);
		$tmp_arr = $split_rom;
		$split_rom = array();
		foreach ($tmp_arr as $val) {
			if ($val != '/') {
				$split_rom[] = $val;
			}
		}
		unset($tmp_arr);
		$arr_num = '';
		for ($i = 0; $i < sizeof($split_rom); $i++) {

			$num = $numrom[$split_rom[$i]];

			if ($i > 0 && ($num < $numrom[$split_rom[$i - 1]]))
				$num = -$num;

			$arr_num += $num;
		}

		return str_replace("/", "", $arr_num);
	}

	/**
	 * Konwersja na rzymskie
	 *
	 * @return string
	 */
	public static function convertRom() {
		$number = self::$number;
		$numrom = array_reverse(self::$numrom);
		$arabic = array_values($numrom);
		$roman = array_keys($numrom);
		$str_roman = '';

		//algorithm from oguds
		$i = 0;
		while ($number != 0) {
			while ($number >= $arabic[$i]) {
				$number-= $arabic[$i];
				$str_roman.= $roman[$i];
			}
			$i++;
		}

		$romovr = self::$romovr;

		return str_replace("/", "", preg_replace(array_values($romovr), array_keys($romovr), $str_roman));
	}

}