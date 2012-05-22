<?php

/**
 * MK_System_Version
 *
 * Model dla tabeli system_version
 *
 * @category	MK_System
 * @package		MK_System_Version
 *
 * @throws		MK_Db_Exception
 */
class MK_System_Version extends MK_Db_PDO {

	/**
	 * @var string
	 */
	protected $tableName = 'system_version';

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