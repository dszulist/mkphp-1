<?php

/**
 * MK_Sys_Changelog
 *
 * Model dla tabeli sys_changelog
 *
 * @category    MK_System
 * @package        MK_Sys_Changelog
 *
 * @throws        MK_Db_Exception
 */
class MK_Sys_Changelog extends MK_Db_PDO
{

    /**
     * @var string
     */
    protected $tableName = 'sys_changelog';

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

CREATE OR REPLACE FUNCTION add_changelog(text, text, text, character, text)
  RETURNS integer AS
$BODY$
DECLARE
  version_id integer;
  desc_exists text;
BEGIN
	SELECT INTO version_id id FROM system_version WHERE subject = $1;
	IF version_id IS NOT NULL THEN
		SELECT INTO desc_exists description FROM sys_changelog WHERE description = $2 AND system_version_id = version_id;
		IF NOT FOUND THEN
			EXECUTE 'INSERT INTO sys_changelog(system_version_id, description, description_more, patch_type, bt_id) VALUES ('||version_id||','|| quote_literal($2) ||', '|| quote_literal($3) ||', '''|| $4 ||''','|| quote_literal($5) ||');';
		ELSE
			EXECUTE 'UPDATE sys_changelog SET description_more = '|| quote_literal($3) || ' WHERE description = '|| quote_literal($2) || ' AND system_version_id = '||version_id;
		END IF;
	ELSE
		RAISE NOTICE 'NIE ZNALEZIONO WYDANEJ WERSJI %',$1;
		RETURN 0;
	END IF;
	RETURN 1;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE;

CREATE TABLE sys_changelog
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
  CONSTRAINT sys_changelog_pk PRIMARY KEY (id )
);

*/