<?php

/*
 * Klasa do sortowania tablic jednowymiarowych typu ['klucz' => 'wartosc'] 
 */
class MArraySort {

    // kolumna po której mają być sortowane tablice assocjacyjne po wartosci klucza 
    public static $_sortValue = NULL;
    // Porzadek sortowania
    public static $_sortOrder = "ASC";



    /*
     * Sortowanie tablicy po kluczach, 
     * z uwzględnieniem polskich znaków 
     * oraz z zachowaniem wartości kluczy
     * 
     * Parametr order ustawiony na 'DESC', powoduje posortowanie w odwrotnej kolejności
     */
    static function byKeys(&$array, $order = '') {
        setlocale(LC_COLLATE, 'pl_PL.utf8', 'pl');
        uksort($array, 'strcoll');
        setlocale(LC_COLLATE, '');
        if (!empty($order) && $order == 'DESC') {
            $array = array_reverse($array, true); //parametr true powoduje zachowanie wartości kluczy
        }
    }
    

    /*
     * Sortowanie tablicy po wartościach, 
     * z uwzględnieniem polskich znaków 
     * oraz z zachowaniem wartości kluczy
     * 
     * Parametr order ustawiony na 'DESC', powoduje posortowanie w odwrotnej kolejności
     */
    static function byValues(&$array, $order = '') {
        setlocale(LC_COLLATE, 'pl_PL.utf8', 'pl');
        uasort($array, 'strcoll');
        setlocale(LC_COLLATE, '');
        if (!empty($order) && $order == 'DESC') {
            $array = array_reverse($array, true); //parametr true powoduje zachowanie wartości kluczy
        }
    }

    
    static function array_diff_assoc_recursive($array1, $array2) {
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key])) {
                    $difference[$key] = $value;
                } 
                elseif (!is_array($array2[$key])) {
                    $difference[$key] = $value;
                }
                else {
                    $new_diff = MArraySort::array_diff_assoc_recursive($value, $array2[$key]);
                    if ($new_diff != FALSE) {
                        $difference[$key] = $new_diff;
                    }
                }
            } 
            elseif (!isset($array2[$key]) || $array2[$key] != $value) {
                $difference[$key] = $value;
            }
        }
        return !isset($difference) ? 0 : $difference;
    }

    
    /**
     * 
     * Sortuje tablice wielowymiarowa po wartosci w kluczy tablicy zagniezdzonej tj.
     * 
     * Przy wywołaniu : $mySortedArray = MArraySort::assocByKeyValue($myArray,'symbol');
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
     */
    public static function assocByKeyValue($array, $sort, $dir='') {
        if (!empty($sort)){
            self::$_sortValue = $sort;
        }
        if (!empty($dir)){
            self::$_sortOrder = $dir;
        }

        usort($array, 'MArraySort::invenSort');
        
        return $array;
    }

    
    /**
     * Funkcja pomocnicza dla assocByKeyValue sprawdza po kluczu ktory z elementów ma wiekszy klucz i zwraca odpowiedni wynik dla funkcji usort
     * 
     * @param Array $item1
     * @param Array $item2
     * @return Integer
     */
    public static function invenSort($item1, $item2) {
        $i = (self::$_sortValue == 'ASC') ? 1 : -1;
        if ($item1[self::$_sortValue] == $item2[self::$_sortValue]){
            return 0;
        }
        return (($item1[self::$_sortValue] < $item2[self::$_sortValue]) ? 1 : -1) * $i;
    }

}
