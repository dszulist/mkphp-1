<?php

include 'Config' . DIRECTORY_SEPARATOR . 'defines.php';

spl_autoload_register('MK_autoload');

/**
 *
 * @param String $className 
 */
function MK_autoload($className){	
	if(substr($className, 0, 3) == 'MK_'){
		$file = substr(MK_PATH, 0, -3) . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';		
		include $file;
	}
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
function isAjaxExecution($sendHeaders = false){
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'){
		if($sendHeaders) {
			sendJSONHeaders();
		}
		return true;
	}
	return false;
}


/**
 * Tworzy nagłówki JSON, i ustawia brak cashowania (do obsługi zapytan typu XHR)
 */
function sendJSONHeaders(){
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()-10) . ' GMT');
	header('Content-type: application/json');
}


/**
 *  Sprawdza czy istnieje plik blokujacy uzycie aplikacji
 */
function checkApplicationState(){
	if (file_exists(FILE_APP_LOCK)) {
		if (strpos(file_get_contents(FILE_APP_LOCK), 'upgrade') !== false) {
			header("Content-type: text/html; charset=utf-8");
			echo "<center><div style='margin-top:80px;'><img src='public/images/docflow_logo.png' alt='Logo Docflow'>";
			echo "<h1>Przerwa techniczna.</h1><br />Proszę spróbować za 10 minut.<br />Proszę nie wyłączać i nie restartować serwera.<br />";
			echo "</div><br /><img alt='W trakcie przetwarzania' align='top' src='public/images/ajax/ajaxLoadingSmall.gif' /></center>";
			die;
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
function MK_isCLIExecution($executeCmd=false, array $argv=array()){
	if(defined("STDIN")){
		if($executeCmd === true){
			MK_executeCLICommand($argv);
		}
		return  true;
	}
	return false;
}


/**
 * Funkcja tworzy e-mail z treścią błędu bazy danych i wysyła w przypadku wyłączonego DEVELOPER-a
 */
function Mk_GetDBErrors($debugMsg, $file="", $line=0) {
	$subject = 'ERROR '.DB_NAME.' (DB)'.(isset($_SERVER['HTTP_HOST'])?' ['.$_SERVER['HTTP_HOST'].']':'');
	$email = MK_ErrorMail('Database', $file, $line)."\n"
			. '<table width="100%" border="1">'
			. '<tr><td colspan="2"><strong>Informacje:</strong></td></tr>'
			. '<tr><td colspan="2"><pre>' . $debugMsg . '</pre></td></tr>'
			. '</table>';

		$headers = 'Content-type: text/html; charset=utf-8' . "\n"
			. 'MIME-Version: 1.0' . "\n"
			. 'X-Mailer: PHP' . "\n"
			. 'From: '.SQL_ERROR_EMAIL_ADDRESS. "\n";

	if(DEVELOPER !== true) {
		mail(SQL_ERROR_EMAIL_ADDRESS, $subject, $email, $headers);
	}
}

/**
 * Sprawdza czy w aplikacji jest włączone "debugowanie"
 * 
 * @return Boolean 
 */
function MK_isDebugEnabled(){
	return (DEVELOPER === true || (array_key_exists('APP_DEBUG', $_SESSION) && $_SESSION['APP_DEBUG'] === true));
}



/**
 * Uruchamia kontroler konsoli i wywołuje z niego metode z parametrami
 *
 * @param Array		$argv - (default:array())Tablica z parametrami przekazanymi w lini polecen
 * @return Boolean
 */
function MK_executeCLICommand(array $argv=array()){
	$consoleController = new ConsoleController();
	$opts = getopt('m::');

	if(count($argv)>2){
		$argv = array_slice($argv, 2);
	}

	if(!empty($opts)){
		foreach (array_keys($opts) as $opt){
			switch ($opt) {
				case 'm':	$consoleController->{$opts['m']}($argv);
							break;
			}
		}
	}
}











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
 */
function MK_ErrorHandler($type, $message="", $file="", $line="", array $errcontext=array()) {
	$subject = 'ERROR '.DB_NAME.' (PHP)'.(isset($_SERVER['HTTP_HOST'])?' ['.$_SERVER['HTTP_HOST'].']':'');
	// W przypadku tego błędu nie logujemy ponieważ nie ma on się pojawiać
	if( $type == 2 && preg_match('#pg_fetch_array\(\) \[[^]]+\]: Unable to jump to row [0-9]+ on PostgreSQL result index [0-9]+#i', $message) ){
		return true;
	}

	$email = MK_ErrorMail($type, $file, $line)."\n"
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
			. 'From: '.PHP_ERROR_EMAIL_ADDRESS. "\n";

	if(DEVELOPER === true){
		require_once MK_PATH . DIRECTORY_SEPARATOR . 'Exception.php';
		throw new MK_Exception($email);
	}
	else {
		//error_log($email, 1, PHP_ERROR_EMAIL_ADDRESS, $headers);
		mail(PHP_ERROR_EMAIL_ADDRESS, $subject, $email ,$headers);
	}

	// Tutaj zwracamy informacje dla uzytkownika - w tym przypadku wyrzucamy wyjatek ktory zwróci jsona z informacja o obedzie ktora zostanie wyswietlana uzytkownikowi w postaci okna z błędem
	if ( ($type !== E_NOTICE) && ($type < 2048) ) {
		throw new MK_Exception('Błąd! Prosze spróbować jeszcze raz.');
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
function MK_ErrorMail($type, $file='null', $line='null'){

	$userLogin = 'Brak informacji';
	$userId = 'Brak informacji';
		
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
		. '<tr><td><strong>Błąd typu:</strong></td><td>('.$type.')</td></tr>'
		. '<tr><td><strong>Linia:</strong></td><td>'.$line.'</td></tr>'
		. '<tr><td><strong>Plik:</strong></td><td>'.$file.'</td></tr>';

	if(isset($_SERVER["HTTP_HOST"])) {
		$email .= '<tr><td><strong>Host:</strong></td><td>'.$_SERVER["HTTP_HOST"].'</td></tr>';
	}

	$email .= '<tr><td><strong>Baza danych:</strong></td><td>'.DB_NAME.'</td></tr>'
		. '<tr><td><strong>Login użytkownika:</strong></td><td>'.$userLogin.'</td></tr>'
		. '<tr><td><strong>ID użytkownika:</strong></td><td>'.$userId.'</td></tr>'
		. '<tr><td><strong>Data wystąpienia:</strong></td><td>'.$date.'</td></tr>';

	if(isset($_SERVER["REQUEST_URI"])) {
		$email .= '<tr><td><strong>REQUEST_URI:</strong></td><td>'.$_SERVER["REQUEST_URI"].'</td></tr>';
	}
	if(isset($_SERVER["HTTP_USER_AGENT"])) {
		$email .= '<tr><td><strong>HTTP_USER_AGENT:</strong></td><td>'.$_SERVER["HTTP_USER_AGENT"].'</td></tr>';
	}
	if(isset($_SERVER["REMOTE_ADDR"])) {
		$email .= '<tr><td><strong>REMOTE_ADDR:</strong></td><td>'.$_SERVER["REMOTE_ADDR"].'</td></tr>';
	}

	$email .= '</table>';

	return $email;
}


/**
 * Funkcja tworzy email z trescia bledu JavaScriptowego i wysyła maila w przypadku wyłączonego  'DEVELOPERA'
 */
function MK_GetJSErrors(){
	$subject = 'ERROR '.DB_NAME.' (JS)'.(isset($_SERVER['HTTP_HOST'])?' ['.$_SERVER['HTTP_HOST'].']':'');
	if( isset($_COOKIE['ys-javascriptErrorLog']) ) {
		$errorObject = json_decode(substr($_COOKIE['ys-javascriptErrorLog'],2));
		setcookie('ys-javascriptErrorLog', '', time()-24*3600, COOKIES_PATH);

		$email = MK_ErrorMail('JavaScript') . "\n"
			. '<table width="100%" border="1">'
			. '<tr><td colspan="2"><strong>Informacje:</strong></td></tr>'
			. '<tr><td colspan="2"><pre>' . print_r($errorObject, true) . '</pre></td></tr>'
			. '</table>';

		$headers = 'Content-type: text/html; charset=utf-8' . "\n"
			. 'MIME-Version: 1.0' . "\n"
			. 'X-Mailer: PHP' . "\n"
			. 'From: '.PHP_ERROR_EMAIL_ADDRESS. "\n";

		if( DEVELOPER !== true){
			mail(PHP_ERROR_EMAIL_ADDRESS, $subject, $email, $headers);
		}

	}
}


/**
 * Funkcja wywoływana na zakończenie skryptu PHP
 * w przypadku gdy skrypt kończy się błędem uruchamia funkcje powiadamiajaca o błędzie
 */
function MK_ShutdownFunction(){
	$error = error_get_last();
	if( !empty($error) ) {
		MK_ErrorHandler(
			isset($error['type']) ? $error['type'] : null,
			isset($error['message']) ? $error['message'] : null,
			isset($error['file']) ? $error['file'] : null,
			isset($error['line']) ? $error['line'] : null
		);
	}
}

