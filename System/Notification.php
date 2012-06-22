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
	 * @param $userId
	 *
	 * @throws MK_Exception
	 * @return array
	 */
	public function getList($userId) {
        $userId = (int) $userId;
        if($userId <= 0) {
            throw new MK_Exception('Nieprawidłowe parametry do odczytania listy nowych powiadomień');
        }

		$sql = 'SELECT sn.* FROM ' . $this->tableName .' sn '
			. ' LEFT JOIN system_notification_user snu ON (sn.id = snu.notification_id AND snu.user_id = ?) WHERE snu.user_id IS NULL ORDER BY id DESC';
		return $this->GetRows($sql, array($userId));
	}

}