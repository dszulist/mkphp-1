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
 * $converter = new MK_Convert_Roman("2727");
 * echo $converter->result();
 * result is : MMDCCXXVII
 * $converter = new MK_Convert_Roman("MMDCCXXVII");
 * echo $converter->result(); is 2727
 * overscore on roman numeral is represented by underscore prefix
 *
 * */
class MK_Convert_Roman {

	public static $number;
	public static $numrom;
	public static $romovr;
	public static $result;

	public static function convert($number) {
		self::$number = $number;

		self::$numrom = array("I" => 1, "A" => 4,
			"V" => 5, "B" => 9,
			"X" => 10, "E" => 40,
			"L" => 50, "F" => 90,
			"C" => 100, "G" => 400,
			"D" => 500, "H" => 900,
			"M" => 1000, "J" => 4000,
			"P" => 5000, "K" => 9000,
			"Q" => 10000, "N" => 40000,
			"R" => 50000, "W" => 90000,
			"S" => 100000, "Y" => 400000,
			"T" => 500000, "Z" => 900000,
			"U" => 1000000);

		self::$romovr = array("/_V/" => "/P/",
			"/_X/" => "/Q/",
			"/_L/" => "/R/",
			"/_C/" => "/S/",
			"/_D/" => "/T/",
			"/_M/" => "/U/",
			"/IV/" => "/A/", "/IX/" => "/B/", "/XL/" => "/E/", "/XC/" => "/F/",
			"/CD/" => "/G/", "/CM/" => "/H/", "/M_V/" => "/J/", "/MQ/" => "/K/",
			"/QR/" => "/N/", "/QS/" => "/W/", "/ST/" => "/Y/", "/SU/" => "/Z/");

		if (is_numeric($number)) {
			self::convert2rom();
		} else {
			self::convert2num();
		}
	}

	public static function convert2num() {

		self::$result = self::convertNum();
		//need roman numeral input validation
	}

	public static function result($num) {
		self::convert($num);
		return self::$result;
	}

	public static function convert2rom() {
		if (self::$number > 0) {
			self::$result = self::convertRom();
		}

        return self::raiseError(1);
	}

	public static function convertNum() {

		$number = self::$number;
		$numrom = self::$numrom;
		$romovr = self::$romovr;

		$number = preg_replace(array_keys($romovr), array_values($romovr), $number);
//print $number;
		$split_rom = preg_split('//', strrev($number), -1, PREG_SPLIT_NO_EMPTY);
		$tmp_arr = $split_rom;
		$split_rom = array();
		foreach ($tmp_arr as $key => $val) {
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

	/**
	 *
	 * @param Integer $num 
	 */
	public static function raiseError($num) {
		if ($num == 1) {
			echo "unsupported number";
		}
	}

}