<?php

/**
 * MK_System_Build
 *
 * Model dla tabeli system_build
 *
 * @category	MK_System
 * @package		MK_System_Build
 *
 * @throws		MK_Db_Exception
 */
class MK_System_Build extends MK_Db_PDO {

	/**
	 * @var string
	 */
	protected $tableName = 'system_build';

	/**
	 * Odczytywanie wszystkich rekordów z tabeli
	 *
	 * @return array
	 */
	public function getList() {
		$sql = 'SELECT * FROM ' . $this->tableName
			. ' ORDER BY id DESC';
		return $this->GetRows($sql);
	}

}