<?php

require_once ('defines.php');
require_once (MK_PATH . DIRECTORY_SEPARATOR . 'MK.php');

spl_autoload_register('MK::_autoload');

MK::checkApplicationState();

// wylaczenie cachowania wsdl'a
ini_set("soap.wsdl_cache_enabled", WSDL_CACHE_ENABLE);

// ustawienie strefy czasowej
date_default_timezone_set(MK_TIMEZONE);

// do polskich nazw dat w kalendarzu
setlocale(LC_TIME, MK_LOCALE_TIME);

// do "." w liczbach, a nie ","
setlocale(LC_NUMERIC, MK_LOCALE_NUMERIC);

// rejestracja wrapperów
stream_wrapper_register("tcp", "MK_Stream_Tcp");

// #Debuging
if (MK_DEBUG_FIREPHP) {
	require (DIR_LIBS . 'FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');
	require (DIR_LIBS . 'FirePHPCore' . DIRECTORY_SEPARATOR . 'fb.php');
	//@TODO sprawdzic ten klucz sesji i obsłużyć
	$_SESSION['sql_last_time'] = microtime(true);
}

// #ErrorHandling
error_reporting(MK_DEVELOPER || MK_IS_CLI ? (E_ALL | E_STRICT) : '');
ini_set('display_errors', MK_DEVELOPER || MK_IS_CLI ? 'on' : 'off');

if (MK_IS_CLI === true) {
	set_time_limit(0);
	//resetowanie maski uprawnien
	umask(0);
} else {
	// Ustawiamy własną funkcję do obsługi błędów, jeżeli nie wywołujemy aplikacji z konsoli
	set_error_handler('MK_Error::handler');
	register_shutdown_function('MK::shutdownFunction');
}

if (MK_ERROR_JS_ENABLED) {
	MK_Error::fromJavaScript();
}

// #SessionHandling
ini_set('session.entropy_length', 16);
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.hash_function', 1);
ini_set('session.hash_bits_per_character', 6);
ini_set('session.save_handler', SESSION_SAVE_HANDLER);

session_save_path(DIR_SESSION);
session_set_cookie_params(0, MK_COOKIES_PATH);

// Uruchomienie kontrollera konsoli jezeli wywołanie jest z konsoli
if (MK_IS_CLI) {    
    MK::executeCLICommand($argv);
}