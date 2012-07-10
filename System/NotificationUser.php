<?php

/**
 * MK_System_NotificationUser
 *
 * Model dla tabeli system_notification_user
 *
 * @category    MK_System
 * @package        MK_System_NotificationUser
 *
 * @throws        MK_Db_Exception
 */
class MK_System_NotificationUser extends MK_Db_PDO
{

	/**
	 * @var string
	 */
	protected $tableName = 'system_notification_user';

	/**
	 * Odczytywanie wszystkich rekordów z tabeli
	 *
	 * @return array
	 */
	public function getList()
	{
		$sql = 'SELECT * FROM ' . $this->tableName
			. ' ORDER BY id DESC';
		return $this->GetRows($sql);
	}

	/**
	 * Oznaczanie notatek/informacji jako przeczytanych dla wybranego użytkownika
	 *
	 * @param array $notificationIds
	 * @param integer $userId
	 *
	 * @throws MK_Exception
	 * @return bool
	 */
	public function markAsRead(array $notificationIds, $userId)
	{
		$userId = (int)$userId;
		if (count($notificationIds) == 0 || $userId <= 0) {
			throw new MK_Exception('Nieprawidłowe parametry do oznaczania powiadomienia jako przeczytanego');
		}
		foreach ($notificationIds as $notificationId) {
			$sql = 'INSERT INTO ' . $this->tableName . ' (notification_id, user_id) VALUES (?, ?)';
			$this->Execute($sql, array($notificationId, $userId));
		}
		return true;
	}

}

/*

CREATE SEQUENCE system_notification_user_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

CREATE TABLE system_notification_user
(
  id serial NOT NULL,
  notification_id integer NOT NULL,
  user_id integer NOT NULL,
  createdate timestamp without time zone DEFAULT now(),
  CONSTRAINT system_notification_user_pk PRIMARY KEY (id ),
  CONSTRAINT system_notification_user_fk1 FOREIGN KEY (notification_id)
      REFERENCES system_notification (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

*/