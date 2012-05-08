<?php
// Ścieżka do biblioteki MK(php)
define('MK_PATH',		realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR));

// Ścieżki do katalogów wykorzystywanych przez bibliotekę
define('MK_DIR_TEMP',		defined('DIR_TEMP') ? DIR_TEMP : realpath(sys_get_temp_dir()));
define('MK_DIR_SESSION',	defined('DIR_SESSION') ? DIR_SESSION : realpath(session_save_path()));

// Konfiguracja startowa aplikacji
define('MK_LANG',			'pl');
define('MK_DEFAULT_LANG',	'pl');
define('MK_LOCALE_TIME',	'pl_PL.UTF-8');
define('MK_LOCALE_NUMERIC',	'en_US');
define('MK_TIMEZONE',		'Europe/Warsaw');
define('MK_HTML_HEADER',	'Content-Type: text/html; charset=utf-8');
define('MK_COOKIES_PATH',	dirname(((isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : 'localhost').'?'));
define('MK_IS_CLI',			defined('STDIN'));
define('MK_CHMOD_DIR',		0775);
define('MK_EOL',            (!empty($_SERVER['SERVER_SOFTWARE'])) ? '<br/>' : PHP_EOL);

// Konfigruacja zgłaszania i zapisywania błędów
define('MK_DEBUG',		        defined('APP_DEBUG') ? APP_DEBUG : false);
define('MK_DEVELOPER',			defined('DEVELOPER') ? DEVELOPER : false);
define('MK_ERROR_JS_ENABLED',	defined('APP_ERROR_JS_ENABLED') ? APP_ERROR_JS_ENABLED : true);

// Ścieżki do plików wykorzystywanych przez aplikację
define('APP_FILE_LOCK',		(defined('APP_PATH') ? APP_PATH . DIRECTORY_SEPARATOR : '') . 'under_construction.txt');
define('APP_STATUS_LOG',	(defined('APP_PATH') ? APP_PATH . DIRECTORY_SEPARATOR : '') . 'upgrade/log/status.log');
define('MTM_FILE_LIST',		'/var/lib/mtm/task.list');
define('MTM_FILE_LOG',		'/var/log/mtm/mtm.log');
define('MTM_FILE_LOCK',		'/tmp/mtm_task.lock');

// Ścieżki do aplikacji zewnętrznych
define('EXEC_MINIFY',		'/opt/yuicompressor-2.4.6/build/yuicompressor-2.4.6.jar');
define('EXEC_JAVA',         '/opt/java/bin/java');

// Domyślna konfiguracja systemu
define('DB_DEFAULT_LIMIT',			40);
define('DB_DEFAULT_START',			0);
define('DB_DEFAULT_SORT_DIRECTION',	'ASC');
define('DB_DEFAULT_SORT_COLUMN',	null);

// Cacheowanie wsdl'i
define('WSDL_CACHE_ENABLE',		false);

// Ilość miejsc po przecinku - wykorzystywane lokalnie poprzez bcscale()
define('MK_PRECISION_NUMBER',	2); // liczby
define('MK_PRECISION_INDEX',	4); // wskaźniki
define('MK_PRECISION_PERCENT',	2); // procenty

/**
 * Stałe wymagane, aby niektóre części MK(php) działały:
 *	define('SESSION_SAVE_HANDLER,	'files');		// Sesje zapisywane w plikach
 *	//define('SESSION_SAVE_HANDLER,	'memcache');	// Sesje zapisywane w pamięci
 *	define('DIR_SESSION',			APP_PATH.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'session');			// dla SESSION_SAVE_HANDLER = 'files'
 *	//define('DIR_SESSION',			'tcp://127.0.0.1:11211?persistent=1&weight=1&timeout=1&retry_interval=15');	// dla SESSION_SAVE_HANDLER = 'memcache'
 *	define('APP_NAME',		'');	// Nazwa aplikacji
 *	define('DB_HOST',		'');	// Baza danych: hostname
 *	define('DB_PORT',		'');	// Baza danych: port
 *	define('DB_USER',		'');	// Baza danych: użytkownik
 *	define('DB_PASS',		'');	// Baza danych: hasło
 *	define('DB_NAME',		'');	// Baza danych: nazwa
 *	define('DB_DEBUG',		'');	// Baza danych: Czy debugować zapytania SQL?
 *
 * Stałe pomocnicze:
 *	define('MK_DEBUG',			false);
 *	define('APP_ERROR_JS_ENABLED',	true);
 *
 */