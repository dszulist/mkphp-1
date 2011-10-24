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
	 * Informuje ile transakcji jest otwartych
	 *
	 * @var integer
	 */
	private $_transCounter = 0;

	/**
	 * Flaga oznaczająca czy transakcja ma być zamknięta(commit) czy cofnięta (rollback)
	 *
	 * @var type
	 */
	private $_transOk = false;

	protected $db = null;

	public function __construct(){
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = microtime(true);

		// Połączenie z bazą danych (singleton)
		$this->db = MK_Db_PDO_Singleton::getInstance();

		// Odczytanie i obliczenie czasu wykonania zapytania SQL
		$execTime = microtime(true) - $timeStart;
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		$this->fireBugSqlDump("MK_Db_PDO_Singleton::getInstance()", '', array(), $execTime);
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
	 * Wykonanie przygotowanego zapytania SQL. Nie są pobierane żadne dane.
	 * Zwracany ilość zmienionych rekordów.
	 *
	 * @param String	 $sql - zapytanie sql'owe
	 * @param Array	 $params - parametry zapytania
	 *
	 * @throws MK_Db_Exception
	 * @return integer
	 */
	public function Execute($sql, array $params = array()) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = microtime(true);

		// Jeżeli zostały podany parametry, to wykonujemy zapytanie przy pomocy prepare/execute
		// W przeciwnym wypadku uruchiamy zapytanie poprzez exec(), które umożliwia wykonanie wielu zapytań SQL
		if( count($params) > 0 ) {
			$pdoObj = $this->db->prepare($sql);
			if ($pdoObj->execute($params) === false) {
				throw new MK_Db_Exception(self::MESSAGE_ERROR_RESULTS);
			}
			$affectedRows = $pdoObj->rowCount();
		}
		else {
			$results = $this->db->exec($sql);
			if ($results === false) {
				throw new MK_Db_Exception(self::MESSAGE_ERROR_RESULTS);
			}
			$affectedRows = $results;
		}

		// Odczytanie i obliczenie czasu wykonania zapytania SQL
		$execTime = microtime(true) - $timeStart;
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		$this->fireBugSqlDump("DbExecute", $sql, $params, $execTime);

		// Ilość zmodyfikowanych wierszy
		return $affectedRows;
	}


	/**
	 * Odczytanie tylko pojedynczej wartości (pierwszej kolumny w zapytaniu).
	 * Brany jest pod uwagę tylko jeden rekord, a zwracana wartość typu string.
	 *
	 * @param String $sql - zapytanie sql'owe
	 * @param Array	 $params - parametry zapytania
	 *
	 * @throws MK_Db_Exception
	 * @return string
	 */
	public function GetOne($sql, array $params = array()) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = microtime(true);

		// Wykonanie zapytania SQL
		if ($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(self::MESSAGE_ERROR_RESULTS);
		}

		// Odczytanie odpowiedzi (string)
		$resString = $pdoObj->fetchColumn();

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pusty string
		// W aplikacji będziemy się spodziewać pustego stringa, a nie 'false'
		if($resString === false) {
			$resString = '';
		}

		// Odczytanie i obliczenie czasu wykonania zapytania SQL
		$execTime = microtime(true) - $timeStart;
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		$this->fireBugSqlDump("DbGetOne", $sql, $params, $execTime);

		return $resString;
	}


	/**
	 * Odczytanie tej samej kolumny z wszystkich wierszy zapytania SQL.
	 * Odczytane dane są w postaci tablicy jednowymiarowej, indeksowanej od zera.
	 *
	 * @param String	 $sql
	 * @param Array	 $params
	 * @param boolean $trim
	 *
	 * @throws MK_Db_Exception
	 * @return array
	 */
	public function GetCol($sql, $params = array(), $trim = false) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = microtime(true);

		// Wykonanie zapytania SQL
		if ($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(self::MESSAGE_ERROR_RESULTS);
		}
		// Odczytanie odpowiedzi (array)
		$resArray = $pdoObj->fetchAll(PDO::FETCH_COLUMN);

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pustą tablicę
		// W aplikacji będziemy się spodziewać pustej tablicy, a nie 'false'
		// WARNING: count(false) == 1 [co sugerowałoby, że jest to tablica!]
		if($resArray === false) {
			$resArray = array();
		}

		// Odczytanie i obliczenie czasu wykonania zapytania SQL
		$execTime = microtime(true) - $timeStart;
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		$this->fireBugSqlDump("DbGetCol", $sql, $params, $execTime);

		return $resArray;
	}


	/**
	 * Odczytanie tylko jednego wiersza z podanego zapytania SQL.
	 *
	 * @param String	 $sql - zapytanie sql'owe
	 * @param Array	 $params - parametry zapytania
	 *
	 * @throws MK_Db_Exception
	 * @return array
	 */
	public function GetRow($sql, array $params = array()) {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = microtime(true);

		// Wykonanie zapytania SQL
		if ($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(self::MESSAGE_ERROR_RESULTS);
		}
		// Odczytanie odpowiedzi (array)
		$resArray = $pdoObj->fetch();

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pustą tablicę
		// W aplikacji będziemy się spodziewać pustej tablicy, a nie 'false'
		// WARNING: count(false) == 1 [co sugerowałoby, że jest to tablica!]
		if($resArray === false) {
			$resArray = array();
		}

		// Odczytanie i obliczenie czasu wykonania zapytania SQL
		$execTime = microtime(true) - $timeStart;
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		$this->fireBugSqlDump("DbGetRow", $sql, $params, $execTime);

		return $resArray;
	}


	/**
	 * Odczytanie wszystkich wierszy z podanego zapytania SQL.
	 * Podanie parametru $columnAsKey pozwoli odczytać dane w postaci tablicy,
	 * której kluczem (indeksem) będzie wartość podanej kolumny, np. sid
	 *
	 * @param String	 $sql - zapytanie sql'owe
	 * @param Array	 $params - parametry zapytania
	 * @param String $columnAsKey (opcjonalnie) - nazwa kolumny jako klucz
	 *
	 * @throws MK_Db_Exception
	 * @return array
	 */
	public function GetRows($sql, array $params, $columnAsKey = '') {
		// Bez array_values wywala błąd - nie ma być kluczy w tablicy!
		$params = array_values($params);

		// Przygotowanie zapytania SQL
		$pdoObj = $this->db->prepare($sql);
		// Ustawienie tablicy asocjacyjnej w odpowiedzi
		$pdoObj->setFetchMode(PDO::FETCH_ASSOC);
		// Uruchomienie licznika uruchamiania zapytania SQL
		$timeStart = microtime(true);

		// Wykonanie zapytania SQL
		if ($pdoObj->execute($params) === false) {
			throw new MK_Db_Exception(self::MESSAGE_ERROR_RESULTS);
		}

		// Odczytanie odpowiedzi (array)
		$resArray = array();
		if (strlen($columnAsKey) > 0) {
			$sqlDumpName = "DbGetRows['{$columnAsKey}']";
			while ($row = $pdoObj->fetch(PDO::FETCH_ASSOC)) {
				$resArray[$row[$columnAsKey]] = $row;
			}
		}
		else {
			$sqlDumpName = "DbGetRows";
			$resArray = $pdoObj->fetchAll();
		}

		// Jeżeli odpowiedź będzie false, to powinien zwrócić pustą tablicę
		// W aplikacji będziemy się spodziewać pustej tablicy, a nie 'false'
		// WARNING: count(false) == 1 [co sugerowałoby, że jest to tablica!]
		if($resArray === false) {
			$resArray = array();
		}

		// Odczytanie i obliczenie czasu wykonania zapytania SQL
		$execTime = microtime(true) - $timeStart;
		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		$this->fireBugSqlDump($sqlDumpName, $sql, $params, $execTime);

		return $resArray;
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
		if ($pdoObj->execute() === false) {
			throw new MK_Db_Exception(self::MESSAGE_ERROR_RESULTS);
		}

		// Odczytanie odpowiedzi (array)
		$resValue = $pdoObj->fetchColumn();

		// Jeżeli odpowiedź będzie false, to operacja powinna być wstrzymana,
		// ponieważ wartość sekwencji jest nieprawidłowa przez co dane zostałyby
		// zapisane w bazie danych w nieodpowiedni sposób.
		if($resValue === false) {
			throw new MK_Db_Exception(self::MESSAGE_ERROR_SEQUENCE);
		}

		// Zwrócenie szczegółowego komunikatu w konsoli FireBug-a
		$this->fireBugSqlDump("setNextVal", $sql);

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
	 */
	public function transStart() {
		$this->fireBugSqlDump("transStart");
		if ($this->_transCounter > 0) {
			$this->_transCounter++;
			return true;
		}
		$this->_transOk = true;
		/**
		 *   true  - transakcja została utworzona
	 	 *   false - baza danych nie obsługuje transakcji
		 */
		$transOk = $this->db->beginTransaction();
		if(!$transOk){
			throw new MK_Db_Exception('Baza danych nie obsługuje transakcji');
		}

		$this->_transCounter = 1;
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
	 * @return 
	 */
	public function transComplete( $commit = true ) {
		$this->fireBugSqlDump("transComplete");
		if ($this->_transCounter > 1) {
			$this->_transCounter--;
			return true;
		}
		if( $this->_transCounter == 1 ) {
			$tableLogsDb = new TableLogsDb();
			$tableLogsDb->closeConnectionForTableLog();
		}
		$this->_transCounter = 0;
		/**
		 *   true  - COMMIT
	 	 *   false - ROLLBACK
		 */
		if ($commit && $this->_transOk) {
			if(!$this->db->commit()) {
				$this->_transOk = false;
				throw new MK_Db_Exception('transakcja nie powiodła się');
			}
		}
		else {
			$this->_transOk = false;
			$this->db->rollBack();
		}

		return $this->_transOk;
	}


	/**
	 * Zablokowanie COMMIT dla danej transakcji.
	 * Ustawienie $this->_transOK = false
	 * DB->CompleteTrans() cofnie wszystkie transakcje.
	 */
	public function transFail() {
		$this->_transOk = false;
	}


	/**
	 * Włączenie (true) lub wyłączenie (false) debugowania SQL.
	 * @param boolean $debug
	 */
	public function debug( $debug=true ) {
		$this->db->debug = $debug;
	}


	/**
	 * Metodę SelectLimit. Jeżeli podamy id klucza i nazwę, to pobiera numer strony na której znajduje się rekord.
	 *
	 * @param String	 $sql - zapytanie sql'owe
	 * @param Array	 $params - parametry zapytania
	 * @param String	 $paramName - nazwa klucza
	 * @param String	 $paramVal - wartość klucza
	 * @param Integer	$start - start
	 * @param Integer	$limit - limit
	 *
	 * @throws MK_Db_Exception
	 * @return Array
	 *  Dodatkowo:
	 *   $res[start] - początek pobieranych wierszy
	 *   $res[limit] - ilość wierszy na stronę
	 *   $res[totalCount] - maksymalna ilość wierszy
	 *   $res[results] - wynik zapytania
	 */
	public function SelectLimit($sql, array $params, $primaryName = null, $primaryVal = 0, $start = false, $limit = false) {
		$limit = ($limit === false) ? MK_Registry::get('limit') : $limit;
		$start = ($start === false) ? MK_Registry::get('start') : $start;
		$primaryVal = (int) $primaryVal;

		$timeStart = microtime(true);
		$resCount = $this->_getCount($sql, $params);
		//jeżeli interesuje nas, na której stronie znajduje się rekord
		if ($primaryName !== null && $primaryVal > 0) {
			$preparedSqlToGetRowNumber = self::_replaceSelectColumnsFromQuery($sql, $primaryName);
			$countExclamation = substr_count($preparedSqlToGetRowNumber, '?');
			$countParams = array_slice($params, -$countExclamation);

			$preparedSqlToGetRowNumber = 'SELECT row_number'
				. ' FROM ( ' . $preparedSqlToGetRowNumber . ' ) as oldtable'
				. ' CROSS JOIN ('
						. ' SELECT ARRAY( ' . $preparedSqlToGetRowNumber . ' ) as id)  AS oldids'
					. ' CROSS JOIN generate_series(1, ' . $resCount . ') AS row_number'
				. ' WHERE oldids.id[row_number] =  oldtable.key_column AND oldtable.key_column = ?'
				. ' LIMIT 1';

			$rowNumber = (int) $this->GetOne($preparedSqlToGetRowNumber, array_merge($countParams, $countParams, array($primaryVal)));

			$start = ( ceil($rowNumber / $limit) - 1 ) * $limit;
		}

		$resArray = $this->_selectLimit($sql, $limit, $start, $params);

		$execTime = microtime(true) - $timeStart;
		$this->fireBugSqlDump("DbSelectLimit", $sql, $params, $execTime);

		return array(
			'start'			=> $start,
			'limit'			=> $limit,
			'totalCount'	=> $resCount,
			'results'		=> $resArray
		);
	}


	/**
	 * Pomocnicza funkcja do SelectLimit
	 *
	 * @param type $sql
	 * @param type $nrows
	 * @param type $offset
	 * @param type $inputarr
	 * @param type $secs2cache
	 *
	 * @return array
	 */
	private function _selectLimit($sql, $nrows=-1, $offset=-1, $inputarr=false, $secs2cache=0) {
		$offsetStr = ($offset >= 0) ? " OFFSET " . ((integer) $offset) : '';
		$limitStr = ($nrows >= 0) ? " LIMIT " . ((integer) $nrows) : '';
		return $this->GetRows($sql . "{$limitStr}{$offsetStr}", $inputarr);
	}


	/**
	 * Zlicza wiersze na podstawie podanego zapytania i parametrów
	 *
	 * @param type $sql
	 * @param type $params
	 *
	 * @return integer
	 */
	private function _getCount($sql, array $params) {
		$qryRecs = 0;
		$rewritesql = $this->_stripOrderBy($sql);
		$rewritesql = "SELECT COUNT(*) FROM ({$rewritesql}) _MK_ALIAS_";

		if (isset($rewritesql) && $rewritesql != $sql) {
			if (preg_match('/\sLIMIT\s+[0-9]+/i',$sql,$limitarr)) {
				$rewritesql .= $limitarr[0];
			}

			$qryRecs = $this->GetOne($rewritesql, $params);

			if ($qryRecs !== false) {
				return $qryRecs;
			}
		}

		// strip off unneeded ORDER BY if no UNION
		if (preg_match('/\s*UNION\s*/is', $sql)) {
			$rewritesql = $sql;
		}
		else {
			$rewritesql = $this->_stripOrderBy($sql);
		}

		if (preg_match('/\sLIMIT\s+[0-9]+/i',$sql,$limitarr)) {
			$rewritesql .= $limitarr[0];
		}

		$resValue = $this->GetOne($rewritesql,$params);
		if (!$resValue) {
			$resValue = $this->GetOne($sql,$params);
		}

		return $resValue;
	}


	/**
	 * Wywala Order'a z zapytania.
	 * Skopiowane z Ado
	 *
	 * @param string $sql
	 * @return string
	 */
	private function _stripOrderBy($sql)
	{
		$rez = preg_match('/(\sORDER\s+BY\s[^)]*)/is',$sql,$arr);
		if ($arr) {
			if (strpos($arr[0],'(') !== false) {
				$at = strpos($sql,$arr[0]);
				$cntin = 0;
				for ($i=$at, $max=strlen($sql); $i < $max; $i++) {
					$ch = $sql[$i];
					if ($ch == '(') {
						$cntin += 1;
					} elseif($ch == ')') {
						$cntin -= 1;
						if ($cntin < 0) {
							break;
						}
					}
				}
				$sql = substr($sql,0,$at).substr($sql,$i);
			}
			else {
				$sql = str_replace($arr[0], '', $sql);
			}
		}
		return $sql;
	 }


	/**
	 *
	 * Tworzy łańcuch dla zapytania typu INSERT
	 *
	 * @param Array	 $data - tablica wartości które mają być włożone do wiersza, klucze muszą mieć takie same nazwy jak pola w tabeli
	 * @param String	 $table - nazwa tabeli
	 *
	 * @return String
	 */
	public function createInsert(array $data, $table) {
		return 'INSERT INTO ' . $table . '(' . implode(', ', array_keys($data)) . ')'
			. ' VALUES(' . self::arrayToQueryIn($data) . ')';
	}


	/**
	 * Tworzy łańcuch dla zapytania typu UPDATE
	 *
	 * @param Array	 $data - tablica wartości które mają być włożone do wiersza, klucze muszą mieć takie same nazwy jak pola w tabeli
	 * @param String	 $table - nazwa tabeli
	 * @param Array	 $where - tablica z wartościamy na podstawie których maja być aktualizowane rekordy, klucze muszą mieć takie same nazwy jak pola w tabeli
	 *
	 * @return String
	 */
	public function createUpdate(array $data, $table, array $where) {
		$sql = 'UPDATE ' . $table . ' SET ';

		foreach($data as $key => $value) {
			$sql .= ' ' . $key . ' = ?,';
		}

		$sql = substr($sql, 0, -1);

		if (is_array($where) && count($where) > 0) {
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
	 * @param Mixed	  fields
	 * @param String	 query
	 * @param String	 logicalExpression
	 *
	 * @return Array
	 */
	public function prepareQueryWhere($fields, $query, $logicalExpression, &$whereSql, &$whereValue, $fullText = false) {
		if (!empty($fields) && $query != '') {
			$whereSql_tmp = array();
			$fields = (json_decode($fields)==NULL) ? $fields : json_decode($fields);
			$query = ($fullText) ? '%'.$query.'%' : $query;

			//jesli pole fields nie jest tablica to tworzymy z niego tablice 1 elementowa
			$fields = (!is_array($fields)) ? array($fields) : $fields;
			foreach ($fields as $v) {
				$whereSql_tmp[] = 'UPPER(CAST('.$v.' AS text)) '.((strstr($query, '%') !== false)?'LIKE':'=').' ?';
				$whereValue[] = strtoupper($query);
			}
			$whereSql[] = '('.implode(' '.$logicalExpression.' ',$whereSql_tmp).')';
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
	 * @return String
	 */
	public function arrayToQueryIn( array $data ) {
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
		if( $sortArray[0] === false ) {
			$sortArray[0] = MK_Registry::get('sort');
			if( empty($sortArray[0]) ) {
				return '';
			}
		}

		// Sprawdzenie pierwszego elementu tablicy
		// Pobranie z rejestru, jeśli jest pusty
		if( $dirArray[0] === false ) {
			$dirArray[0] = MK_Registry::get('dir');
		}

		// Przygotowanie zapytania SQL
		$orderBy = '';
		foreach($sortArray as $i=>$sort) {
			if( empty($sort) ) {
				continue;
			}
			if($orderBy != '') {
				$orderBy .= ', ';
			}
			$orderBy .= $sort . ( isset($dirArray[$i]) ? ' '.$dirArray[$i] : '' );
		}

		// Sprawdzenie czy została dodana jakakolwiek kolumna do sortowania
		return (strlen($orderBy) > 0) ? ' ORDER BY '.$orderBy : '';
	}


	/**
	 * Przygotowuje warunki dla zapytania sql na podstawie podanej tablicy
	 *
	 * @param Array	 $where
	 *
	 * @return String
	 */
	public function prepareSqlConditions($where) {
		return (is_array($where) && count($where)>0) ? ' WHERE ('.implode(' AND ',$where).') ' : '';
	}


	/**
	 * Podmieniam kolumny z selecta w podanym zapytaniu na podany indeks
	 *
	 * @param String	 $sql
	 * @param String	 $param
	 * @return String
	 */
	private function _replaceSelectColumnsFromQuery($sql, $param) {
		// Zamiana nawiasów na tekst
		preg_match_all('#\(.*\)#im',$sql,$brackets);

		$tempSql = $sql;

		foreach($brackets[0] as $key => $val) {
			$tempSql = str_replace($val, 'brackets_'.$key, $tempSql);
		}

		// Odczytanie listy kolumn
		preg_match('#^SELECT\s[\w\W]+\sFROM#i', $tempSql, $columns);
		$tempSql = $columns[0];

		foreach($brackets[0] as $key => $val) {
			$tempSql = str_replace('brackets_'.$key, $val, $tempSql);
		}

		// Przywrócenie nawiasów z tekstu
		return str_replace($tempSql, 'SELECT ' . $param . ' AS key_column FROM', $sql);
	}


	/**
	 * 	Metoda dzieli, długą listę (powyżej 1000 elementów) występującą w zapytaniu SQL na mniejsze
	 *  czesci, po 1000 elementow i zwraca spreparowanego SQLa
	 *
	 *	@param $columnName string - Nazwa kolumny z tabeli
	 *	@param $arrayValues array - Tablica wartosci z kolumny
	 *	@param $delimiter string  - Delimiter, którym oddzielone beda dane
	 *
	 *	@return String
	 */
	static function splitSqlList($columnName, array $arrayValues, $delimiter, $quote) {
		$maxListSize = 1000;
		$splittedSql = '(';
		$count = count($arrayValues);
		$numParts = floor($count/$maxListSize);
		for ($i = 0 ; $i <= $numParts ; $i++) {
			$part = array_slice($arrayValues, $i * $maxListSize, $maxListSize);
			$splittedSql .= $columnName . ' IN ('.$quote.implode($quote.$delimiter.$quote, $part).$quote.')';
			if ($i < $numParts) {
				$splittedSql .= ' OR ';
			}
		}
		$splittedSql .= ')';
		return $splittedSql;
	}

}