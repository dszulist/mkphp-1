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
	public static function mail($type, $file='null', $line='null') {

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

		$email = '<br/><table width="100%" border="0" cellspacing="0" cellpadding="0">'
				. '<tr><td style="width:180px;"><strong>Błąd typu:</strong></td><td>(' . $type . ')</td></tr>'
				. '<tr><td style="width:180px;"><strong>Linia:</strong></td><td>' . $line . '</td></tr>'
				. '<tr><td style="width:180px;"><strong>Plik:</strong></td><td>' . $file . '</td></tr>';

		if (isset($_SERVER["HTTP_HOST"])) {
			$email .= '<tr><td><strong>Host:</strong></td><td>' . $_SERVER["HTTP_HOST"] . '</td></tr>';
		}

		$email .= '<tr><td><strong>Baza danych:</strong></td><td>' . DB_NAME . '</td></tr>'
				. '<tr><td><strong>Login użytkownika:</strong></td><td>' . $userLogin . '</td></tr>'
				. '<tr><td><strong>ID użytkownika:</strong></td><td>' . $userId . '</td></tr>'
				. '<tr><td><strong>Data wystąpienia:</strong></td><td>' . date("Y-m-d H:i:s") . '</td></tr>';

		if (isset($_SERVER["REQUEST_URI"])) {
			$email .= '<tr><td><strong>REQUEST_URI:</strong></td><td>' . $_SERVER["REQUEST_URI"] . '</td></tr>';
		}
		if(defined('SITE_PATH')) {
			$email .= '<tr><td><strong>SITE_PATH:</strong></td><td>' . SITE_PATH . '</td></tr>';
		}
		if (isset($_SERVER["HTTP_USER_AGENT"])) {
			$email .= '<tr><td><strong>HTTP_USER_AGENT:</strong></td><td>' . $_SERVER["HTTP_USER_AGENT"] . '</td></tr>';
		}
		if (isset($_SERVER["REMOTE_ADDR"])) {
			$email .= '<tr><td><strong>REMOTE_ADDR:</strong></td><td>' . $_SERVER["REMOTE_ADDR"] . '</td></tr>';
		}

		$email .= '</table>';

		return $email;
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
		$subject = 'ERROR ' . DB_NAME . ' (PHP)' . (isset($_SERVER['HTTP_HOST']) ? ' [' . $_SERVER['HTTP_HOST'] . ']' : '');

		// W przypadku tego błędu nie logujemy ponieważ nie ma on się pojawiać
		if ($type == 2 && preg_match('#pg_fetch_array\(\) \[[^]]+\]: Unable to jump to row [0-9]+ on PostgreSQL result index [0-9]+#i', $message)) {
			return true;
		}

		$email = MK_Error::mail($type, $file, $line) . "\n"
				. '<br/><br/><strong>Komunikat:</strong> ' . $message;

		if (count($errContext) > 0) {
			$email .= '<br/><br/><strong>Informacje szczegółowe:</strong><pre>' . print_r($errContext, true) . '</pre>';
		}

		$email .= '<br/><br/><strong>Backtrace:</strong><pre>' . ( empty($debugBacktrace) ? print_r(debug_backtrace(), true) : $debugBacktrace ) . '</pre>';

		$headers = 'Content-type: text/html; charset=utf-8' . "\n"
				. 'MIME-Version: 1.0' . "\n"
				. 'X-Mailer: PHP/' . phpversion() . "\n"
				. 'From: ' . PHP_ERROR_EMAIL_ADDRESS . "\n"
				. 'Reply-To: ' . PHP_ERROR_EMAIL_ADDRESS . "\n";

		if (DEVELOPER === true) {
			return $email;
		}

		//error_log($email, 1, PHP_ERROR_EMAIL_ADDRESS, $headers);
		mail(PHP_ERROR_EMAIL_ADDRESS, $subject, $email, $headers);

		// Tutaj zwracamy informacje dla uzytkownika - w tym przypadku wyrzucamy wyjatek ktory zwróci jsona z informacja o obedzie ktora zostanie wyswietlana uzytkownikowi w postaci okna z błędem
		if (($type !== E_NOTICE) && ($type < 2048)) {
			exit('Nieoczekiwany błąd! Szczegółowy komunikat został wysłany do Administratora.');
		}

		return false;
	}

	/**
	 * Funkcja tworzy e-mail z treścią błędu bazy danych i wysyła w przypadku wyłączonego DEVELOPER-a
	 *
	 * @param string $debugMsg
	 * @param string $file (default: "")
	 * @param integer $line (default: 0)
	 * @param string $debugBacktrace (default: "")
	 */
	public static function getDataBase($message, $file="", $line=0, $debugBacktrace="") {
		$subject = 'ERROR ' . DB_NAME . ' (DB)' . (isset($_SERVER['HTTP_HOST']) ? ' [' . $_SERVER['HTTP_HOST'] . ']' : '');

		$email = MK_Error::mail('Database', $file, $line) . "\n"
				. '<table width="100%" border="1">'
				. '<tr><td colspan="2"><strong>Message:</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . $message . '</pre></td></tr>'
				. '<tr><td colspan="2"><strong>Backtrace:</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . (empty($debugBacktrace) ? print_r(debug_backtrace(), true) : $debugBacktrace ) . '</pre></td></tr>'
				. '</table>';

		$headers = 'Content-type: text/html; charset=utf-8' . "\n"
				. 'MIME-Version: 1.0' . "\n"
				. 'X-Mailer: PHP/' . phpversion() . "\n"
				. 'From: ' . SQL_ERROR_EMAIL_ADDRESS . "\n"
				. 'Reply-To: ' . SQL_ERROR_EMAIL_ADDRESS . "\n";

		if (DEVELOPER !== true) {
			mail(SQL_ERROR_EMAIL_ADDRESS, $subject, $email, $headers);
		}
	}

	/**
	 * Funkcja tworzy email z trescia bledu JavaScriptowego i wysyła maila w przypadku wyłączonego  'DEVELOPERA'
	 */
	public static function getJavaScript() {
		$subject = 'ERROR ' . DB_NAME . ' (JS)' . (isset($_SERVER['HTTP_HOST']) ? ' [' . $_SERVER['HTTP_HOST'] . ']' : '');
		if (isset($_COOKIE['ys-javascriptErrorLog'])) {
			$errorObject = json_decode(substr($_COOKIE['ys-javascriptErrorLog'], 2));
			setcookie('ys-javascriptErrorLog', '', time() - 86400, COOKIES_PATH);

			$email = MK_Error::mail('JavaScript') . "\n"
					. '<table width="100%" border="1">'
					. '<tr><td colspan="2"><strong>Informacje:</strong></td></tr>'
					. '<tr><td colspan="2"><pre>' . print_r($errorObject, true) . '</pre></td></tr>'
					. '</table>';

			$headers = 'Content-type: text/html; charset=utf-8' . "\n"
					. 'MIME-Version: 1.0' . "\n"
					. 'X-Mailer: PHP/' . phpversion() . "\n"
					. 'From: ' . PHP_ERROR_EMAIL_ADDRESS . "\n"
					. 'Reply-To: ' . PHP_ERROR_EMAIL_ADDRESS . "\n";

			if (DEVELOPER !== true) {
				mail(PHP_ERROR_EMAIL_ADDRESS, $subject, $email, $headers);
			}
		}
	}

}