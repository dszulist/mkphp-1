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
     * Wylicza wartość brutto
     *
     * @param float $netto		- wartość netto
     * @param float $tax		- procentowa wartość podatku
     * @param float $quantity	- ilość
     * @return float
     */
    public static function calculateBrutto($netto, $tax, $quantity=1) {
        $netto = (float) $netto;
        $tax = (float) $tax;
        $quantity = (float) $quantity;

        $brutto = $quantity * $netto * ( 1 + ( $tax / 100 ) );

        return round($brutto, 2);
    }
	
}