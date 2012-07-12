<?php

/**
 * MK_Stream_CouchDB
 *
 * Klasa do obsłgi couchdb
 *
 * @category MK_Steam
 * @package MK_Stream_CouchDB
 */
class MK_Stream_CouchDB
{
	/**
	 * Uchwyt do bazy danych
	 * @var MK_CouchDB
	 */
	private $db;

	/**
	 * Utworzenie połączenia z bazą
	 *
	 * @param $path
	 * @param $mode
	 * @param $options
	 * @param $opened_path
	 *
	 * @return bool
	 */
	function stream_open($path, $mode, $options, &$opened_path)
	{
		$this->db = new \MK_CouchDB($path);
		return true;
	}

	/**
	 * Wysyła inserta do bazy
	 *
	 * @param string $data JSON
	 * @param null $lenght
	 *
	 * @return int
	 */
	function stream_write($data, $lenght = null)
	{
		$this->db->add(json_decode($data, true));
		return strlen($data);
	}

	/**
	 * @todo do zrobienia
	 * @param $path
	 * @return mixed
	 */
	public function unlink($path)
	{
		return parse_url($path);
	}

	/**
	 * @todo do zrobienia
	 * Póki co nie widze potrzeby oprogramowywania tego, nie będziemy czytać streamem z couchdb :)
	 *
	 * @param null $count
	 *
	 * @return string
	 */
	function stream_read($count = null)
	{
		return '';
	}

}