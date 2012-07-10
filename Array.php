<?php
/**
 * MK_Array
 *
 * Klasa wspomagajaca operacje na tablicach
 *
 * @category MK
 * @package MK_Array
 * @throws MK_Exception
 * @author bskrzypkowiak
 */
class MK_Array
{

	/**
	 * @static
	 * Zamienia tablice na obiekt
	 *
	 * @param $array
	 *
	 * @return bool|stdClass
	 */
	public static function toObject($array)
	{
		if (!is_array($array)) {
			return $array;
		}

		$object = new stdClass();
		if (is_array($array) && count($array) > 0) {
			foreach ($array as $name => $value) {
				$name = strtolower(trim($name));
				if (!empty($name)) {
					$object->$name = self::toObject($value);
				}
			}
			return $object;
		}
		return false;
	}


}
