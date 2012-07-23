<?php

/**
 * MK_System_Build
 *
 * Model dla tabeli system_build
 *
 * @category    MK_System
 * @package        MK_System_Build
 *
 * @throws        MK_Db_Exception
 */
class MK_System_Build extends MK_Db_PDO
{

	/**
	 * @var string
	 */
	protected $tableName = 'system_build';

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

}

/*

CREATE OR REPLACE FUNCTION add_build(text, text)
  RETURNS integer AS
$BODY$
DECLARE
  version_id integer;
  version_build integer;
  id_build integer;
  patch_exist integer;
  app_version text;
BEGIN
	SELECT INTO app_version get_app_version();
	SELECT INTO version_id id FROM system_version WHERE subject = app_version;
	IF version_id IS NOT NULL THEN
		SELECT INTO patch_exist count(id) FROM system_changelog WHERE system_version_id = version_id AND branch_id = 0;
		IF patch_exist > 0 THEN
			SELECT INTO id_build nextval('system_build_id_seq');
			SELECT INTO version_build MAX(build_version) FROM system_build WHERE system_version_id = version_id;
			IF version_build IS NULL THEN
				version_build := 0;
			END IF;
			version_build := version_build + 1;
			IF $2 = '' THEN
				EXECUTE 'INSERT INTO system_build(id, system_version_id, build_version, release_date) VALUES ('|| id_build ||','|| version_id ||','|| version_build ||', '''|| $1 ||''');';
			ELSE
				EXECUTE 'INSERT INTO system_build(id, system_version_id, build_version, release_date, createdate) VALUES ('|| id_build ||','|| version_id ||','|| version_build ||', '''|| $1 ||''', '''|| $2 ||''');';
			END IF;
			EXECUTE 'UPDATE system_changelog SET branch_id = '|| id_build ||' WHERE system_version_id = '|| version_id ||' AND branch_id = 0;';
			RAISE NOTICE 'DODANO PATCH DO WERSJI % O NUMERZE %', app_version, version_build;
		END IF;
	ELSE
		RAISE NOTICE 'NIE ZNALEZIONO WYDANEJ WERSJI %', app_version;
		RETURN 0;
	END IF;
	RETURN 1;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE;

CREATE TABLE system_build
(
  id serial NOT NULL,
  system_version_id integer NOT NULL,
  build_version integer NOT NULL,
  release_date timestamp without time zone,
  createdate timestamp without time zone DEFAULT now(),
  CONSTRAINT system_build_pk PRIMARY KEY (id ),
  CONSTRAINT system_build_fk1 FOREIGN KEY (system_version_id)
      REFERENCES system_version (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

*/