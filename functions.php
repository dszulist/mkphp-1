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