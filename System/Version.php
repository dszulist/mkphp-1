<?php

/**
 * MK_System_Version
 *
 * Model dla tabeli system_version
 *
 * @category    MK_System
 * @package        MK_System_Version
 *
 * @throws        MK_Db_Exception
 */
class MK_System_Version extends MK_Db_PDO
{

	/**
	 * @var string
	 */
	protected $tableName = 'system_version';

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

CREATE SEQUENCE system_version_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

CREATE TABLE system_version
(
  id bigserial NOT NULL,
  subject character varying NOT NULL,
  content character varying,
  adding_date timestamp without time zone,
  change_date date,
  CONSTRAINT system_version_pk PRIMARY KEY (id )
);

*/