<?php

/**
 * MK_Sys_Notification
 *
 * Model dla tabeli sys_notification
 *
 * @category    MK_System
 * @package        MK_Sys_Notification
 *
 * @throws        MK_Db_Exception
 */
class MK_Sys_Notification extends MK_Db_PDO
{

    /**
     * @var string
     */
    protected $tableName = 'sys_notification';

    /**
     * Odczytywanie wszystkich rekordów z tabeli
     *
     * @param      $userId
     * @param bool $withChangelog - czy pobierać dodatkowo listę rejestru zmian
     *
     * @throws MK_Exception
     * @return array
     */
    public function getList($userId, $withChangelog = true)
    {
        $userId = (int)$userId;
        if($userId <= 0) {
            throw new MK_Exception('Nieprawidłowe parametry do odczytania listy nowych powiadomień');
        }

        $sql = 'SELECT sn.* FROM ' . $this->tableName . ' sn '
            . ' LEFT JOIN sys_notification_user snu ON (sn.id = snu.notification_id AND snu.user_id = ?) WHERE snu.user_id IS NULL ORDER BY id DESC';
        $notifications = $this->GetRows($sql, array ($userId), 'id');

        // Odczytywanie rejestru zmian dla powiadomień
        if($withChangelog == true) {
            $notificationIds = array ();
            foreach ($notifications as &$notification) {
                $notificationIds[] = $notification['id'];
                $notification['changelogs'] = array ();
            }
            $changelogDb = new MK_Sys_Changelog();
            $changelogs = $changelogDb->getListByNotificationIds($notificationIds);
            foreach ($changelogs as &$changelog) {
                $notifications[$changelog['notification_id']]['changelogs'][] = $changelog;
            }
        }

        return array_values($notifications);
    }

}

/*

CREATE OR REPLACE FUNCTION add_notification(text, text, text)
  RETURNS integer AS
$BODY$
DECLARE
  notification_id integer;
  desc_exists text;
BEGIN
	SELECT INTO notification_id id FROM sys_notification WHERE subject = $1;
	IF NOT FOUND THEN
		EXECUTE 'INSERT INTO sys_notification(subject, description, notification_date) VALUES ('||quote_literal($1)||','||quote_literal($2)||', '||quote_literal($3)||');';
	ELSE
		RAISE NOTICE 'ISTNIEJE POWIADOMIENIE O TEMACIE: %',$1;
		RETURN 0;
	END IF;
	RETURN 1;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE;

CREATE SEQUENCE sys_notification_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

CREATE TABLE sys_notification
(
  id serial NOT NULL,
  subject character varying(200),
  description text,
  notification_date date NOT NULL,
  notice_type smallint DEFAULT 0,
  createdate timestamp without time zone DEFAULT now(),
  CONSTRAINT sys_notification_pk PRIMARY KEY (id )
);

*/