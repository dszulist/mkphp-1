<?php
/**
 * ProxyClassAbstract
 *
 * Obsługa dziedziczenia po tablicy która jest przekazana jako argument
 *
 * @category    MK_XML
 * @package        MK_XML_ProxyClassAbstract
 * @author        bskrzypkowiak
 */
abstract class MK_XML_ProxyClassAbstract
{

	/**
	 * Konstruktor
	 */
	public function __construct()
	{
		if (func_num_args() > 0) {
			$arg = func_get_arg(0);
			if ($arg) {
				if (is_array($arg)) {
					foreach ($arg as $key => $val) {
						$this->$key = $val;
					}
				}
			}
		}
	}
}