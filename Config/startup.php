<?php
// Ustawienie uprawnień użytkownika/grupy "www-data"
$posixInfo = posix_getpwnam('www-data');
if ($posixInfo !== false) {
	posix_setgid($posixInfo['gid']);
	posix_setuid($posixInfo['uid']);
}

require_once ('defines.php');
require_once (MK_PATH . DIRECTORY_SEPARATOR . 'functions.php');
require_once (MK_PATH . DIRECTORY_SEPARATOR . 'MK.php');

validate_directory(MK_DIR_TEMP);
if (SESSION_SAVE_HANDLER == 'files') {
	validate_directory(MK_DIR_SESSION);
	block_directory_htaccess(MK_DIR_SESSION);
}

spl_autoload_register('MK::_autoload');

use_soap_error_handler(MK_DEBUG);

MK::checkApplicationState();

// wylaczenie cachowania wsdl'a
ini_set("soap.wsdl_cache_enabled", WSDL_CACHE_ENABLE);

// ustawienie strefy czasowej
date_default_timezone_set(MK_TIMEZONE);

// do polskich nazw dat w kalendarzu
setlocale(LC_TIME, MK_LOCALE_TIME);

// do "." w liczbach, a nie ","
setlocale(LC_NUMERIC, MK_LOCALE_NUMERIC);

if (MK_DEBUG || MK_IS_CLI) {
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 'on');
	set_time_limit(0);
	umask(0); // Resetowanie maski uprawnien
} else {
	// #ErrorHandling
	error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
	ini_set('display_errors', 'off');
	// Ustawiamy własną funkcję do obsługi błędów, jeżeli nie wywołujemy aplikacji z konsoli
	set_error_handler('MK_Error::handler', error_reporting());
	register_shutdown_function('MK::shutdownFunction');
}

if (MK_ERROR_JS_ENABLED) {
	MK_Error::fromJavaScript();
}

// Nadpisanie php.ini
ini_set("memory_limit", "512M");
ini_set("max_execution_time", "600");
ini_set("default_socket_timeout", "600");

// #SessionHandling
ini_set('session.entropy_length', 16);
ini_set('session.entropy_file', '/dev/urandom');
ini_set('session.hash_function', 1);
ini_set('session.hash_bits_per_character', 6);
ini_set('session.save_handler', SESSION_SAVE_HANDLER);
ini_set('session.gc_maxlifetime', 0);
ini_set('session.gc_probability', 1);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cache_expire', 480);

session_save_path(MK_DIR_SESSION);
session_set_cookie_params(0, MK_COOKIES_PATH);

// rejestracja wrapperów
if (SESSION_SAVE_HANDLER == 'memcache') {
	stream_wrapper_register("tcp", "MK_Stream_Tcp");
}

//myk na swfUpload który sessid podaje w gecie
if (!empty($_GET['PHPSESSID'])) {
	session_id($_GET['PHPSESSID']);
	$_COOKIE[session_name()] = $_GET['PHPSESSID'];
}

// Uruchomienie sesji
session_start();

// Debuging - wymagany dodatek do Firebuga (http://developercompanion.com/)
define('MK_DEBUG_FIREPHP', (isset($_SESSION['DEBUG_FIREPHP']) && !MK_IS_CLI));
if (MK_DEBUG_FIREPHP) {
	require_once (MK_DIR_VENDORS . DIRECTORY_SEPARATOR . 'FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');
	require_once (MK_DIR_VENDORS . DIRECTORY_SEPARATOR . 'FirePHPCore' . DIRECTORY_SEPARATOR . 'fb.php');
}

// Uruchomienie kontrollera konsoli jezeli wywołanie jest z konsoli
if (MK_IS_CLI) {
	MK::executeCLICommand($argv);
}
