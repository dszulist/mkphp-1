<?php

/**
 * MK_Db_PDO
 *
 * Klasa PDO
 *
 * @category	MK_Db
 * @package		MK_Db_PDO
 *
 * @throws		MK_Db_Exception
 */
class MK_Db_PDO {

	/**
	 * Komunikat pomyslnego zapisania danych
	 */
	CONST MESSAGE_SUCCESS_SAVE = 'Pomyślnie zapisano zmiany';

	/**
	 * Komunikat pomyslnego usuniecia rekordu
	 */
	CONST MESSAGE_SUCCESS_DELETE = 'Pomyślnie usunięto rekord';

	/**
	 * Singleton połączenia z bazą danych (MK_Db_PDO_Singleton)
	 *
	 * @access protected
	 * @var object
	 */
	protected $db = null;

	/**
	 * Przechowywanie obiektu Mk_Logs do debugowania SQL-i
	 *
	 * @access private
	 * @var object
	 */
	private $_mkLogs = null;

	/**
	 * Ignorowane klasy w debug_backtrace dla SQL-i [fireBugSqlDump()]
	 * Można rozszerzyć w dowolnym momencie poprzez setMoreSqlIgnoreClass()
	 *
	 * @access private
	 * @var array
	 */
	private $_sqlIgnoreClass = array('MK_Db_PDO');

	/**
	 * @var String
	 */
	protected $_debugFileName;

	/**
	 * @var Array
	 */
	protected $_tableColumns;

	/**
	 * @var Array
	 */
	protected $fieldsCanBeSearched;

	/**
	 * Nazwa tabeli modelu bazy danych
	 *
	 * @var String
	 */
	protected $_tableName;

	/**
	 * Konstruktor
	 */
	public function __construct() {
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = MK_DEBUG_FIREPHP ? microtime(true) : 0;

		// Połączenie z bazą danych (singleton)
		$this->db = MK_Db_PDO_Singleton::getInstance();

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("MK_Db_PDO_Singleton::getInstance()", '', array(), microtime(true) - $timeStart);
		}
	}

	/**
	 * Ustawienie większej ilości klas do ignorowania dla fireBugSqlDump()
	 *
	 * @param array $classArray
	 */
	public function setMoreSqlIgnoreClass($classArray) {
		$this->_sqlIgnoreClass = array_merge($this->_sqlIgnoreClass, $classArray);
	}

	/**
	 * Odczytanie ostatniego błędu bazy danych
	 *
	 * @return string
	 */
	public function getErrorMsg() {
		$errorInfo = MK_Db_PDO_Singleton::getInstance()->errorInfo();
		return isset($errorInfo[2]) ? $errorInfo[2] : null;
	}

	/**
	 * Pobiera aktualną wersję aplikacji z bazy danych
	 *
	 * @return string
	 */
	public function getAppVersion() {
		return $this->GetOne('SELECT get_app_version()');
	}

	/**
	 * Wykonanie przygotowanego zapytania SQL. Nie są pobierane żadne dane.
	 * Zwracany ilość zmienionych rekordów.
	 *
	 * @param String	 $sql - zapytanie sql'owe
	 * @param Array      $params (default: array()) - parametry zapytania
	 *
	 * @throws MK_Db_Exception
	 * @return integer
	 */
	protected function Execute($sql, array $params = array()) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = MK_DEBUG_FIREPHP ? microtime(true) : 0;

		// Jeżeli zostały podany parametry, to wykonujemy zapytanie przy pomocy prepare/execute
		// W przeciwnym wypadku uruchiamy zapytanie poprzez exec(), które umożliwia wykonanie wielu zapytań SQL
		if(count($params) > 0) {
			$pdoObj = $this->db->prepare($sql);
			if($pdoObj->execute($params) === false) {
				throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_RESULTS);
			}
			$affectedRows = $pdoObj->rowCount();
		} else {
			$results = $this->db->exec($sql);
			if($results === false) {
				throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_RESULTS);
			}
			$affectedRows = $results;
		}

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("DbExecute", $sql, $params, microtime(true) - $timeStart);
		}

		// Jeżeli jest włączone debugowanie, to SQL-e zapisywane są do pliku debug.log
		$this->_debugToFile($sql, $params);

		// Ilość zmodyfikowanych wierszy
		return $affectedRows;
	}

	/**
	 * Odczytanie tylko pojedynczej wartości (pierwszej kolumny w zapytaniu).
	 * Brany jest pod uwagę tylko jeden rekord, a zwracana wartość typu string.
	 *
	 * @param String    $sql - zapytanie sql'owe
	 * @param Array	 $params (default: array()) - parametry zapytania
	 *
	 * @throws MK_Db_Exception
	 * @return string
	 */
	protected function GetOne($sql, array $params = array()) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = MK_DEBUG_FIREPHP ? microtime(true) : 0;

		// Wykonanie zapytania SQL
		if($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_RESULTS);
		}

		// Odczytanie odpowiedzi (string)
		$resString = $pdoObj->fetchColumn();

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pusty string
		// W aplikacji będziemy się spodziewać pustego stringa, a nie 'false'
		if($resString === false) {
			$resString = '';
		}

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("DbGetOne", $sql, $params, microtime(true) - $timeStart);
		}

		// Jeżeli jest włączone debugowanie, to SQL-e zapisywane są do pliku debug.log
		$this->_debugToFile($sql, $params);

		return $resString;
	}

	/**
	 * Odczytanie tej samej kolumny z wszystkich wierszy zapytania SQL.
	 * Odczytane dane są w postaci tablicy jednowymiarowej, indeksowanej od zera.
	 *
	 * @param String	 $sql
	 * @param Array      $params (default: array())
	 *
	 * @throws MK_Db_Exception
	 * @return array
	 */
	protected function GetCol($sql, $params = array()) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = MK_DEBUG_FIREPHP ? microtime(true) : 0;

		// Wykonanie zapytania SQL
		if($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_RESULTS);
		}
		// Odczytanie odpowiedzi (array)
		$resArray = $pdoObj->fetchAll(PDO::FETCH_COLUMN);

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pustą tablicę
		// W aplikacji będziemy się spodziewać pustej tablicy, a nie 'false'
		// WARNING: count(false) == 1 [co sugerowałoby, że jest to tablica!]
		if($resArray === false) {
			$resArray = array();
		}

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("DbGetCol", $sql, $params, microtime(true) - $timeStart);
		}

		// Jeżeli jest włączone debugowanie, to SQL-e zapisywane są do pliku debug.log
		$this->_debugToFile($sql, $params);

		return $resArray;
	}

	/**
	 * Odczytanie tylko jednego wiersza z podanego zapytania SQL.
	 *
	 * @param String	 $sql - zapytanie sql'owe
	 * @param Array      $params (default: array()) - parametry zapytania
	 *
	 * @throws MK_Db_Exception
	 * @return array
	 */
	protected function GetRow($sql, array $params = array()) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = MK_DEBUG_FIREPHP ? microtime(true) : 0;

		// Wykonanie zapytania SQL
		if($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_RESULTS);
		}
		// Odczytanie odpowiedzi (array)
		$resArray = $pdoObj->fetch();

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pustą tablicę
		// W aplikacji będziemy się spodziewać pustej tablicy, a nie 'false'
		// WARNING: count(false) == 1 [co sugerowałoby, że jest to tablica!]
		if($resArray === false) {
			$resArray = array();
		}

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("DbGetRow", $sql, $params, microtime(true) - $timeStart);
		}

		// Jeżeli jest włączone debugowanie, to SQL-e zapisywane są do pliku debug.log
		$this->_debugToFile($sql, $params);

		return $resArray;
	}

	/**
	 * Odczytanie wszystkich wierszy z podanego zapytania SQL.
	 * Podanie parametru $columnAsKey pozwoli odczytać dane w postaci tablicy,
	 * której kluczem (indeksem) będzie wartość podanej kolumny, np. sid
	 *
	 * @param String	 $sql - zapytanie sql'owe
	 * @param Array      $params (default: array()) - parametry zapytania
	 * @param String     $columnAsKey (default:'') - nazwa kolumny jako klucz
	 *
	 * @throws MK_Db_Exception
	 * @return array
	 */
	protected function GetRows($sql, array $params = array(), $columnAsKey = '') {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = MK_DEBUG_FIREPHP ? microtime(true) : 0;

		// Wykonanie zapytania SQL
		if($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_RESULTS);
		}

		// Odczytanie odpowiedzi (array)
		$resArray = array();
		if(strlen($columnAsKey) > 0) {
			$sqlDumpName = "DbGetRows['{$columnAsKey}']";
			while($row = $pdoObj->fetch(PDO::FETCH_ASSOC)) {
				$resArray[$row[$columnAsKey]] = $row;
			}
		} else {
			$sqlDumpName = "DbGetRows";
			$resArray = $pdoObj->fetchAll();
		}

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pustą tablicę
		// W aplikacji będziemy się spodziewać pustej tablicy, a nie 'false'
		// WARNING: count(false) == 1 [co sugerowałoby, że jest to tablica!]
		if($resArray === false) {
			$resArray = array();
		}

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump($sqlDumpName, $sql, $params, microtime(true) - $timeStart);
		}

		// Jeżeli jest włączone debugowanie, to SQL-e zapisywane są do pliku debug.log
		$this->_debugToFile($sql, $params);

		return $resArray;
	}

	/**
	 * Sprawdzenie ilości powiązań dla podanego klucza podstawowego w schemacie "public".
	 * Jako wynik zwracana ilość rekordów w tabelach powiązanych w schemacie "public" dla wartości kolumny równej "pkey_value".
	 *
	 * @param $primaryKeyName
	 * @param $primaryKeyValue
	 *
	 * @return int
	 */
	protected function countKeyUsage($primaryKeyName, $primaryKeyValue) {
		// Przygotowanie zapytania SQL
		$sql = "SELECT mk_count_pkey('{$primaryKeyName}', {$primaryKeyValue})";
		return (int) $this->GetOne($sql);
	}

	/**
	 * Odczytanie kolejnej wartości sekwencji (inkrementowanej w bazie danych)
	 *
	 * @param String	 $sequence - nazwa sekwencji
	 *
	 * @throws MK_Db_Exception
	 * @return Float/Integer
	 */
	function setNextVal($sequence) {
		// Przygotowanie zapytania SQL
		$sql = "SELECT nextval('{$sequence}')";
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);

		// Wykonanie zapytania SQL
		if($pdoObj->execute() === false) {
			throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_RESULTS);
		}

		// Odczytanie odpowiedzi (array)
		$resValue = $pdoObj->fetchColumn();

		// Jeżeli odpowiedź będzie false, to operacja powinna być wstrzymana,
		// ponieważ wartość sekwencji jest nieprawidłowa przez co dane zostałyby
		// zapisane w bazie danych w nieodpowiedni sposób.
		if($resValue === false) {
			throw new MK_Db_Exception(MK_Db_PDO_Singleton::MESSAGE_ERROR_SEQUENCE);
		}

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("setNextVal", $sql);
		}

		// Jeżeli jest włączone debugowanie, to SQL-e zapisywane są do pliku debug.log
		$this->_debugToFile($sql);

		return $resValue;
	}

	/**
	 * Rozpoczęcie głównego bloku transakcji: DB->StartTrans()
	 *
	 * 1. Bloki transakcji można zagnieżdzać, ale nie jest wtedy uruchamiana nowa transakcja.
	 * Inkrementowana jest jedynie wartość $this->transOff.
	 * Podczas wywoływania CompleteTrans() wartość $this->transOff jest pomniejszana o 1.
	 *
	 * 2. Dopóki nie uruchomimy metody CompleteTrans(), to cała zawartość bloku będzie
	 * uruchomiona z domyślnym Rollback-iem (sekwencje zostaną "przebite",
	 * ale operacje na wierszach zostaną przywrócone).
	 *
	 * 3. Pomiędzy StartTrans() a CompleteTrans() metody BeginTrans/CommitTrans/RollbackTrans
	 * zostaną zablokowane (nieaktywne).
	 *
	 * 4. Wywołanie metody CompleteTrans() śledzi błędy, więc jeśli wystąpił jakiś błąd SQL
	 * lub została wywołana wcześniej metoda FailTrans(), to zostanie uruchomiony ROLLBACK.
	 *
	 * @throws MK_Db_Exception
	 * @return bool
	 */
	public function transStart() {
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("transStart");
		}

		if(MK_Db_PDO_Singleton::transCount() > 0) {
			MK_Db_PDO_Singleton::transCount(1);
			return true;
		}

		/**
		 *   true  - transakcja została utworzona
		 *   false - baza danych nie obsługuje transakcji
		 */
		MK_Db_PDO_Singleton::transOk(false);
		$transOk = $this->db->beginTransaction();
		if(!$transOk) {
			throw new MK_Db_Exception('Baza danych nie obsługuje transakcji');
		}

		MK_Db_PDO_Singleton::transOk(true);
		MK_Db_PDO_Singleton::transCount(1, true);

		return $transOk;
	}

	/**
	 * Zatwierdzenie SQL-i głównego bloku transakcji, gdy $this->transOff == 1
	 * oraz gdy nie wystąpiły żadne błędy SQL-owe.
	 *
	 * @param boolean $commit
	 *   true  - monitoruje błędy SQL,
	 *   false - wymuszenie odrzucenia wszystkich SQL-i w transakcji
	 *
	 * @throws MK_Db_Exception
	 * @return bool
	 */
	public function transComplete($commit = true) {
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("transComplete(" . ($commit ? 'true' : 'false') . ")");
		}

		$_transCount = MK_Db_PDO_Singleton::transCount();

		if($_transCount > 1) {
			// Transakcja jest w innej transakcji, zamykanie bloku transakcji
			MK_Db_PDO_Singleton::transCount(-1);
			return true;
		} else if($_transCount == 1) {
			// Jeśli transakcja nie została przerwana, to należy zamknąć logi
			if($commit == true) {
				if(class_exists('TableLogsDb')) {
					$tableLogsDb = new TableLogsDb();
					$tableLogsDb->closeConnectionForTableLog();
				}
			}
			// Transakcja jest do zamknięcia
			MK_Db_PDO_Singleton::transCount(0, true);
		} else if($_transCount == 0) {
			// Transakcja nie była uruchomiona
			return false;
		} else {
			// Do takiego błędu nie powinno w ogóle dojść, ale należałoby się przed tym zabezpieczyć...
			throw new MK_Db_Exception('Transakcja wywołała niespodziewany błąd. Poinformuj administratora systemu.');
		}

		/**
		 * true  - COMMIT
		 * false - ROLLBACK
		 */
		if($commit && MK_Db_PDO_Singleton::transOk()) {
			if(!$this->db->commit()) {
				MK_Db_PDO_Singleton::transOk(false);
				throw new MK_Db_Exception('Transakcja nie powiodła się');
			}
		} else {
			MK_Db_PDO_Singleton::transOk(false);
			$this->db->rollBack();
		}

		return MK_Db_PDO_Singleton::transOk();
	}

	/**
	 * Zablokowanie COMMIT dla danej transakcji.
	 * Ustawienie transakcji na fail
	 * Cofnięcie całej transakcji (wymuszenie rollBack)
	 */
	public function transFail() {
		MK_Db_PDO_Singleton::transOk(false);
		$this->transComplete(false);
	}

	/**
	 * Włączenie (true) lub wyłączenie (false) debugowania zapytań SQL.
	 * Zapisywanie zapytań SQL do pliku /temp/errors/sql-debug.log i upload do logs.madkom.pl (od Klienta)
	 *
	 * @param boolean $debug (default: true)
	 * @param string  $fileName (default:'sql-debug')
	 */
	public function debug($debug = true, $fileName = 'sql-debug') {
		MK_Db_PDO_Singleton::debug($debug);
		if($debug === true) {
			$this->_debugFileName = $fileName;
		}
	}

	/**
	 * Jeżeli debugowanie jest włączone, to zapisuje wykonane zapytanie SQL do pliku *.log
	 *
	 * @param $sql
	 * @param $params (default:array())
	 *
	 * @internal param bool $debug (default: true)
	 */
	private function _debugToFile($sql, $params = array()) {
		if(MK_Db_PDO_Singleton::debug() === true) {
			if(is_null($this->_mkLogs)) {
				$this->_mkLogs = new MK_Logs(APP_PATH);
			}
			$devMsg = $this->_prepareFullQuery($sql, $params);
			$this->_mkLogs->saveToFile($this->_debugFileName, $devMsg);
		}
	}

	/**
	 * Metodę SelectLimit. Jeżeli podamy id klucza i nazwę, to pobiera numer strony na której znajduje się rekord.
	 *
	 * @param String      $sql - zapytanie sql'owe
	 * @param Array       $params (default: array()) - parametry zapytania
	 * @param String      $primaryName (default: null) - nazwa klucza
	 * @param int|\String $primaryVal (default: 0) - wartość klucza
	 * @param bool|int    $start (default: false) - start
	 * @param bool|int    $limit (default: false) - limit
	 *
	 * @throws MK_Db_Exception
	 * @return Array
	 *  Dodatkowo:
	 *   $res[start] - początek pobieranych wierszy
	 *   $res[limit] - ilość wierszy na stronę
	 *   $res[totalCount] - maksymalna ilość wierszy
	 *   $res[results] - wynik zapytania
	 */
	protected function SelectLimit($sql, array $params = array(), $primaryName = null, $primaryVal = 0, $start = false, $limit = false) {
		$limit = ($limit === false) ? MK_Registry::get('limit') : $limit;
		$start = ($start === false) ? MK_Registry::get('start') : $start;
		$primaryVal = (int) $primaryVal;

		$timeStart = MK_DEBUG_FIREPHP ? microtime(true) : 0;
		$resCount = $this->_getCount($sql, $params);
		//jeżeli interesuje nas, na której stronie znajduje się rekord
		if($primaryName !== null && $primaryVal > 0) {
			$preparedSqlToGetRowNumber = self::_replaceSelectColumnsFromQuery($sql, $primaryName);
			$countExclamation = substr_count($preparedSqlToGetRowNumber, '?');
			$countParams = array_slice($params, -$countExclamation);

			$preparedSqlToGetRowNumber = "SELECT row_number
				 FROM ( {$preparedSqlToGetRowNumber} ) oldtable
				 	CROSS JOIN (
				 		SELECT ARRAY( {$preparedSqlToGetRowNumber} ) AS id
				 	) AS oldids
			        CROSS JOIN generate_series(1, {$resCount}) AS row_number
				 WHERE oldids.id[row_number] = oldtable.key_column AND oldtable.key_column = ?
				 LIMIT 1";

			$rowNumber = (int) $this->GetOne($preparedSqlToGetRowNumber, array_merge($countParams, $countParams, array($primaryVal)));

			$start = (ceil($rowNumber / $limit) - 1) * $limit;
		}

		$resArray = $this->_selectLimit($sql, $limit, $start, $params);

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		if(MK_DEBUG_FIREPHP) {
			$this->fireBugSqlDump("DbSelectLimit", $sql, $params, microtime(true) - $timeStart);
		}

		// Jeżeli jest włączone debugowanie, to SQL-e zapisywane są do pliku debug.log
		$this->_debugToFile($sql, $params);

		return array(
			'start' => $start,
			'limit' => $limit,
			'totalCount' => $resCount,
			'results' => $resArray
		);
	}

	/**
	 * Pomocnicza funkcja do SelectLimit
	 *
	 * @param string        $sql
	 * @param integer       $nrows (default: -1)
	 * @param integer       $offset (default: -1)
	 * @param bool|array    $inputarr (default: false)
	 *
	 * @return array
	 */
	private function _selectLimit($sql, $nrows = -1, $offset = -1, $inputarr = false) {
		$offsetStr = ($offset >= 0) ? " OFFSET " . ((integer) $offset) : '';
		$limitStr = ($nrows >= 0) ? " LIMIT " . ((integer) $nrows) : '';
		return $this->GetRows($sql . "{$limitStr}{$offsetStr}", $inputarr);
	}

	/**
	 * Zlicza wiersze na podstawie podanego zapytania i parametrów
	 *
	 * @param string        $sql
	 * @param array         $params
	 *
	 * @return integer
	 */
	private function _getCount($sql, array $params) {
		$qryRecs = 0;
		$rewritesql = $this->_stripOrderBy($sql);
		$rewritesql = "SELECT COUNT(*) FROM ({$rewritesql}) _MK_ALIAS_";

		if(isset($rewritesql) && $rewritesql != $sql) {
			if(preg_match('/\sLIMIT\s+[0-9]+/i', $sql, $limitarr)) {
				$rewritesql .= $limitarr[0];
			}

			$qryRecs = $this->GetOne($rewritesql, $params);

			if($qryRecs !== false) {
				return $qryRecs;
			}
		}

		// strip off unneeded ORDER BY if no UNION
		if(preg_match('/\s*UNION\s*/is', $sql)) {
			$rewritesql = $sql;
		} else {
			$rewritesql = $this->_stripOrderBy($sql);
		}

		if(preg_match('/\sLIMIT\s+[0-9]+/i', $sql, $limitarr)) {
			$rewritesql .= $limitarr[0];
		}

		$resValue = $this->GetOne($rewritesql, $params);
		if(!$resValue) {
			$resValue = $this->GetOne($sql, $params);
		}

		return $resValue;
	}

	/**
	 * Wywala Order'a z zapytania.
	 * Skopiowane z Ado
	 *
	 * @param string $sql
	 *
	 * @return string
	 */
	private function _stripOrderBy($sql) {
		preg_match('/(\sORDER\s+BY\s[^)]*)/is', $sql, $arr);
		if($arr) {
			if(strpos($arr[0], '(') !== false) {
				$at = strpos($sql, $arr[0]);
				$cntin = 0;
				for($i = $at, $max = strlen($sql); $i < $max; $i++) {
					$ch = $sql[$i];
					if($ch == '(') {
						$cntin += 1;
					} elseif($ch == ')') {
						$cntin -= 1;
						if($cntin < 0) {
							break;
						}
					}
				}
				$sql = substr($sql, 0, $at) . substr($sql, $i);
			} else {
				$sql = str_replace($arr[0], '', $sql);
			}
		}
		return $sql;
	}

	/**
	 *
	 * Tworzy łańcuch dla zapytania typu INSERT
	 *
	 * @param Array      $data - tablica wartości które mają być włożone do wiersza, klucze muszą mieć takie same nazwy jak pola w tabeli
	 * @param String	 $table - nazwa tabeli
	 *
	 * @return String
	 */
	protected function createInsert(array $data, $table) {
		return 'INSERT INTO ' . $table . '(' . implode(', ', array_keys($data)) . ')'
			. ' VALUES(' . self::arrayToQueryIn($data) . ')';
	}

	/**
	 * Tworzy łańcuch dla zapytania typu UPDATE
	 *
	 * @param Array      $data - tablica wartości które mają być włożone do wiersza, klucze muszą mieć takie same nazwy jak pola w tabeli
	 * @param String	 $table - nazwa tabeli
	 * @param Array      $where - tablica z wartościamy na podstawie których maja być aktualizowane rekordy, klucze muszą mieć takie same nazwy jak pola w tabeli
	 *
	 * @return String
	 */
	protected function createUpdate(array $data, $table, array $where) {
		$sql = 'UPDATE ' . $table . ' SET ';

		foreach($data as $key => $value) {
			$sql .= ' ' . $key . ' = ?,';
		}

		$sql = substr($sql, 0, -1);

		if(is_array($where) && count($where) > 0) {
			$sql .= ' WHERE ';

			foreach($where as $key => $val) {
				$sql .= ' ' . $key . ' = ? AND';
			}

			$sql = substr($sql, 0, -3);
		}

		return $sql;
	}

	/**
	 * Metoda przygotowująca warunki wyszukiwania
	 *
	 * @param mixed   $fields
	 * @param string  $query
	 * @param string  $logicalExpression
	 * @param array   &$whereSql
	 * @param array   &$whereValue
	 * @param boolean $fullText (default: false)
	 */
	protected function prepareQueryWhere($fields, $query, $logicalExpression, &$whereSql, &$whereValue, $fullText = false) {
		if(!empty($fields) && $query != '') {
			$whereSql_tmp = array();
			$fields = (json_decode($fields) == NULL) ? $fields : json_decode($fields);
			$query = ($fullText) ? '%' . $query . '%' : $query;

			//jesli pole fields nie jest tablica to tworzymy z niego tablice 1 elementowa
			$fields = (!is_array($fields)) ? array($fields) : $fields;
			foreach($fields as $v) {
				$whereSql_tmp[] = 'UPPER(CAST(' . $v . ' AS text)) ' . ((strstr($query, '%') !== false) ? 'LIKE' : '=') . ' ?';
				$whereValue[] = mb_convert_case($query, MB_CASE_UPPER, 'UTF-8');
			}
			$whereSql[] = '(' . implode(' ' . $logicalExpression . ' ', $whereSql_tmp) . ')';
		}
	}

	/**
	 * Przekształca tablicę do postaci '?,?,?,?...',
	 * przydatną do zapytań używających warunku IN
	 *
	 * W przypadku pustej tablicy zwraca wartość '-1',
	 * żeby zapytanie SQL się nie wysypało.
	 *
	 * @param Array $data
	 *
	 * @return String
	 */
	public function arrayToQueryIn(array $data) {
		$countData = count($data);
		return ($countData > 0) ? implode(',', array_fill(0, $countData, '?')) : '-1';
	}

	/**
	 * Metoda przygotowująca sortowanie
	 *
	 * @param Mixed		  $sort
	 * @param Mixed		  $dir
	 *
	 * @return String
	 *
	 * @TODO Metoda do przepisania - zastanowić się jak przekazywać parametry
	 */
	function prepareQueryOrder($sort = false, $dir = false) {
		// Zamiana string na array
		$sortArray = is_array($sort) ? $sort : array($sort);
		$dirArray = is_array($dir) ? $dir : array($dir);

		// Sprawdzenie pierwszego elementu tablicy
		// Pobranie z rejestru, jeśli jest pusty
		if($sortArray[0] === false) {
			$this->_prepareSortParam();
			$sortArray[0] = MK_Registry::get('sort');
			if(empty($sortArray[0])) {
				return '';
			}
		}

		// Sprawdzenie pierwszego elementu tablicy
		// Pobranie z rejestru, jeśli jest pusty
		if($dirArray[0] === false) {
			$dirArray[0] = MK_Registry::get('dir');
		}

		// Przygotowanie zapytania SQL
		$orderBy = '';
		foreach($sortArray as $i => $sort) {
			if(empty($sort)) {
				continue;
			}
			if($orderBy != '') {
				$orderBy .= ', ';
			}
			$orderBy .= $sort . (isset($dirArray[$i]) ? ' ' . $dirArray[$i] : '');
		}

		// Sprawdzenie czy została dodana jakakolwiek kolumna do sortowania
		return (strlen($orderBy) > 0) ? ' ORDER BY ' . $orderBy : '';
	}

	/**
	 * Przygotowuje warunki dla zapytania sql na podstawie podanej tablicy
	 *
	 * @param Array	 $where
	 *
	 * @return String
	 */
	public function prepareSqlConditions($where) {
		return (is_array($where) && count($where) > 0) ? ' WHERE (' . implode(' AND ', $where) . ') ' : '';
	}

	/**
	 * Podmieniam kolumny z selecta w podanym zapytaniu na podany indeks
	 *
	 * @param String     $sql
	 * @param String     $param
	 *
	 * @throws MK_Db_Exception
	 * @return String
	 */
    private function _replaceSelectColumnsFromQuery($sql, $param)
    {
        $from = $this->_truncateSqlAfterFrom( $sql );

        if( empty($from) ) {

            throw new MK_Db_Exception('Preparowanie zapytania SQL zakończone niepowodzeniem.');
        }
        return "SELECT {$param} AS key_column FROM {$from}";
    }

    /**
     * Wycina z zapytania SQL jedynie to co się znajduje po głównym 'FROM'
     *
     * Przykład:
     * "SELECT trim(name), (SELECT '(id:'||id||')' FROM table1) FROM table2 WHERE field=true AND (SELECT COUNT(id) FROM test) > 5"
     *
     * Dla powyższego SQL zostanie zwrócona część:
     * " table2 WHERE field=true AND (SELECT COUNT(id) FROM test) > 5"
     *
     * @param String $sql Zapytanie SQL
     * @return String Wycięta część z zapytania
     */
    private function _truncateSqlAfterFrom( $sql )
    {
        $upperSql = strtoupper( $sql );
        $sqlLength = strlen( $upperSql );
        $dQuotes = $sQuotes = false;
        $deep = 0;
        for( $idx=0; $idx<$sqlLength; $idx++ ){

            if( $upperSql[$idx] == "'" ) { $sQuotes = !$sQuotes; }
            if( $upperSql[$idx] == '"' ) { $dQuotes = !$dQuotes; }

            if( !$sQuotes && !$dQuotes ) {
                if( $upperSql[$idx] == '(' ){ $deep++; }
                if( $upperSql[$idx] == ')' ){ $deep--; }
            }
            if( $deep == 0 && strpos( substr( $upperSql, $idx, 6), ' FROM ' ) === 0 ) {
                return substr( $sql, $idx+6);
            }
        }
        return null;
    }

	/**
	 *     Metoda dzieli, długą listę (powyżej 1000 elementów) występującą w zapytaniu SQL na mniejsze
	 *  czesci, po 1000 elementow i zwraca spreparowanego SQLa
	 *
	 * @param $columnName string - Nazwa kolumny z tabeli
	 * @param $arrayValues array - Tablica wartosci z kolumny
	 * @param $delimiter string  - Delimiter, którym oddzielone beda dane
	 *
	 * @param $quote
	 *
	 * @return String
	 */
	static function splitSqlList($columnName, array $arrayValues, $delimiter, $quote) {
		$maxListSize = 1000;
		$splittedSql = '(';
		$count = count($arrayValues);
		$numParts = floor($count / $maxListSize);
		for($i = 0; $i <= $numParts; $i++) {
			$part = array_slice($arrayValues, $i * $maxListSize, $maxListSize);
			$splittedSql .= $columnName . ' IN (' . $quote . implode($quote . $delimiter . $quote, $part) . $quote . ')';
			if($i < $numParts) {
				$splittedSql .= ' OR ';
			}
		}
		$splittedSql .= ')';
		return $splittedSql;
	}

	/**
	 * Wywoływana gdy nie znajdzie metody w klasach dziedziczących.
	 * Wykorzystujemy ją do budowania dynamicznych zapytań do bazy.
	 * Przy kładowo jeżeli wywołamy metodę findRowByIs w modelu ResolutionDb to budowane jest zapytanie "SELECT * FROM swpirb_resolution WHERE id = ?".
	 * Trzeba pamiętać aby klasa, z której wywoływana jest metoda musi zawierać atrybut "_tableName" ustawiony na protected
	 *
	 * Dynamiczne metody:
	 *     - findRowBy - zwraca jeden wiersz np findRowByIdAndState
	 *     - findRowsBy - zwraca wiersze spełniające warunki np findRowsByIdAndState
	 *
	 * Kolejne parametry rozdzialamy słowem And
	 *
	 * @param string $name - nazwa wywoływanej metody
	 * @param array  $arguments - parametry przekazywane do wywoływanej metody
	 *
	 * @throws Exception
	 * @return array
	 */
	public function __call($name, $arguments) {
		if(!preg_match('/^(find[a-zA-Z]+)By/', $name, $match)) {
			throw new Exception('Nie odnaleziono funkcji ' . $name);
		}
		$methodName = '_' . $match[1];
		if(method_exists($this, $methodName)) {
			return $this->{$methodName}($name, $arguments);
		}

		throw new Exception('Nie odnaleziono funkcji ' . $name);
	}

	/**
	 * Wywoływana gdy wywołamy metodę zaczynającą się od "findRowsBy".
	 * Zwraca rekordy spełniające podane warunki.
	 * Nazwy parametrów znajdują się nazwie f-cji rozdzielone słowem AND.
	 *
	 * Przykładowa nazwa funkcji wywołana z klasy ResolutionDb "findRowsByIdAndState($id, $state)",
	 * zwróci wyniki na podstawie zapytania: SELECT * FROM swpirb_resolution WHERE id = ? AND state = ?
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return array
	 */
	private function _findRows($name, array $arguments) {
		$sql = $this->_prepareSql(
			str_replace('findRowsBy', '', $name), $arguments
		);
		return $this->GetRows($sql, $arguments);
	}

	/**
	 * Wywoływana gdy wywołamy metodę zaczynającą się od "findRowBy".
	 * Zwraca jeden rekord spełniający podane warunki.
	 * Nazwy parametrów znajdują się nazwie f-cji rozdzielone słowem AND.
	 *
	 * Przykładowa nazwa funkcji wywołana z klasy ResolutionDb "findRowByIdAndState($id, $state)",
	 * zwróci wyniki na podstawie zapytania: SELECT * FROM swpirb_resolution WHERE id = ? AND state = ? LIMIT 1
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return array
	 */
	private function _findRow($name, array $arguments) {
		$sql = $this->_prepareSql(
			str_replace('findRowBy', '', $name), $arguments
		) . ' LIMIT 1';
		return $this->GetRow($sql, $arguments);
	}

	/**
	 * Zwraca tablicę zawierająca tylko te elementy, które pokrywają się z kolumnami tabeli
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	protected function _getDataForColumns(array $args) {
		$data = array_intersect_key($args, array_flip($this->_tableColumns));

		//puste string'i zamieniamy na null'e
		foreach($data as &$colValue) {
			if(is_null($colValue) || (empty($colValue) && strlen($colValue) === 0)) {
				$colValue = null;
			}
		}
		unset($colValue);

		return $data;
	}

	/**
	 *
	 * Zebranie nazw pól po których ma się odbyć wyszukiwanie
	 *
	 * @param Array     $ia_queryParams
	 *
	 * @return Array
	 */
	protected function _prepareFieldsFromQueryParams(array $ia_queryParams) {
		$oa_fields = array();

		if(!empty($ia_queryParams['fields']) && !empty($ia_queryParams['query']) && mb_strlen($ia_queryParams['query']) > 0) {
			$a_fieldsTmp = json_decode($ia_queryParams['fields']);

			if(is_array($a_fieldsTmp) && count($a_fieldsTmp) > 0) {
				foreach($a_fieldsTmp as $s_field) {
					if(in_array($s_field, $this->fieldsCanBeSearched)) {
						$oa_fields[] = $s_field;
					}
				}
			}
		}

		return $oa_fields;
	}

	/**
	 * Jeżeli w rejestrze nie ma ustawionej kolumny sortowania,
	 * to ustawiamy ją na podstawie stałej SORT_COLUMN pochodzącej z klasy dziecka
	 */
	private function _prepareSortParam() {
		if(!MK_Registry::isRegistered('sort') || MK_Registry::get('sort') === null) {
			$const = get_class($this) . '::SORT_COLUMN';
			if(defined($const)) {
				MK_Registry::set('sort', constant($const));
			}
		}
	}

	/**
	 * Zamienia wielką literę na małą dodając przed nią _.
	 * Np: A -> _a
	 *
	 * @param array $match
	 *
	 * @return string
	 */
	private function _replaceUppercase($match) {
		return '_' . strtolower($match[1]);
	}

	/**
	 * Przygotowuje SQL'a na podstawie podstawie podanych nazw parametrów w f-cji.
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function _prepareSql($name, array $arguments) {
		$paramsNames = explode('And', $name);
		$sql = "SELECT * FROM {$this->_tableName} WHERE ";
		$sqlAnd = '';

		$i = 0;
		foreach($paramsNames as $paramName) {
			$paramName{0} = strtolower($paramName{0});
			$paramName = preg_replace_callback('/([A-Z]{1})/', array($this, '_replaceUppercase'), $paramName);
			$sql .= $sqlAnd . strtolower($paramName) . ' = ? ';
			$sqlAnd = ' AND ';

			if(!array_key_exists($i, $arguments)) {
				throw new InvalidArgumentException('Podano nieprawidłową liczbę parametrów');
			}
			$i++;
		}

		return $sql;
	}

	/**
	 * Grupowanie identyfikatorów:
	 *  - insert - nie występuje w tablicy $oldIds, ale występuje w $newIds
	 *  - update - występuje w tablicy $oldIds oraz $newIds
	 *  - delete - występuje w tablicy $oldIds, ale nie występuje w $newIds
	 *
	 * @param Array $newIds Identyfikatory do sprawdzenia
	 * @param Array $oldIds Identyfikatory pobrane z bazy danych
	 *
	 * @return Array
	 */
	public function groupIdsBySql(array $newIds, array $oldIds) {
		$ret = array(
			'insert' => array(),
			'update' => array(),
			'delete' => array()
		);

		foreach($newIds as $nValue) {
			$notInArray = true;
			foreach($oldIds as $oKey => $oValue) {
				if($nValue == $oValue) {
					$ret['update'][] = $nValue;
					unset($oldIds[$oKey]);
					$notInArray = false;
					break;
				}
			}
			if($notInArray) {
				$ret['insert'][] = $nValue;
			}
		}
		$ret['delete'] = $oldIds;

		return $ret;
	}

	/**
	 * Zrzucenie SQL-a i jego parametrów do okna firebuga
	 *
	 * @param String $dumpName - nazwa wyświetlanej operacji
	 * @param String $sql - zapytanie SQL
	 * @param Array  $params - parametry zapytania (dane)
	 * @param int    $execTime
	 */
	public function fireBugSqlDump($dumpName, $sql = "", array $params = array(), $execTime = 0) {
		// Odczytanie klasy i metody, w której wyświetlony zostanie komunikat
		$className = get_class($this);
		$methodName = '';
		$filePath = '';
		$lineNumber = '';
		$traceList = debug_backtrace();
		$traceArr = array();
		foreach($traceList as $trace) {
			if(!isset($trace['class']) || in_array($trace['class'], $this->_sqlIgnoreClass)) {
				continue;
			}
			if(count($traceArr) == 0) {
				$className = $trace['class'];
				$methodName = isset($trace['function']) ? $trace['function'] : '';
				$filePath = isset($trace['file']) ? str_replace(APP_PATH, '', $trace['file']) : '';
				$lineNumber = isset($trace['line']) ? $trace['line'] : -1;
			}
			if(isset($trace['object'])) {
				unset($trace['object']);
			}
			$traceArr[] = $trace;
		}
		// Czas generowania SQL-a
		$sqlTime = microtime(true);
		$sqlTimeDiff = round($sqlTime - (isset($_SESSION['sql_last_time']) ? $_SESSION['sql_last_time'] : 0), 4);
		$execTime = round($execTime, 4);
		// Wyświetlenie komunikatu debug-a
		if(empty($sql)) {
			// Debugowanie dodatkowych informacji (utworzenie połączenia, otwarcie/zamknięcie transakcji)
			if(DB_DEBUG) {
				FB::info((object) array(
					'OPERATION' => $dumpName,
					'BACKTRACE' => $traceArr
				), "INFO ({$sqlTime} [+{$sqlTimeDiff}]) :: {$filePath}:{$lineNumber} :: {$className}->{$methodName}");
			}
		} else {
			FB::warn((object) array(
				'OPERATION' => $dumpName,
				'SQL+PARAMS' => $this->_sqlFormat($this->_prepareFullQuery($sql, $params)),
				'SQL' => "\n" . $sql . "\n",
				'PARAMS' => $params,
				'BACKTRACE' => $traceArr
			), "SQL ({$sqlTime} [+{$sqlTimeDiff}ms] {{$execTime}ms}) :: {$filePath}:{$lineNumber} :: {$className}->{$methodName}");
		}
	}

	/**
	 * Przełamuje ciagi sql
	 *
	 * @param String $query
	 *
	 * @return String
	 */
	private function _sqlFormat($query) {
		$keywords = array("select ", " from ", " left join ", " right join ", " inner join ", " where ", " order by ", " group by ", "insert into ", "update ");
		foreach($keywords as $keyword) {
			if(preg_match("#($keyword*)#i", $query, $matches)) {
				$query = str_replace($matches[1], "\n" . mb_convert_case($matches[1], MB_CASE_UPPER, 'UTF-8') . "  \t", $query);
			}
		}
		return $query . "\n";
	}

	/**
	 * Umieszczenie danych z tablicy $params w zapytaniu SQL.
	 * Pozwala podglądnąć całe zapytanie SQL i skopiować do dalszej analizy.
	 *
	 * @param String $sql - zapytanie SQL
	 * @param Array  $params - parametry zapytania
	 *
	 * @return String
	 */
	private function _prepareFullQuery($sql, array $params) {
		if(count($params) == 0) {
			return $sql;
		}

		foreach($params as $param) {
			if(is_null($param)) {
				$param = 'NULL';
			} elseif(!is_numeric($param)) {
				$param = "'" . str_replace("'", "''", $param) . "'";
			}
			$sql = preg_replace('#\?#', $param, $sql, 1);
		}

		return $sql;
	}

	/**
	 * Wstawia parametry do rejestru
	 */
	public static function setRegistryParams() {
		$limit = MK_Validator::positiveIntegerArgument('limit', $_POST) ? (int) $_POST['limit'] : DB_DEFAULT_LIMIT;
		$start = MK_Validator::positiveIntegerArgument('start', $_POST) ? (int) $_POST['start'] : DB_DEFAULT_START;
		$sortDirection = MK_Validator::stringArgument('dir', $_POST) ? pg_escape_string($_POST['dir']) : DB_DEFAULT_SORT_DIRECTION;
		$sortColumn = MK_Validator::stringArgument('sort', $_POST) ? pg_escape_string($_POST['sort']) : DB_DEFAULT_SORT_COLUMN;

		MK_Registry::set('limit', $limit);
		MK_Registry::set('start', $start);
		MK_Registry::set('dir', $sortDirection);
		MK_Registry::set('sort', $sortColumn);
	}

}