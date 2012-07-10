<?php
/**
 * Pomocnicza klasa dla sendClientSystemInformations
 */
class KeyVal
{

	/**
	 * Klucz, np. 'count_tables_row'
	 * @var string
	 */
	public $key;

	/**
	 * Wartość, np. '123' lub json_encode(array('table1' => 123, 'table2' => 234))
	 * Podanie pojedynczej wartości będzie uznane jako:
	 *  count_tables_row => 123
	 * Podanie tablicy asocjacyjnej będzie dodatkowo interpretowane jako tablica klucz/wartość w szczegółach GO
	 * @var string|array
	 */
	public $value;

	/**
	 * Nazwa opcji (niewykorzystywane) - takie samo jak klucz, np. 'count_tables_row'
	 * @var
	 */
	public $options;

}