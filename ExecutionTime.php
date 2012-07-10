<?php

/**
 * Klasa pomocna przy mierzeniu czasu wykonania kodu
 */
class MK_ExecutionTime
{
	/**
	 * Startowy czas w sekundach
	 *
	 * @var float
	 */
	private $_startTime;


	/**
	 * W konstruktorze ustawiany jest czas startowy
	 */
	public function __construct()
	{
		$this->_startTime = $this->_getActualTime();
	}


	/**
	 * Zwraca różnicę czasu od stworzenia obiektu do wywołania tej metody
	 *
	 * @return float
	 */
	public function getDifferenceTime()
	{
		return round($this->_getActualTime() - $this->_startTime, 3);
	}


	/**
	 * Zwraca aktualny czas w sekundach
	 *
	 * @return float
	 */
	private function _getActualTime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
}