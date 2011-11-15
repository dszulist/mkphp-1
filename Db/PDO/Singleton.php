<?php

/**
 * MK_Db_PDO_Singleton
 *
 * Klasa z Singletonem PDO
 *
 * @category	MK_Db
 * @package		MK_Db_PDO_Singleton
 *
 * @throws		MK_Db_Exception
 */
class MK_Db_PDO_Singleton {
	CONST MESSAGE_ERROR_RESULTS = 'Błąd przy wysyłaniu zapytania do bazy danych';
	CONST MESSAGE_ERROR_SEQUENCE = 'Nieprawidłowa wartość sekwencji - operacja przerwana';

	/**
	 * Instance of singleton class (in our case it’s the database connection)
	 *
	 * @access private
	 * @var object
	 * @static
	 */
	private static $_singleton;

	/**
	 * Czy udało się utworzyć transakcję?
	 *
	 * @access private
	 * @var boolean
	 * @static
	 */
	private static $_transOk = false;

	/**
	 * Licznik otwartych transakcji
	 *
	 * @access private
	 * @var integer
	 * @static
	 */
	private static $_transCounter = 0;

	/**
	 *
	 * Singleton pattern for database connection
	 *
	 * @return PDO
	 * @access public
	 * @static
	 */
	public static function getInstance() {
		if (!is_object(self::$_singleton)) {
			$dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
			try {
				self::$_singleton = new PDO($dsn, DB_USER, DB_PASS);

				// PDO::ATTR_DEFAULT_FETCH_MODE: Set default fetch mode. Description of modes is available in PDOStatement::fetch() documentation.
				//	PDO::FETCH_ASSOC: returns an array indexed by column name as returned in your result set
				//	PDO::FETCH_BOTH (default): returns an array indexed by both column name and 0-indexed column number as returned in your result set
				//	PDO::FETCH_BOUND: returns TRUE and assigns the values of the columns in your result set to the PHP variables to which they were bound with the PDOStatement::bindColumn() method
				//	PDO::FETCH_CLASS: returns a new instance of the requested class, mapping the columns of the result set to named properties in the class. If fetch_style includes PDO::FETCH_CLASSTYPE (e.g. PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE) then the name of the class is determined from a value of the first column.
				//	PDO::FETCH_INTO: updates an existing instance of the requested class, mapping the columns of the result set to named properties in the class
				//	PDO::FETCH_LAZY: combines PDO::FETCH_BOTH and PDO::FETCH_OBJ, creating the object variable names as they are accessed
				//	PDO::FETCH_NUM: returns an array indexed by column number as returned in your result set, starting at column 0
				//	PDO::FETCH_OBJ: returns an anonymous object with property names that correspond to the column names returned in your result set
				self::$_singleton->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

				// PDO::ATTR_CASE: Force column names to a specific case.
				//	PDO::CASE_LOWER: Force column names to lower case.
				//	PDO::CASE_NATURAL: Leave column names as returned by the database driver.
				//	PDO::CASE_UPPER: Force column names to upper case.
				self::$_singleton->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);

				// PDO::ATTR_ERRMODE: Error reporting.
				//	PDO::ERRMODE_SILENT: Just set error codes.
				//	PDO::ERRMODE_WARNING: Raise E_WARNING.
				//	PDO::ERRMODE_EXCEPTION: Throw exceptions.
				self::$_singleton->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

				///@TODO ogarnąć DEBUG
				//self::$_singleton->debugDumpParams(DB_DEBUG);
			} catch (PDOException $e) {
				$debugMsg = $e->getMessage() . "\n" . str_replace(DB_PASS, '*HIDDEN*', $e->getTraceAsString());
				MK_Error::getDataBase($debugMsg, $e->getFile(), strval($e->getLine()));

				$retArray = array(
					'success' => false,
					'message' => 'Nie udało się połączyć z bazą danych'
				);

				if (MK_DEVELOPER === true) {
					$retArray['debug'] = $debugMsg;
				}

				if (MK::isAjaxExecution(true)) {
					die(json_encode($retArray));
				}

				echo $retArray['message'] . PHP_EOL;

				if (MK_DEVELOPER) {
					echo $retArray['debug'] . PHP_EOL;
				}
				die();
			}
		}
		return self::$_singleton;
	}

	/**
	 * Ustawienie/Odczytanie statusu utworzenia transakcji.
	 * Podanie parametru $status ustawia nową wartość.
	 * Domyślnie zwraca ustawioną wcześniej wartość.
	 *
	 * @param boolean $status (default:null)
	 *
	 * @return boolean
	 */
	public static function transOk($status=null) {
		if (is_bool($status)) {
			self::$_transOk = $status;
		}
		return self::$_transOk;
	}

	/**
	 * Zwiększanie/Zmniejszanie wartości licznik. Dla $setValue=true wartość jest ustawiana.
	 *
	 * @param integer $incrementation (default:0) - wartość zwiększana/zmniejszana/ustawiana
	 * @param boolean $setValue (default:false) - zwiększanie / ustawianie wartości
	 *
	 * @return integer
	 */
	public static function transCount($incrementation=0, $setValue=false) {
		$incrementation = (int) $incrementation;
		if ($incrementation != 0) {
			if ($setValue == true) {
				self::$_transCounter = $incrementation;
			} else {
				self::$_transCounter += $incrementation;
			}
		}
		return self::$_transCounter;
	}

}