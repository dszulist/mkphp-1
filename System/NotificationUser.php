<?php

/**
 * MK_System_NotificationUser
 *
 * Model dla tabeli system_notification_user
 *
 * @category	MK_System
 * @package		MK_System_NotificationUser
 *
 * @throws		MK_Db_Exception
 */
class MK_System_NotificationUser extends MK_Db_PDO {

	/**
	 * @var string
	 */
	protected $tableName = 'system_notification_user';

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

	/**
	 * Oznaczanie notatek/informacji jako przeczytanych dla wybranego użytkownika
	 *
	 * @param $notificationIds
	 * @param $userId
	 *
	 * @throws MK_Exception
	 * @return bool
	 */
	public function markAsRead($notificationIds, $userId) {
		$userId = (int) $userId;
		if( !is_array($notificationIds) || count($notificationIds) == 0 || $userId <= 0) {
			throw new MK_Exception('Nieprawidłowe parametry do oznaczania notatki jako przeczytana');
		}
		foreach($notificationIds as $notificationId) {
			$sql = 'INSERT INTO ' . $this->tableName . ' (notification_id, user_id) VALUES (?, ?)';
			$this->Execute($sql, array($notificationId, $userId));
		}
		return true;
	}

}