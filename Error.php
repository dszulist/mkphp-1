<?php

/**
 * MK_Error
 *
 * Obsługa błędów php/js zgłaszanie na maila etc.
 *
 * @category MK
 * @package    MK_Error
 * @author bskrzypkowiak
 */
class MK_Error
{
	/**
	 * Dane użytkownika z sesji
	 * @var array
	 */
	private static $userData = array();

	/**
	 * @var string
	 */
	private static $mailAdmin = 'Szczegółowy komunikat został wysłany do Administratora.';

	/**
	 * Ignorowanie określonych klas z metodami
	 */
	private static $traceIgnorePath = array(
		'MK_Error::handler',
		'MK_Error::getExtendedTrace',
		'MK::shutdownFunction',
		'printr'
	);

	/**
	 * Ustawienie większej ilości klas do ignorowania dla fireBugSqlDump()
	 *
	 * @param $tracePath
	 *
	 * @internal param array $classArray
	 */
	public static function setMoreTraceIgnorePath($tracePath)
	{
		self::$traceIgnorePath = array_merge(self::$traceIgnorePath, $tracePath);
	}

	/**
	 * Uproszczony raport błędu dla Exception.
	 * Zapisanie zdarzenia w pliku tekstowym i wysłanie do logs.madkom.pl (dla developer:false)
	 *
	 * try {
	 *     // code
	 * } catch (Exception $e) {
	 *     die(MK_Error::getSimpleMessage($e));
	 * }
	 *
	 * @param Exception $exceptionClass
	 *
	 * @return string
	 */
	public static function getSimpleInfo(Exception $exceptionClass)
	{
		return '<pre>' . self::fromException($exceptionClass->getMessage(), $exceptionClass->getFile(), strval($exceptionClass->getLine()), self::getExtendedTrace($exceptionClass)) . '</pre>';
	}

	/**
	 * Rozbudowany raport ścieżki błędu
	 *
	 * @param Exception $exception
	 *
	 * @return string
	 */
	public static function getExtendedTrace($exception = null)
	{
		$traceKey = 1;
		$msg = " #" . $traceKey++ . "\t";

		if ($exception instanceof Exception) {
			$msg .= $exception->getFile() . '(' . $exception->getLine() . ')';
			$traceArray = $exception->getTrace();
		} else {
			$traceArray = debug_backtrace();
		}

		// Odwrócenie kolejności czytania tablicy ze śladami
		$traceArray = array_reverse($traceArray, true);

		foreach ($traceArray as $trace) {
			$_class = isset($trace['class']) ? $trace['class'] : '';
			$_type = isset($trace['type']) ? $trace['type'] : '';
			$_function = isset($trace['function']) ? $trace['function'] : '';
			$_file = isset($trace['file']) ? $trace['file'] : '';
			$_line = isset($trace['line']) ? $trace['line'] : -1;

			// Ignorowanie klas z metodami (self::$traceIgnorePath)
			$classTypeFunction = $_class . $_type . $_function;
			if (in_array($classTypeFunction, self::$traceIgnorePath)) {
				continue;
			}

			// Śledzenie pliku, wraz z wywoływaną klasą, metodą i argumentami:
			$msg .= "\n #" . $traceKey++ . "\t" . $_file . '(' . $_line . '): ' . $classTypeFunction . '(';

			// Odczytanie argumentów
			if (isset($trace['args']) && count($trace['args']) > 0) {
				foreach ($trace['args'] as $argsKey => $argsValue) {
					$msg .= ($argsKey ? ' , ' : ' ');
					$msg .= print_r($argsValue, true);
				}
			}
			$msg .= ' )';
		}

		return $msg . "\n";
	}

	/**
	 * Tworzy szczegółowe informacje dla raportu błędu
	 *
	 * @param string $file (default: "(null)")
	 * @param string $line (default: "(null)")
	 *
	 * @return string
	 */
	private static function _prepareMessage($file = '(null)', $line = '(null)')
	{
		$userDataMsg = '';
		if (!empty(self::$userData)) {
			$userDataMsg = "\nUżytkownik:\n";
			foreach (self::$userData as $key => $val) {
				$userDataMsg .= " " . $key . ": " . $val . "\n";
			}
		}

		$devMessage = " Host:\t" . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'brak danych') . "\n"
			. " Plik:\t{$file}\n"
			. " Linia:\t{$line}\n"
			. "\nBaza danych:\n"
			. " Host:\t" . DB_HOST . "\n"
			. " Nazwa:\t" . DB_NAME . "\n"
			. $userDataMsg
			. "\nInformacje dodatkowe:\n"
			. " REMOTE_ADDR:\t" . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'brak danych') . "\n"
			. " SERVER_ADDR:\t" . (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'brak danych') . "\n"
			. " REQUEST_URI:\t" . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'brak danych') . "\n"
			. " USER_AGENT:\t" . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'brak danych') . "\n";

		if (defined('APP_PATH')) {
			$devMessage .= " APP_PATH:\t" . APP_PATH . "\n"
				. " COOKIES_PATH:\t" . MK_COOKIES_PATH . "\n";
		}

		return $devMessage . "\n";
	}

	/**
	 * Obsługa błędów PHP. Zapisywanie informacji do pliku.
	 *
	 * @param Integer     $type
	 * @param String      $message (default: "")
	 * @param String      $file (default: "")
	 * @param int|string  $line (default: "")
	 * @param Mixed       $errContext (default: array())
	 * @param String      $debugBacktrace (default: "")
	 *
	 * @return Boolean
	 */
	public static function handler($type, $message = "", $file = "", $line = "", $errContext = array(), $debugBacktrace = "")
	{
		if (!($type & error_reporting())) {
			return true;
		}
		// W przypadku tego błędu nie logujemy ponieważ nie ma on się pojawiać
		if (preg_match('#pg_fetch_array\(\)( \[[^]]+\])*: Unable to jump to row [0-9]+ on PostgreSQL result index [0-9]+#i', $message)) {
			return true;
		}

		$devMessage = self::_prepareMessage($file, $line) . "Komunikat błędu:\n " . $message . "\n\n";
		$md5 = md5($message . $file . $line);
		$devMessage .= "ERROR CODE: " . $type . "\n";
		$devMessage .= "ERROR TYPE: " . self::getErrorType($type) . "\n";
		if (count($errContext) > 0) {
			$devMessage .= "Informacje szczegółowe:\n " . substr(print_r($errContext, true), 0, 10240) . "\n\n";
		}
		$devMessage .= "Backtrace:\n" . substr(empty($debugBacktrace) ? print_r(debug_backtrace(), true) : $debugBacktrace, 0, 1024) . "\n";

		if (MK_DEBUG === true) {
			return "Błąd \"php\"\t" . $md5 . "\n" . $devMessage;
		}
		if (!class_exists('MK_Logs')) {
			include_once('Logs.php');
		}
		// Tutaj zwracamy informacje dla uzytkownika - w tym przypadku wyrzucamy wyjatek ktory zwróci jsona z informacja o obedzie ktora zostanie wyswietlana uzytkownikowi w postaci okna z błędem
		if (($type !== E_NOTICE) && ($type < 2048)) {
			return 'Nieoczekiwany błąd! ' . self::$mailAdmin;
		}

		$logs = new MK_Logs(APP_PATH);
		$logs->saveToFile('php', $devMessage, $md5);

		return false;
	}

	public static function getErrorType($errno)
	{
		switch ($errno) {
			case E_NOTICE:
			case E_USER_NOTICE:
				return 'Notice';
				break;
			case E_WARNING:
			case E_USER_WARNING:
			case E_STRICT:
				return 'Warning';
				break;
			case E_ERROR:
			case E_USER_ERROR:
				return 'Fatal Error';
				break;
			default:
				return 'Unknown Error';
				break;
		}
	}

	/**
	 * Obsługa błędów zwróconych przez aplikację. Zapisywanie informacji do pliku.
	 *
	 * @param String     $message (default: "")
	 * @param String     $file (default: "")
	 * @param int|string $line (default: "")
	 * @param String     $debugBacktrace (default: "")
	 *
	 * @return Boolean
	 */
	public static function fromException($message = "", $file = "", $line = "", $debugBacktrace = "")
	{
		$md5 = md5($message . $file . $line);
		$devMessage = self::_prepareMessage($file, $line) . "Komunikat:\n " . $message . "\n\n"
			. "Backtrace:\n" . substr(empty($debugBacktrace) ? print_r(debug_backtrace(), true) : $debugBacktrace, 0, 1024) . "\n";

		if (MK_DEBUG === true) {
			return "Błąd \"exception\"\t" . $md5 . "\n" . $devMessage;
		}

		$logs = new MK_Logs(APP_PATH);
		$logs->saveToFile('exception', $devMessage, $md5);

		return $message;
	}

	/**
	 * Obsługa błędów w bazie danych. Zapisywanie informacji do pliku.
	 *
	 * @param string   $message
	 * @param string   $file (default: "")
	 * @param integer  $line (default: 0)
	 * @param string   $debugBacktrace (default: "")
	 *
	 * @return string
	 */
	public static function fromDataBase($message = "", $file = "", $line = 0, $debugBacktrace = "")
	{
		$md5 = md5($message . $file . $line);
		$devMessage = self::_prepareMessage($file, $line) . "Komunikat:\n " . $message . "\n\n"
			. "Backtrace:\n" . substr(empty($debugBacktrace) ? print_r(debug_backtrace(), true) : $debugBacktrace, 0, 1024) . "\n";

		if (MK_DEBUG === true) {
			return "Błąd \"db\"\t" . $md5 . "\n" . $devMessage;
		}

		$logs = new MK_Logs(APP_PATH);
		$logs->saveToFile('db', $devMessage, $md5);

		return $message;
	}

	/**
	 * Obsługa błędu JavaScript odczytanego z ciastka. Zapisywanie informacji do pliku.
	 *
	 * @return string
	 */
	public static function fromJavaScript()
	{
		if (isset($_COOKIE['ys-javascriptErrorLog'])) {
			MK_Cookie::clear('ys-javascriptErrorLog');
			$errorObject = json_decode(substr($_COOKIE['ys-javascriptErrorLog'], 2));

			$md5 = md5(print_r($errorObject, true));
			$devMessage = self::_prepareMessage() . "Komunikat:\n " . substr(print_r($errorObject, true), 0, 1024) . "\n\n";

			if (MK_DEBUG === true) {
				return "Błąd \"js\"\t" . $md5 . "\n" . $devMessage;
			}

			$logs = new MK_Logs(APP_PATH);
			$logs->saveToFile('js', $devMessage, $md5);

			return 'Błąd JavaScript. ' . self::$mailAdmin;
		}
		return null;
	}

	/**
	 * Ustawia dane użytkownika z sesji
	 * @param array $userData
	 */
	public static function setUserData(array $userData) {
		self::$userData = $userData;
	}

}