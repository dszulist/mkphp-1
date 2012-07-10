<?php

/**
 * MK_Filter_AlnumWithUnderscore
 *
 * @category    MK_Filter
 * @package        MK_Filter_AlnumWithUnderscore
 */
class MK_Filter_AlnumWithUnderscore
{

	/**
	 * Returns the string $value, removing all but alphabetic and digit characters
	 *
	 * @param string $value
	 * @param boolean $allowWhiteSpace
	 * @param boolean $unicodeEnabled
	 *
	 * @return string
	 */
	public static function filter($value, $allowWhiteSpace = false, $unicodeEnabled = true)
	{
		$allowWhiteSpace = (boolean)$allowWhiteSpace;
		if (null === $unicodeEnabled) {
			$unicodeEnabled = (@preg_match('/\pL/u', 'a')) ? true : false;
		}
		$_meansEnglishAlphabet = false;

		$whiteSpace = $allowWhiteSpace ? '\s' : '';
		if (!$unicodeEnabled) {
			// POSIX named classes are not supported, use alternative a-zA-Z0-9 match
			$pattern = '/[^a-zA-Z0-9_' . $whiteSpace . ']/';
		} else if ($_meansEnglishAlphabet) {
			//The Alphabet means english alphabet.
			$pattern = '/[^a-zA-Z0-9_' . $whiteSpace . ']/u';
		} else {
			//The Alphabet means each language's alphabet.
			$pattern = '/[^\p{L}\p{N}_' . $whiteSpace . ']/u';
		}

		return preg_replace($pattern, '', (string)$value);
	}

}