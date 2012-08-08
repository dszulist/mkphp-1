<?php

/**
 * MK_Sys_Cron
 *
 * Model dla tabeli sys_cron
 *
 * @category    MK_System
 * @package        MK_Sys_Cron
 *
 * @throws        MK_Db_Exception
 */
class MK_Sys_Cron extends MK_Db_PDO
{

    /**
     * @var string
     */
    protected $tableName = 'sys_cron';

    /**
     * Odczytywanie wszystkich rekordów z tabeli
     *
     * @return array
     */
    public function getList()
    {
        return $this->GetRows("SELECT * FROM {$this->tableName} ORDER BY id ASC", array (), 'id');
    }

    /**
     * Odczytywanie wszystkich aktywnych rekordów z tabeli
     *
     * @return array
     */
    public function getActiveList()
    {
        return $this->GetRows("SELECT * FROM {$this->tableName} WHERE state = ? ORDER BY sequence_order DESC", array ('A'), 'id');
    }

    /**
     * Oznaczenie zadania, że jest w trakcie wykonywania / zakończone
     *
     * @param      $id
     * @param bool $finished
     * @param int  $currentTime
     *
     * @return int
     */
    public function setTaskExecuted($id, $finished = false, $currentTime = 0)
    {
        $timeStamp = date("Y-m-d H:i:s", $currentTime > 0 ? $currentTime : time());
        if($finished === true) {
            return $this->Execute("UPDATE {$this->tableName} SET last_exec = ?, exec_lock = ?, error_lock = ? WHERE id = ?", array ($timeStamp, null, null, $id));
        } else {
            return $this->Execute("UPDATE {$this->tableName} SET exec_lock = ? WHERE id = ?", array ($timeStamp, $id));
        }
    }

    /**
     * Oznaczenie zadania, że wystąpił jakiś błąd (szczegóły w logach)
     *
     * @param     $id
     * @param int $currentTime
     *
     * @return int
     */
    public function setTaskError($id, $currentTime = 0)
    {
        $timeStamp = date("Y-m-d H:i:s", $currentTime > 0 ? $currentTime : time());
        return $this->Execute("UPDATE {$this->tableName} SET error_lock = ? WHERE id = ?", array ($timeStamp, (int)$id));
    }

}

/*

CREATE SEQUENCE sys_cron_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;

CREATE TABLE sys_cron
(
  id integer NOT NULL DEFAULT nextval('sys_cron_seq'::regclass),
  expression character varying DEFAULT '* * * * *'::character varying,
  task_name character varying NOT NULL,
  php_class character varying NOT NULL,
  php_method character varying NOT NULL,
  php_argv character varying,
  sequence_order integer NOT NULL DEFAULT 0,
  state character varying(1) DEFAULT 'A'::character varying,
  last_exec timestamp without time zone,
  exec_lock timestamp without time zone,
  error_lock timestamp without time zone,
  CONSTRAINT sys_cron_pk PRIMARY KEY (id )
);

COMMENT ON COLUMN sys_cron.id
 IS 'Identyfikator zaplanowanego zadania';
COMMENT ON COLUMN sys_cron.expression
 IS 'Format crontab: "Minute / Hour / Day of Month / Month / Day of Week"';
COMMENT ON COLUMN sys_cron.task_name
 IS 'Nazwa zaplanowanego zadania';
COMMENT ON COLUMN sys_cron.php_class
 IS 'Nazwa klasy, która zostanie uruchomiona';
COMMENT ON COLUMN sys_cron.php_method
 IS 'Nazwa metody, która zostanie uruchomiona';
COMMENT ON COLUMN sys_cron.php_argv
 IS 'Parametry przekazywane do metody oddzielone spacją (tak jak w konsoli)';
COMMENT ON COLUMN sys_cron.sequence_order
 IS 'Kolejność wykonywania zadań z crona - jeśli zadanie ma mieć wyższy priorytet (wcześniej uruchomione), to należy podać liczbę wyższą niż pozostałe wartości w tabeli';
COMMENT ON COLUMN sys_cron.state
 IS 'Status zaplanowanego zadania:
A - Aktywne
I - Nieaktywne
D - Usunięte';
COMMENT ON COLUMN sys_cron.last_exec
 IS 'W momencie uruchomienia/wykonywania zadania należy ustawić locka, który nie pozwoli na ponowne uruchomienie zadania, gdy jest już wykonywane';
COMMENT ON COLUMN sys_cron.exec_lock
 IS 'NOW()+5m => jeżeli zadanie długo się wykonuje, to nie będzie sprawdzane czy istnieje taka potrzeba, aby ponownie uruchomić';
COMMENT ON COLUMN sys_cron.error_lock
 IS 'NOW()+1h => jeżeli wystąpi jakikolwiek błąd podczas wcześniejszego wykonywania zadania z crona, to blokowane jest na godzinę czasu';

*/