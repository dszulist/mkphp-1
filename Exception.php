<?php
/**
 * MK_Exception
 *
 * Obsługa wyjątków dla JSON-a
 *
 * @category MK
 * @package	MK_Exception
 */
class MK_Exception extends Exception {


	/**
	 * Ignorowanie określonych klas z metodami
	 */
	private static $_traceIgnore = array(
		'MK_Error::handler',
		'MK::shutdownFunction'
	);


	/**
	 *
	 * Zwrócenie elementu z tablicy
	 *
	 * @param string $name
	 * @param array $arr
	 * @return string
	 */
	private function _getValue( $name , $arr ){
		return isset($arr[$name]) ? $arr[$name] : '';
	}


	/**
	 *
	 * Rozbudowany raport błędu w htmlu
	 *
	 * @param string $dbError	- raport błędu bazy danych
	 */
	public function getExtendedMessage( $dbError='' ) {
		$msg = '';

		if( !empty($dbError) ) {
			$msg .= '<br /><b>Komunikat bazy:</b> ' . $dbError;
		}

		// Śledzenie plików
		$traceKey = 1;
		$msg .= '<br /><b>#' . $traceKey++ . '</b> ' . $this->getFile() . '(' . $this->getLine() . ')';

		// Odwrócenie kolejności czytania tablicy ze śladami
		$traceArray = array_reverse($this->getTrace(),true);

		foreach($traceArray as $trace) {
			// Połączenie klasy,typu,metody
			$classTypeFunction = $this->_getValue('class',$trace) . $this->_getValue('type',$trace) . $this->_getValue('function',$trace);

			// Ignorowanie klas z metodami (self::$_traceIgnore)
			if( in_array($classTypeFunction, self::$_traceIgnore) ) continue;

			// Śledzenie pliku, wraz z wywoływaną klasą, metodą i argumentami:
			$msg .= '<br /><b>#' . $traceKey++ . '</b> ' . $this->_getValue('file',$trace) . '(' . $this->_getValue('line',$trace) . '): <b>' . $classTypeFunction . '(</b>';

			// Odczytanie argumentów
			if( isset($trace['args']) && count($trace['args']) > 0 ) {
				foreach($trace['args'] as $argsKey=>$argsValue) {
					$msg .= ($argsKey?' <b>,</b> ':' ');
					$msg .= '<pre>'.print_r($argsValue,true).'</pre>';
				}
			}
			$msg .= ' <b>)</b>';
		}
		return $msg;
	}
}
