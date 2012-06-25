<?php

/**
 * MK_System_Changelog
 *
 * Model dla tabeli system_changelog
 *
 * @category    MK_System
 * @package        MK_System_Changelog
 *
 * @throws        MK_Db_Exception
 */
class MK_System_Changelog extends MK_Db_PDO {

	/**
	 * @var string
	 */
	protected $tableName = 'system_changelog';

	/**
	 * Odczytywanie wszystkich rekordów z tabeli
	 *
	 * @return array
	 */
	public function getList() {
		$sql = 'SELECT c.* FROM ' . $this->tableName . ' c'
			. ' ORDER BY c.id DESC';
		return $this->GetRows($sql);
	}


	/**
	 * Odczytanie listy rejestru zmian powiązanych z identyfikatorami powiadomień
	 *
	 * @param array $notificationIds
	 *
	 * @return array
	 */
	public function getListByNotificationIds(array $notificationIds) {
		$sql = 'SELECT c.* FROM ' . $this->tableName . ' c'
			. ' WHERE c.notification_id IN (' . $this->arrayToQueryIn($notificationIds) . ') ORDER BY c.id ASC';
		return $this->GetRows($sql, $notificationIds);
	}

}