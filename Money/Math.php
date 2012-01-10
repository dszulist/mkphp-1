<?php

/**
 * MK_Money_Math
 *
 * Klasa zawiera funkcje do operacji na cenach
 *
 * @category	MK_Money
 * @package		MK_Money_Math
 */
Class MK_Money_Math {

	/**
	 * Wylicza wartość brutto = $quantity * $netto * ( 1 + ( $tax / 100 ) )
	 *
	 * @param float $netto  - wartość netto
	 * @param float $tax    - procentowa wartość podatku
	 * @param int $quantity - ilość sztuk
	 * @return string
	 */
	public static function calculateBrutto($netto, $tax, $quantity=1) {
		bcscale(MK_PRECISION_NUMBER + 1);
		return bcround(bcmul(bcmul($quantity, $netto), bcadd(1, bcdiv($tax, 100))), MK_PRECISION_NUMBER);
	}

}