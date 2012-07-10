<?php
/**
 * Wysłanie informacji z systemu do Brokera
 */
class sendClientSystemInformations
{

	/**
	 * Nazwa aplikacji
	 * @var string
	 */
	public $appName;

	/**
	 * Obiekt KeyVal
	 * @var array
	 */
	public $keyValArray;

	/**
	 * Przygotowanie sendClientSystemInformations - należy jeszcze uruchomić metodę add()
	 *
	 * @param $appName
	 */
	public function __construct($appName = null)
	{
		$this->appName = is_null($appName) && defined('APP_NAME') ? APP_NAME : $appName;
		$this->keyValArray = array();
	}

	/**
	 * Dodanie elementów do wysłania do Brokera
	 *
	 * @param      $key - klucz
	 * @param      $value - pojedyncza wartość lub tablica asocjacyjna
	 * @param null $options - wartość nieużywana (domyślnie taka sama wartość jak klucz)
	 *
	 * @return \sendClientSystemInformations
	 */
	public function add($key, $value, $options = null)
	{
		$keyVal = new KeyVal();
		$keyVal->key = $key;
		$keyVal->value = is_array($value) ? json_encode($value) : $value;
		$keyVal->options = empty($options) ? $key : $options;
		$this->keyValArray[] = $keyVal;
		return $this;
	}

}