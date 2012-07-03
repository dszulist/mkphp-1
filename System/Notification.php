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
	 * @param      $userId
	 * @param bool $withChangelog - czy pobierać dodatkowo listę rejestru zmian
	 *
	 * @throws MK_Exception
	 * @return array
	 */
	public function getList($userId, $withChangelog = true) {
        $userId = (int) $userId;
        if($userId <= 0) {
            throw new MK_Exception('Nieprawidłowe parametry do odczytania listy nowych powiadomień');
        }

		$sql = 'SELECT sn.* FROM ' . $this->tableName .' sn '
			. ' LEFT JOIN system_notification_user snu ON (sn.id = snu.notification_id AND snu.user_id = ?) WHERE snu.user_id IS NULL ORDER BY id DESC';
		$notifications = $this->GetRows($sql, array($userId), 'id');

		// Odczytywanie rejestru zmian dla powiadomień
		if($withChangelog == true) {
			$notificationIds = array();
			foreach($notifications as &$notification) {
				$notificationIds[] = $notification['id'];
				$notification['changelogs'] = array();
			}
			$changelogDb = new MK_System_Changelog();
			$changelogs = $changelogDb->getListByNotificationIds($notificationIds);
			foreach($changelogs as &$changelog) {
				$notifications[$changelog['notification_id']]['changelogs'][] = $changelog;
			}
		}

		return array_values($notifications);
	}

}

/*

CREATE SEQUENCE system_notification_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

CREATE TABLE system_notification
(
  id serial NOT NULL,
  subject character varying(200),
  description text,
  notification_date date NOT NULL,
  notice_type smallint DEFAULT 0,
  createdate timestamp without time zone DEFAULT now(),
  CONSTRAINT system_notification_pk PRIMARY KEY (id )
);

*/