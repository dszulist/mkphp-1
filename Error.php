<?php

/**
 * MK_Error
 *
 * Obsługa błędów php/js zgłaszanie na maila etc.
 *
 * @category MK
 * @package	MK_Error
 * @author bskrzypkowiak
 */
class MK_Error {

	private static $_mailAdmin = 'Szczegółowy komunikat został wysłany do Administratora.';

	/**
	 * Ignorowanie określonych klas z metodami
	 */
	private static $_traceIgnorePath = array(
		'MK_Error::handler',
		'MK_Error::getExtendedTrace',
		'MK::shutdownFunction'
	);

	/**
	 * Ustawienie większej ilości klas do ignorowania dla fireBugSqlDump()
	 * @param array $classArray
	 */
	public static function setMoreTraceIgnorePath($tracePath) {
		self::$_traceIgnorePath = array_merge(self::$_traceIgnorePath, $tracePath);
	}

	/**
	 * Przygotowanie ścieżki do folderu z raportami błędów
	 *
	 * @return string
	 */
	private static function _saveErrorLog($type, $message) {
		$type = strtoupper($type);
		$errorTime = time();
		$errorEmail = ($type == 'DB') ? SQL_ERROR_EMAIL_ADDRESS : PHP_ERROR_EMAIL_ADDRESS;
		$errorFile = DIR_ERRORS . DIRECTORY_SEPARATOR . date('Y-m-d', $errorTime) . '_' . strtolower($type) . '.log';
		$subject = 'ERROR ' . DB_NAME . ' (' . $type . ')' . (isset($_SERVER['HTTP_HOST']) ? ' [' . $_SERVER['HTTP_HOST'] . ']' : '');
		$headers = 'Content-type: text/html; charset=utf-8' . "\n"
				. 'MIME-Version: 1.0' . "\n"
				. 'X-Mailer: PHP/' . phpversion() . "\n"
				. 'From: ' . $errorEmail . "\n"
				. 'Reply-To: ' . $errorEmail . "\n";

		if (!file_exists($errorFile)) {
			$errorUrl = 'http://' . $_SERVER['HTTP_HOST'] . MK_COOKIES_PATH . DIRECTORY_SEPARATOR . '?logd=' . $errorTime . '&logt=' . $type . '&logs=' . md5($_SERVER['HTTP_HOST'] . $type . $errorTime);
			$mailMsg = "Wystąpił błąd {$type}. Raport z całego dnia dostępny pod adresem: <a href=\"{$errorUrl}\">{$errorUrl}</a>"
					. "<hr/>Poniżej pierwszy zgłoszony raport błędu:<br/>" . $message;
			mail($errorEmail, $subject, $mailMsg, $headers);
		}

		error_log($message . '<hr/>', 3, $errorFile);
	}

	/**
	 * Przeglądanie raportów błędów wybranego typu
	 *
	 * @param integer $logDate
	 * @param string $logType
	 * @param string $logSecure
	 *
	 * @return string
	 */
	public static function previewLog($logDate, $logType, $logSecure) {
		if (md5($_SERVER['HTTP_HOST'] . $logType . $logDate) !== $logSecure) {
			throw new MK_Exception('Nieuprawniony dostęp do raportów błędów');
		}

		$errorFile = DIR_ERRORS . DIRECTORY_SEPARATOR . date('Y-m-d', $logDate) . '_' . strtolower($logType) . '.log';
		if (!file_exists($errorFile)) {
			throw new MK_Exception("Plik {$errorFile} nie istnieje!");
		}

		if (filesize($errorFile) > 5242880) { // 5242880 = 5MB
			header('Content-Disposition: attachment; filename="' . basename($errorFile) . '.html"');
			$hTitle = "ERROR {$logType} - " . date("Y-m-d", $logDate);
			echo <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="pl" xml:lang="pl">
	<head>
	  	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />
	  	<title>{$hTitle}</title>
	</head>
<body>
EOF;
			readfile($errorFile);
			echo '</body></html>';
		} else {
			header('Content-Type: text/html; charset=utf-8');
			readfile($errorFile);
		}
		die();
	}

	/**
	 * Wyświetlenie tablicy i zwrócenie wyjątku MK_Exception.
	 * Pomocne przy debugowaniu kawałka kodu źródłowego.
	 * Wywoływanie:
	 * 		// Wyświetlenie tablicy za pomocą var_dump()
	 * 		MK_Error:preview( array('test'=>'ok') );
	 * 		// Wyświetlenie tablicy za pomocą print_r()
	 * 		MK_Error:preview( array('test'=>'ok') , false );
	 *
	 * @param Array $arrayValues	- Tablica wartości do wyświetlenia
	 * @param Boolean $varDump		- (def:true) Czy wyświetlić dane przez var_dump() czy print_r()
	 *
	 * @throws MK_Exception
	 */
	public static function preview($arrayValues, $varDump=true) {
		if ($varDump === true) {
			ob_start();
			var_dump($arrayValues);
			$str = ob_get_clean();
		} else {
			$str = print_r($arrayValues, true);
		}
		throw new MK_Exception('<pre>' . $str . '</pre>');
	}

	/**
	 * Zwrócenie komunikatu błędu dla Exception
	 *
	 * @param string $msg
	 * @param object $exceptionClass
	 *
	 * @return string
	 */
	public static function getSimpleInfo($msg, $exceptionClass=null) {
		if (is_object($exceptionClass)) {
			$_file = $exceptionClass->getFile();
			$_line = strval($exceptionClass->getLine());
			$_trace = MK_Error::getExtendedTrace($exceptionClass);
		} else {
			$_file = "";
			$_line = "";
			$_trace = "";
		}
		return MK_Error::handler(E_NOTICE, $msg, $_file, $_line, array(), $_trace);
	}

	/**
	 * Rozbudowany raport ścieżki błędu
	 */
	public static function getExtendedTrace($exception=null) {
		$traceKey = 1;
		$msg = '<br /><b>#' . $traceKey++ . '</b> ';

		if (is_object($exception)) {
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

			// Ignorowanie klas z metodami (self::$_traceIgnorePath)
			$classTypeFunction = $_class . $_type . $_function;
			if (in_array($classTypeFunction, self::$_traceIgnorePath)) {
				continue;
			}

			// Śledzenie pliku, wraz z wywoływaną klasą, metodą i argumentami:
			$msg .= '<br /><b>#' . $traceKey++ . '</b> ' . $_file . '(' . $_line . '): <b>' . $classTypeFunction . '(</b>';

			// Odczytanie argumentów
			if (isset($trace['args']) && count($trace['args']) > 0) {
				foreach ($trace['args'] as $argsKey => $argsValue) {
					$msg .= ($argsKey ? ' <b>,</b> ' : ' ');
					$msg .= '<pre>' . print_r($argsValue, true) . '</pre>';
				}
			}
			$msg .= ' <b>)</b>';
		}

		return $msg;
	}

	/**
	 * Tworzy czesc tresci maila z błędem wspólna dla błędów php/js/sql
	 *
	 * @param type $type
	 * @param type $file
	 * @param type $line
	 * @return string
	 */
	public static function prepareMailMsg($type, $file='null', $line='null') {
		$userLogin = 'Brak informacji';
		$userId = 'Brak informacji';
		//@TODO - ugryźć jako parametruyzacja czy cos :)
		//	if(!UserSingleton::getInstance()->isLogged()) {
		//		$userLogin = 'Brak informacji';
		//		$userId = 'Brak informacji';
		//	}
		//	else {
		//		$userLogin = UserSingleton::getInstance()->getCurrentUserLogin();
		//		$userId = UserSingleton::getInstance()->getCurrentUserCellId(false);
		//	}

		$emailMsg = '<br/><table width="100%" border="0" cellspacing="0" cellpadding="0">'
				. '<tr><td style="width:180px;"><strong>Błąd typu:</strong></td><td>(' . $type . ')</td></tr>'
				. '<tr><td style="width:180px;"><strong>Linia:</strong></td><td>' . $line . '</td></tr>'
				. '<tr><td style="width:180px;"><strong>Plik:</strong></td><td>' . $file . '</td></tr>';

		if (isset($_SERVER["HTTP_HOST"])) {
			$emailMsg .= '<tr><td><strong>Host:</strong></td><td>' . $_SERVER["HTTP_HOST"] . '</td></tr>';
		}

		$emailMsg .= '<tr><td><strong>Baza danych:</strong></td><td>' . DB_NAME . '</td></tr>'
				. '<tr><td><strong>Login użytkownika:</strong></td><td>' . $userLogin . '</td></tr>'
				. '<tr><td><strong>ID użytkownika:</strong></td><td>' . $userId . '</td></tr>'
				. '<tr><td><strong>Data wystąpienia:</strong></td><td>' . date("Y-m-d H:i:s") . '</td></tr>';

		if (isset($_SERVER["REQUEST_URI"])) {
			$emailMsg .= '<tr><td><strong>REQUEST_URI:</strong></td><td>' . $_SERVER["REQUEST_URI"] . '</td></tr>';
		}
		if (defined('APP_PATH')) {
			$emailMsg .= '<tr><td><strong>APP_PATH:</strong></td><td>' . APP_PATH . '</td></tr>';
		}
		if (isset($_SERVER["HTTP_USER_AGENT"])) {
			$emailMsg .= '<tr><td><strong>HTTP_USER_AGENT:</strong></td><td>' . $_SERVER["HTTP_USER_AGENT"] . '</td></tr>';
		}
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$emailMsg .= '<tr><td><strong>REMOTE_ADDR:</strong></td><td>' . $_SERVER["REMOTE_ADDR"] . '</td></tr>';
		}

		$emailMsg .= '</table>';

		return $emailMsg;
	}

	/**
	 *
	 * Obsługa błędów PHP - przechwytuje błąd i wysyła wiadomość o błędzie na email
	 *
	 * @param Integer	 $type
	 * @param String	 $message
	 * @param String	 $file
	 * @param Integer 	 $line
	 * @param Array 	 $errContext
	 * @param String     $debugBacktrace
	 *
	 * @return Boolean
	 */
	public static function handler($type, $message="", $file="", $line="", array $errContext=array(), $debugBacktrace="") {
		// W przypadku tego błędu nie logujemy ponieważ nie ma on się pojawiać
		if ($type == 2 && preg_match('#pg_fetch_array\(\) \[[^]]+\]: Unable to jump to row [0-9]+ on PostgreSQL result index [0-9]+#i', $message)) {
			return true;
		}

		$emailMsg = MK_Error::prepareMailMsg($type, $file, $line) . "\n"
				. '<br/><br/><strong>Komunikat:</strong> ' . $message;

		if (count($errContext) > 0) {
			$emailMsg .= '<br/><br/><strong>Informacje szczegółowe:</strong><pre>' . print_r($errContext, true) . '</pre>';
		}

		$emailMsg .= '<br/><br/><strong>Backtrace:</strong><pre>' . ( empty($debugBacktrace) ? print_r(debug_backtrace(), true) : $debugBacktrace ) . '</pre>';

		if (MK_DEVELOPER === true) {
			return $emailMsg;
		}

		self::_saveErrorLog('php', $emailMsg);

		// Tutaj zwracamy informacje dla uzytkownika - w tym przypadku wyrzucamy wyjatek ktory zwróci jsona z informacja o obedzie ktora zostanie wyswietlana uzytkownikowi w postaci okna z błędem
		if (($type !== E_NOTICE) && ($type < 2048)) {
			return 'Nieoczekiwany błąd! ' . self::$_mailAdmin;
		}

		return $message;
	}

	/**
	 * Funkcja tworzy e-mail z treścią błędu bazy danych i wysyła w przypadku wyłączonego MK_DEVELOPER-a
	 *
	 * @param string $debugMsg
	 * @param string $file (default: "")
	 * @param integer $line (default: 0)
	 * @param string $debugBacktrace (default: "")
	 *
	 * @return string
	 */
	public static function getDataBase($message, $file="", $line=0, $debugBacktrace="") {
		$emailMsg = MK_Error::prepareMailMsg('Database', $file, $line) . "\n"
				. '<table width="100%" border="1">'
				. '<tr><td colspan="2"><strong>Message:</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . $message . '</pre></td></tr>'
				. '<tr><td colspan="2"><strong>Backtrace:</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . (empty($debugBacktrace) ? print_r(debug_backtrace(), true) : $debugBacktrace ) . '</pre></td></tr>'
				. '</table>';

		if (MK_DEVELOPER === true) {
			return $emailMsg;
		}

		self::_saveErrorLog('db', $emailMsg);

		return $message;
	}

	/**
	 * Funkcja tworzy email z trescia bledu JavaScriptowego i wysyła maila w przypadku wyłączonego  'MK_DEVELOPERA'
	 *
	 * @return string
	 */
	public static function getJavaScript() {
		if (isset($_COOKIE['ys-javascriptErrorLog'])) {
			$errorObject = json_decode(substr($_COOKIE['ys-javascriptErrorLog'], 2));
			MK_Cookie::clear('ys-javascriptErrorLog');

			$emailMsg = MK_Error::prepareMailMsg('JavaScript') . "\n"
					. '<table width="100%" border="1">'
					. '<tr><td colspan="2"><strong>Informacje:</strong></td></tr>'
					. '<tr><td colspan="2"><pre>' . print_r($errorObject, true) . '</pre></td></tr>'
					. '</table>';

			if (MK_DEVELOPER === true) {
				return $emailMsg;
			}

			self::_saveErrorLog('js', $emailMsg);

			return 'Błąd JavaScript. ' . self::$_mailAdmin;
		}
	}

}