<?php

/**
 * MK
 *
 * @category MK
 * @package	MK
 * @author	bskrzypkowiak
 */
class MK {

	/**
	 * Autoloader dla MKPhp
	 * 
	 * @param String $className 
	 * @return Boolean
	 */
	public static function _autoload($className) {
		if (substr($className, 0, 3) == 'MK_') {
			$file = substr(MK_PATH, 0, -3) . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
			include $file;
			return true;
		}
		return false;
	}

	/**
	 * Sprawdza czy w aplikacji jest włączone "debugowanie"
	 * 
	 * @return Boolean 
	 */
	public static function isDebugEnabled() {
		return (DEVELOPER === true || (isset($_SESSION['APP_DEBUG']) && $_SESSION['APP_DEBUG'] === true));
	}

	/**
	 * Funkcja wywoływana na zakończenie skryptu PHP
	 * w przypadku gdy skrypt kończy się błędem uruchamia funkcje powiadamiajaca o błędzie
	 */
	public static function shutdownFunction() {
		$error = error_get_last();
		if (!empty($error)) {
			MK_Error::handler(
					isset($error['type']) ? $error['type'] : null, isset($error['message']) ? $error['message'] : null, isset($error['file']) ? $error['file'] : null, isset($error['line']) ? $error['line'] : null
			);
		}
	}

	/**
	 * Uruchamia kontroler konsoli i wywołuje z niego metode z parametrami
	 *
	 * @param Array		$argv - (default:array())Tablica z parametrami przekazanymi w lini polecen
	 * @return Boolean
	 */
	public static function executeCLICommand(array $argv=array()) {
		$consoleController = new ConsoleController();
		$opts = getopt('m::');

		if (count($argv) > 2) {
			$argv = array_slice($argv, 2);
		}

		if (!empty($opts)) {
			foreach (array_keys($opts) as $opt) {
				switch ($opt) {
					case 'm': $consoleController->{$opts['m']}($argv);
						break;
				}
			}
		}
	}

	/**
	 * Sprawdza czy skrypt jest wywolany z lini polecen.
	 * W przypadku wywolania z lini polecen zwraca true w przeciwnym wypadku false
	 * Jeżeli podamy w parametrze wywołania funkcji true to zostanie uruchomiony kontroler linii polecen
	 *
	 * @param Boolean	$executeCmd - (default:false) czy uruchomić kontroler lini polecen
	 * @param Array		$argv - (default:array()) Tablica z parametrami przekazanymi w lini polecen
	 * @return Boolean
	 */
	public static function isCLIExecution($executeCmd=false, array $argv=array()) {
		if (defined("STDIN")) {
			if ($executeCmd === true) {
				self::executeCLICommand($argv);
			}
			return true;
		}
		return false;
	}

	/**
	 * Sprawdza czy rządanie jest wysłane za pomocą XMLHttpRequest (AJAX)
	 * w przypadku ajax'a zwraca true
	 * w pozostałych przypadkach false
	 *
	 * @param Boolean	$sendHeaders (default:false) - jezeli true to wyśle nagłówki  dla typu JSON i z wyłączonym caschowaniem
	 *
	 * @return Boolean
	 */
	public static function isAjaxExecution($sendHeaders = false) {
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
			if ($sendHeaders) {
				MK::sendJSONHeaders();
			}
			return true;
		}
		return false;
	}

	/**
	 *  Sprawdza czy istnieje plik blokujacy uzycie aplikacji
	 */
	public static function checkApplicationState() {
		if (file_exists(FILE_APP_LOCK)) {
			if (strpos(file_get_contents(FILE_APP_LOCK), 'upgrade') !== false) {

				if (self::isAjaxExecution(true)) {
					echo '{"success":false,"msg":"<b>Przerwa techniczna.</b><br />Proszę spróbować za 10 minut.<br />Proszę nie wyłączać i nie restartować serwera."}';
				} else {
					header("Content-type: text/html; charset=utf-8");
					echo "<center><div style='margin-top:80px;'><img src='public/images/docflow_logo.png' alt='Logo Docflow'>"
					. "<h1>Przerwa techniczna.</h1><br />Proszę spróbować za 10 minut.<br />Proszę nie wyłączać i nie restartować serwera.<br />"
					. "</div><br /><img alt='W trakcie przetwarzania' align='top' src='public/images/ajax/ajaxLoadingSmall.gif' /></center>";
				}
				die;
			}
		}
	}

	/**
	 * Tworzy nagłówki JSON, i ustawia brak cashowania (do obsługi zapytan typu XHR)
	 */
	public static function sendJSONHeaders() {
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 10) . ' GMT');
		header('Content-type: application/json');
	}
	
}

