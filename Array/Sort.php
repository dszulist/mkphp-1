<?php

/**
 * MK_Array_Sort
 *
 * Klasa do sortowania tablic jednowymiarowych typu ['klucz' => 'wartosc'] 
 *
 * @category MK_Array
 * @package	MK_Array_Sort
 * @author	bskrzypkowiak
 */
class MK_Array_Sort {

	// kolumna po ktorej maja byc sortowane tablice assocjacyjne po wartosci klucza 
	public static $_sortValue = NULL;
	// Porzadek sortowania
	public static $_sortOrder = "ASC";

	/*
	 * Sortowanie tablicy po kluczach, 
	 * z uwzglednieniem polskich znakow
	 * oraz z zachowaniem wartosci kluczy
	 * 
	 * Parametr order ustawiony na 'DESC', powoduje posortowanie w odwrotnej kolejnosci
	 * 
	 * @param Array     $array
	 * @param String    $order 
	 */

	public static function byKeys(array &$array, $order = '') {
		setlocale(LC_COLLATE, 'pl_PL.utf8', 'pl');
		uksort($array, 'strcoll');
		setlocale(LC_COLLATE, '');
		if (!empty($order) && $order == 'DESC') {
			$array = array_reverse($array, true); //parametr true powoduje zachowanie wartości kluczy
		}
	}

	/*
	 * Sortowanie tablicy po wartosciach, 
	 * z uwzglednieniem polskich znakow 
	 * oraz z zachowaniem wartosci kluczy
	 * 
	 * Parametr order ustawiony na 'DESC', powoduje posortowanie w odwrotnej kolejnosci
	 *     
	 * @param Array     $array
	 * @param String    $order 
	 */

	public static function byValues(array &$array, $order = '') {
		setlocale(LC_COLLATE, 'pl_PL.utf8', 'pl');
		uasort($array, 'strcoll');
		setlocale(LC_COLLATE, '');
		if (!empty($order) && $order == 'DESC') {
			$array = array_reverse($array, true); //parametr true powoduje zachowanie wartości kluczy
		}
	}

	/**
	 *
	 * @param Array     $array1
	 * @param Array     $array2
	 * 
	 * @return Mixed 
	 */
	public static function array_diff_assoc_recursive(array $array1, array $array2) {
		foreach ($array1 as $key => $value) {
			if (is_array($value)) {
				if (!isset($array2[$key])) {
					$difference[$key] = $value;
				} elseif (!is_array($array2[$key])) {
					$difference[$key] = $value;
				} else {
					$new_diff = MK_Array_Sort::array_diff_assoc_recursive($value, $array2[$key]);
					if ($new_diff != FALSE) {
						$difference[$key] = $new_diff;
					}
				}
			} elseif (!isset($array2[$key]) || $array2[$key] != $value) {
				$difference[$key] = $value;
			}
		}
		return!isset($difference) ? 0 : $difference;
	}

	/**
	 * 
	 * Sortuje tablice wielowymiarowa po wartosci w kluczy tablicy zagniezdzonej tj.
	 * 
	 * Przy wywołaniu : $mySortedArray = MK_Array_Sort::assocByKeyValue($myArray,'symbol');
	 * 
	 * Tablica wejsciowa ($myArray):
	 * Array
	 * ( 
	 * 		[0] => Array
	 * 			(
	 * 				[symbol] => '002'
	 * 				[field2] => 'lorem'
	 * 			)
	 * 		[1] => Array
	 * 			(
	 * 				[symbol] => '001' 
	 * 				[field2] => 'ipsum'
	 * 			) 		
	 *  )
	 * 
	 * 
	 * Tablica wyjsciowa ($mySortedArray):
	 * Array
	 * ( 
	 * 		[0] => Array
	 * 			(
	 * 				[symbol] => '001'
	 * 				[field2] => 'ipsum'
	 * 			)
	 * 		[1] => Array
	 * 			(
	 * 				[symbol] => '002' 
	 * 				[field2] => 'lorem'
	 * 			) 		
	 *  )
	 *  	 
	 * @param Array $array
	 * @param String $sort
	 * @param String $dir (Optional)
	 * 
	 * @return Array
	 */
	public static function assocByKeyValue(array $array, $sort, $dir='') {
		if (!empty($sort)) {
			self::$_sortValue = $sort;
		}
		if (!empty($dir)) {
			self::$_sortOrder = $dir;
		}

		usort($array, 'MK_Array_Sort::invenSort');

		return $array;
	}

	/**
	 * Funkcja pomocnicza dla assocByKeyValue sprawdza po kluczu ktory z elementow ma wiekszy klucz i zwraca odpowiedni wynik dla funkcji usort
	 * 
	 * @param Array $arr1
	 * @param Array $arr2
	 * 
	 * @return Integer
	 */
	public static function invenSort(array $arr1, array $arr2) {
		$i = (self::$_sortValue == 'ASC') ? 1 : -1;
		if ($arr1[self::$_sortValue] == $arr2[self::$_sortValue]) {
			return 0;
		}
		return (($arr1[self::$_sortValue] < $arr2[self::$_sortValue]) ? 1 : -1) * $i;
	}

}
