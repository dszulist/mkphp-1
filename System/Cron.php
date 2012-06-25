<?php

/**
 * MK_System_Cron
 *
 * Model dla tabeli system_cron
 *
 * @category	MK_System
 * @package		MK_System_Cron
 *
 * @throws		MK_Db_Exception
 */
class MK_System_Cron extends MK_Db_PDO {

	/**
	 * @var string
	 */
	protected $tableName = 'system_cron';

	/**
	 * Odczytywanie wszystkich rekordów z tabeli
	 *
	 * @return array
	 */
	public function getList() {
		return $this->GetRows("SELECT * FROM {$this->tableName} ORDER BY id ASC", array(), 'id');
	}

	/**
	 * Odczytywanie wszystkich aktywnych rekordów z tabeli
	 *
	 * @return array
	 */
	public function getActiveList() {
		return $this->GetRows("SELECT * FROM {$this->tableName} WHERE state = ? ORDER BY sequence_order DESC", array('A'), 'id');
	}

	/**
	 * Oznaczenie zadania, że jest w trakcie wykonywania / zakończone
	 *
	 * @param      $id
	 * @param bool $finished
	 * @param int  $currentTime
	 *
	 * @return int
	 */
	public function setTaskExecuted($id, $finished = false, $currentTime = 0) {
		$timeStamp = date("Y-m-d H:i:s", $currentTime > 0 ? $currentTime : time());
		if($finished === true) {
			return $this->Execute("UPDATE {$this->tableName} SET last_exec = ?, exec_lock = ?, error_lock = ? WHERE id = ?", array($timeStamp, null, null, $id));
		}
		else {
			return $this->Execute("UPDATE {$this->tableName} SET exec_lock = ? WHERE id = ?", array($timeStamp, $id));
		}
	}

	/**
	 * Oznaczenie zadania, że wystąpił jakiś błąd (szczegóły w logach)
	 *
	 * @param     $id
	 * @param int $currentTime
	 *
	 * @return int
	 */
	public function setTaskError($id, $currentTime = 0) {
		$timeStamp = date("Y-m-d H:i:s", $currentTime > 0 ? $currentTime : time());
		return $this->Execute("UPDATE {$this->tableName} SET error_lock = ? WHERE id = ?", array($timeStamp, (int)$id));
	}

}