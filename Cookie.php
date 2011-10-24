<?php
/**
 * MK_Cookie
 *
 * Obsługa ciasteczek zgodna z mkjs
 *
 * @category MK
 * @package	MK_Cookie
 * @author bskrzypkowiak
 */
class MK_Cookie {

	/**
	 * Odczytanie ciastka
	 * 
	 * @param string $name - nazwa pobieranego ciastka
	 * @return mixed
	 */
	public static function decode($name) {
		/**
		 * @TODO Do zrobienia 'kiedyś'. Powinno działać tak jak decodeValue() w Mk.CookieProvider
		 * <code>
			  $cookie = null;
			  if( isset($_COOKIE[$name]) ) {
				  $exp = explode(':',$cookie);
				  if( isset($exp[1]) ) {
					  switch($exp[0]) {
						  case 'e': $cookie = null; break;
						  case 'n': $cookie = (int) $exp[1]; break;
						  case 'd': $cookie = date('Y-m-d',strtotime($exp[1])); break;
						  case 'b': $cookie = ($exp[1] == '1') ? true : false; break;
						  case 'a': // @TODO
						  case 'o': // @TODO
						  default: $cookie = $exp[1]; break;
					}
			  }
		  }
		  return $cookie;
		 * </code>
		 */
		return isset($_COOKIE[$name]) ? substr($_COOKIE[$name], 2) : null;
	}

}

