<?php

/**
 * MK_String
 *
 * Klasa do obsługi stringów
 *
 * @category MK
 * @package    MK_String
 */
class MK_String
{


	/**
	 * @static
	 * Zwraca ilość bajtów jakie zawiera string
	 *
	 * Count the number of bytes of a given string.
	 * Input string is expected to be ASCII or UTF-8 encoded.
	 * Warning: the function doesn't return the number of chars
	 * in the string, but the number of bytes.
	 * See http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	 * for information on UTF-8.
	 *
	 * @param string $str The string to compute number of bytes
	 *
	 * @param bool $format - jezeli ustawimy na true zwróci nam Mb/Tb/etc zamiast ilosci bajtów
	 *
	 * @return int The length in bytes of the given string.
	 */
	public static function bytes($str, $format = false)
	{
		// STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
		// Number of characters in string
		$strlen_var = strlen($str);
		// string bytes counter
		$d = 0;

		/**
		 * Iterate over every character in the string,
		 * escaping with a slash or encoding to UTF-8 where necessary
		 */
		for ($c = 0; $c < $strlen_var; ++$c) {
			$ord_var_c = ord($str{$c});
			switch (true) {
				case(($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
					// characters U-00000000 - U-0000007F (same as ASCII)
					$d++;
					break;
				case(($ord_var_c & 0xE0) == 0xC0):
					// characters U-00000080 - U-000007FF, mask 110XXXXX
					$d += 2;
					break;
				case(($ord_var_c & 0xF0) == 0xE0):
					// characters U-00000800 - U-0000FFFF, mask 1110XXXX
					$d += 3;
					break;
				case(($ord_var_c & 0xF8) == 0xF0):
					// characters U-00010000 - U-001FFFFF, mask 11110XXX
					$d += 4;
					break;
				case(($ord_var_c & 0xFC) == 0xF8):
					// characters U-00200000 - U-03FFFFFF, mask 111110XX
					$d += 5;
					break;
				case(($ord_var_c & 0xFE) == 0xFC):
					// characters U-04000000 - U-7FFFFFFF, mask 1111110X
					$d += 6;
					break;
				default:
					$d++;
			}
		}

		return $format === true ? self::formatBytes($d) : $d;
	}

	/**
	 * Foramtuje bajty na k,M,G,T
	 *
	 * @static
	 *
	 * @param $size
	 * @param int $precision
	 *
	 * @return string
	 */
	public static function formatBytes($size, $precision = 2)
	{
		$base = log($size) / log(1024);
		$suffixes = array('', 'k', 'M', 'G', 'T');

		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}

}
