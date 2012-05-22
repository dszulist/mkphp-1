<?php

/**
 * MK_System_Notification
 *
 * Model dla tabeli system_notification
 *
 * @category	MK_System
 * @package		MK_System_Notification
 *
 * @throws		MK_Db_Exception
 */
class MK_System_Notification extends MK_Db_PDO {

	/**
	 * @var string
	 */
	protected $tableName = 'system_notification';

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