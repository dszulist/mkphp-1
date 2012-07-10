<?php

/**
 * MK_Cookie
 *
 * Obsługa ciasteczek zgodna z mkjs
 *
 * @category MK
 * @package    MK_Cookie
 * @author bskrzypkowiak
 */
class MK_Cookie
{

	/**
	 *
	 */
	CONST MESSAGE_ERROR_REFRESH = '<br />Proszę przeładować stronę, a w razie dalszych problemów skontaktować się z administratorem systemu.';

	/**
	 * Odczytanie ciastka
	 *
	 * @param string $name - nazwa pobieranego ciastka
	 *
	 * @return mixed
	 */
	public static function decode($name)
	{
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

	/**
	 * Ustawia pustą wartość ciastka i informuje przeglądarkę o jego wygaśnięciu.
	 *
	 * @param String     $name
	 */

	/**
	 * Ustawia wartość ciastka
	 *
	 * @param string $name
	 * @param string $value (default: '')
	 * @param integer $time (default: 0)
	 */
	public static function set($name, $value = '', $time = 0)
	{
		setcookie($name, $value, $time, MK_COOKIES_PATH);
	}

	/**
	 * Kasuje ciastko (ustawia puste z datą wygaśnięcia)
	 *
	 * @param string $name
	 * @param integer $timeDiff (default: 86400)
	 */
	public static function clear($name, $timeDiff = 86400)
	{
		if (isset($_COOKIE[$name])) {
			setcookie($name, '', time() - $timeDiff, MK_COOKIES_PATH); // 86400 = 24 * 60 * 60 (24h)
		}
	}

}

