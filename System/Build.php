<?php

/**
 * MK_System_Build
 *
 * Model dla tabeli system_build
 *
 * @category	MK_System
 * @package		MK_System_Build
 *
 * @throws		MK_Db_Exception
 */
class MK_System_Build extends MK_Db_PDO {

	/**
	 * @var string
	 */
	protected $tableName = 'system_build';

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

}

/*

CREATE TABLE system_build
(
  id serial NOT NULL,
  system_version_id integer NOT NULL,
  build_version integer NOT NULL,
  createdate timestamp without time zone DEFAULT now(),
  release_date timestamp without time zone,
  CONSTRAINT system_build_pk PRIMARY KEY (id ),
  CONSTRAINT system_build_fk1 FOREIGN KEY (system_version_id)
      REFERENCES system_version (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

*/