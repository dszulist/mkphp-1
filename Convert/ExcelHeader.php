<?php

/**
 * MK_Convert_ExcelHeader
 *
 * Convertion of Excel Header
 *
 * @category MK_Convert
 *
 * Example
 * echo MK_Convert_ExcelHeader::result(4); //
 * echo MK_Convert_ExcelHeader::result(30); //
 */
class MK_Convert_ExcelHeader
{

	/**
	 * @todo OPIS
	 * @var int
	 */
	private static $tabSize = 26;

	/**
	 * @todo OPIS
	 * @var array
	 */
	private static $_tabElement = array(
		'a', 'b', 'c', 'd', 'e',
		'f', 'g', 'h', 'i', 'j',
		'k', 'l', 'm', 'n', 'o',
		'p', 'q', 'r', 's', 't',
		'u', 'v', 'w', 'x', 'y',
		'z'
	);

	/**
	 * Konwersja cyfra => nagłówek excel-a (litery [a-z]+)
	 * ASCII TABLE: 97-122 [a-z]
	 *
	 * @param $number
	 *
	 * @return string
	 */
	public static function result($number)
	{
		if ($number < 0) {
			return '';
		}
		else if ($number >= self::$tabSize) {
			return self::result(floor($number / self::$tabSize) - 1) . self::$_tabElement[$number % self::$tabSize];
		}
		else {
			return self::$_tabElement[$number];
		}
	}

}