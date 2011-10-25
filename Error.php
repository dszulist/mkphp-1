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
	 *
	 * Obsługa błędów PHP - przechwytuje błąd i wysyła wiadomość o błędzie na email
	 *
	 * @param Integer	 $type
	 * @param String	 $message
	 * @param String	 $file
	 * @param Integer 	 $line
	 * @param Array 	 $errcontext
	 *
	 * @return Boolean
	 */
	public static function handler($type, $message="", $file="", $line="", array $errcontext=array()) {
		$subject = 'ERROR ' . DB_NAME . ' (PHP)' . (isset($_SERVER['HTTP_HOST']) ? ' [' . $_SERVER['HTTP_HOST'] . ']' : '');
		// W przypadku tego błędu nie logujemy ponieważ nie ma on się pojawiać
		if ($type == 2 && preg_match('#pg_fetch_array\(\) \[[^]]+\]: Unable to jump to row [0-9]+ on PostgreSQL result index [0-9]+#i', $message)) {
			return true;
		}

		$email = MK_Error::mail($type, $file, $line) . "\n"
				. '<table width="100%" border="1">'
				. '<tr><td colspan="2"><strong>Wiadomość:</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . $message . '</pre></td></tr>'
				. '<tr><td colspan="2"><strong>Informacje szczegółowe:</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . print_r($errcontext, true) . '</pre></td></tr>'
				. '<tr><td colspan="2"><strong>SQL Backtrace :</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . debug_backtrace() . '</pre></td></tr>'
				. '</table>';

		$headers = 'Content-type: text/html; charset=utf-8' . "\n"
				. 'MIME-Version: 1.0' . "\n"
				. 'X-Mailer: PHP' . "\n"
				. 'From: ' . PHP_ERROR_EMAIL_ADDRESS . "\n";

		if (DEVELOPER === true) {
			exit($email);
		} else {
			//error_log($email, 1, PHP_ERROR_EMAIL_ADDRESS, $headers);
			mail(PHP_ERROR_EMAIL_ADDRESS, $subject, $email, $headers);
		}

		// Tutaj zwracamy informacje dla uzytkownika - w tym przypadku wyrzucamy wyjatek ktory zwróci jsona z informacja o obedzie ktora zostanie wyswietlana uzytkownikowi w postaci okna z błędem
		if (($type !== E_NOTICE) && ($type < 2048)) {
			exit('Błąd! Prosze spróbować jeszcze raz.');
		}

		return false;
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

		$date = date("Y-m-d H:i:s");

		$email = '<br/><br/><table width="100%" border="1">'
				. '<tr><td colspan="2"><center><strong>Tabela informacyjna<strong></center></td></tr>'
				. '<tr><td><strong>Błąd typu:</strong></td><td>(' . $type . ')</td></tr>'
				. '<tr><td><strong>Linia:</strong></td><td>' . $line . '</td></tr>'
				. '<tr><td><strong>Plik:</strong></td><td>' . $file . '</td></tr>';

		if (isset($_SERVER["HTTP_HOST"])) {
			$email .= '<tr><td><strong>Host:</strong></td><td>' . $_SERVER["HTTP_HOST"] . '</td></tr>';
		}

		$email .= '<tr><td><strong>Baza danych:</strong></td><td>' . DB_NAME . '</td></tr>'
				. '<tr><td><strong>Login użytkownika:</strong></td><td>' . $userLogin . '</td></tr>'
				. '<tr><td><strong>ID użytkownika:</strong></td><td>' . $userId . '</td></tr>'
				. '<tr><td><strong>Data wystąpienia:</strong></td><td>' . $date . '</td></tr>';

		if (isset($_SERVER["REQUEST_URI"])) {
			$email .= '<tr><td><strong>REQUEST_URI:</strong></td><td>' . $_SERVER["REQUEST_URI"] . '</td></tr>';
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
	 * Funkcja tworzy e-mail z treścią błędu bazy danych i wysyła w przypadku wyłączonego DEVELOPER-a
	 */
	public static function getDataBase($debugMsg, $file="", $line=0) {
		$subject = 'ERROR ' . DB_NAME . ' (DB)' . (isset($_SERVER['HTTP_HOST']) ? ' [' . $_SERVER['HTTP_HOST'] . ']' : '');
		$email = MK_Error::mail('Database', $file, $line) . "\n"
				. '<table width="100%" border="1">'
				. '<tr><td colspan="2"><strong>Informacje:</strong></td></tr>'
				. '<tr><td colspan="2"><pre>' . $debugMsg . '</pre></td></tr>'
				. '</table>';

		$headers = 'Content-type: text/html; charset=utf-8' . "\n"
				. 'MIME-Version: 1.0' . "\n"
				. 'X-Mailer: PHP' . "\n"
				. 'From: ' . SQL_ERROR_EMAIL_ADDRESS . "\n";

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
					. 'X-Mailer: PHP' . "\n"
					. 'From: ' . PHP_ERROR_EMAIL_ADDRESS . "\n";

			if (DEVELOPER !== true) {
				mail(PHP_ERROR_EMAIL_ADDRESS, $subject, $email, $headers);
			}
		}
	}

}