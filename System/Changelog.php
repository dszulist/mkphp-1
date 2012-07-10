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
class MK_System_Changelog extends MK_Db_PDO
{

	/**
	 * @var string
	 */
	protected $tableName = 'system_changelog';

	/**
	 * Odczytywanie wszystkich rekordów z tabeli
	 *
	 * @return array
	 */
	public function getList()
	{
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
	public function getListByNotificationIds(array $notificationIds)
	{
		$sql = 'SELECT c.* FROM ' . $this->tableName . ' c'
			. ' WHERE c.notification_id IN (' . $this->arrayToQueryIn($notificationIds) . ') ORDER BY c.id ASC';
		return $this->GetRows($sql, $notificationIds);
	}

}

/*

CREATE TABLE system_changelog
(
  id serial NOT NULL,
  notification_id integer DEFAULT 0,
  system_version_id integer,
  branch_id integer NOT NULL DEFAULT 0,
  patch_type character(1) NOT NULL DEFAULT 'b'::bpchar,
  bt_id character varying(1024),
  description text NOT NULL DEFAULT ''::text,
  description_more text,
  createdate timestamp(0) without time zone NOT NULL DEFAULT now(),
  CONSTRAINT system_changelog_pk PRIMARY KEY (id )
);

*/