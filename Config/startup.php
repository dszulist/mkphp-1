<?php

require_once ('defines.php');
require_once (MK_PATH . DIRECTORY_SEPARATOR . 'CoreFunctions.php');

// wylaczenie cachowania wsdl'a
ini_set("soap.wsdl_cache_enabled", WSDL_CACHE_ENABLE);
// ustawienie strefy czasowej
date_default_timezone_set(TIMEZONE);
// do polskich nazw dat w kalendarzu
setlocale(LC_TIME, LOCALE_TIME);
// do "." w liczbach, a nie ","
setlocale(LC_NUMERIC, LOCALE_NUMERIC);


// rejestracja wrapperów
stream_wrapper_register("tcp", "MK_Stream_Tcp");

if (MK_isDebugEnabled()) {
	require ('libs/FirePHPCore/FirePHP.class.php');
	require ('libs/FirePHPCore/fb.php');
	$_SESSION['sql_last_time'] = microtime(true);
}

$isCLI = MK_isCLIExecution(true, empty($argv)?array():$argv);

error_reporting (DEVELOPER || $isCLI ? (E_ALL | E_STRICT) : '');
ini_set('display_errors', DEVELOPER || $isCLI ? 'on' : 'off');

if($isCLI === true){
	set_time_limit(0);
	//resetowanie maski uprawnien
	umask(0);
}
else {
	// Ustawiamy własną funkcję do obsługi błędów, jeżeli nie wywołujemy aplikacji z konsoli
	set_error_handler('MK_ErrorHandler');
	register_shutdown_function('MK_ShutdownFunction');
}

