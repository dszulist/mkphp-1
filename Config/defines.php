<?php

/**
 * Stałe wymagane, aby niektóre części MK(php) działały:
 *
 * JEZELI APLIKACJA KORZYSTA Z PLIKÓW KONFIGURACYJNYCH INI - patrz koniec tego pliku!!!
 *
 *    define('SESSION_SAVE_HANDLER,    'files');        // Sesje zapisywane w plikach
 *    //define('SESSION_SAVE_HANDLER,    'memcache');    // Sesje zapisywane w pamięci
 *    define('DIR_SESSION',            APP_PATH.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.'session');            // dla SESSION_SAVE_HANDLER = 'files'
 *    //define('DIR_SESSION',            'tcp://127.0.0.1:11211?persistent=1&weight=1&timeout=1&retry_interval=15');    // dla SESSION_SAVE_HANDLER = 'memcache'
 *    define('APP_NAME',        '');    // Nazwa aplikacji
 *    define('DB_HOST',        '');    // Baza danych: hostname
 *    define('DB_PORT',        '');    // Baza danych: port
 *    define('DB_USER',        '');    // Baza danych: użytkownik
 *    define('DB_PASS',        '');    // Baza danych: hasło
 *    define('DB_NAME',        '');    // Baza danych: nazwa
 *    define('DB_DEBUG',        '');    // Baza danych: Czy debugować zapytania SQL?
 *
 * Stałe pomocnicze:
 *    define('MK_DEBUG',            false);
 *    define('APP_ERROR_JS_ENABLED',    true);
 *
 */

// Konfiguracja startowa aplikacji
define('MK_LANG', 'pl_PL');
define('MK_LOCALE_TIME', MK_LANG . '.utf8'); // polski format daty
define('MK_LOCALE_NUMERIC', 'POSIX'); // "." w cyfrach zamiast ","
define('MK_TIMEZONE', 'Europe/Warsaw');
define('MK_HTML_HEADER', 'Content-Type: text/html; charset=utf-8');
define('MK_COOKIES_PATH', dirname(((isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : 'localhost') . '?'));
define('MK_IS_CLI', defined('STDIN'));
define('MK_CHMOD_DIR', 0775);
define('MK_EOL', (!empty($_SERVER['SERVER_SOFTWARE'])) ? '<br/>' : PHP_EOL);

// Ścieżka do biblioteki MK(php)
define('MK_PATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR));

// Ścieżki do katalogów wykorzystywanych przez bibliotekę
define('MK_DIR_TEMP', defined('DIR_TEMP') ? DIR_TEMP : realpath(sys_get_temp_dir()));
define('MK_DIR_SESSION', defined('DIR_SESSION') ? DIR_SESSION : realpath(session_save_path()));
define('MK_DIR_UPDATE_LOGS', (defined('APP_PATH') ? APP_PATH . DIRECTORY_SEPARATOR : '') . 'upgrade' . DIRECTORY_SEPARATOR . 'log');
define('MK_DIR_VENDORS', MK_PATH . DIRECTORY_SEPARATOR . 'Vendors');

// Ścieżki do plików wykorzystywanych przez aplikację
define('APP_FILE_LOCK', (defined('APP_PATH') ? APP_PATH . DIRECTORY_SEPARATOR : '') . 'under_construction.txt');
define('APP_STATUS_LOG', (defined('APP_PATH') ? APP_PATH . DIRECTORY_SEPARATOR : '') . 'upgrade/log/status.log');
define('MTM_FILE_LIST', '/var/lib/mtm/task.list');
define('MTM_FILE_LOG', '/var/log/mtm/mtm.log');
define('MTM_FILE_LOCK', '/tmp/mtm_task.lock');

// Ścieżki do aplikacji zewnętrznych
define('EXEC_MINIFY', '/opt/yuicompressor-2.4.6/build/yuicompressor-2.4.6.jar');
define('EXEC_JAVA', '/opt/java/bin/java');

// Domyślna konfiguracja systemu
define('DB_DEFAULT_LIMIT', 40);
define('DB_DEFAULT_START', 0);
define('DB_DEFAULT_SORT_DIRECTION', 'ASC');
define('DB_DEFAULT_SORT_COLUMN', null);

// Cacheowanie wsdl'i
define('WSDL_CACHE_ENABLE', false);

// Ilość miejsc po przecinku - wykorzystywane lokalnie poprzez bcscale()
define('MK_PRECISION_NUMBER', 2); // wartość liczbowa
define('MK_PRECISION_INDEX', 4); // wskaźniki
define('MK_PRECISION_PERCENT', 2); // wartość procentowa
define('MK_PRECISION_FRACTION', 4); // wartość ułamkowa


// Jeżeli aplikacja ma konfiguracje w pliku ini i został wskazany plik ini w postaci stałej APP_INI_FILE to brakujace wymagane definesy są pobierane z tego pliku
if (defined('APP_INI_FILE')) {
	require_once (MK_PATH . DIRECTORY_SEPARATOR . 'Config.php');
	$config = new MK_Config(APP_INI_FILE);

	define('DB_HOST', $config->getString('database', 'host'));
	define('DB_PORT', $config->getString('database', 'port'));
	define('DB_USER', $config->getString('database', 'user'));
	define('DB_PASS', $config->getString('database', 'password'));
	define('DB_NAME', $config->getString('database', 'name'));

	define('SESSION_SAVE_HANDLER', $config->getString('system', 'session'));

	define('APP_DEBUG', $config->getString('system', 'debug'));
	define('DB_DEBUG', APP_DEBUG);
	define('APP_ERROR_JS_ENABLED', $config->getString('system', 'error_raporting_js'));
	define('APP_NAME', $config->getString('system', 'name'));
}

// Konfigruacja zgłaszania i zapisywania błędów
define('MK_DEBUG', defined('APP_DEBUG') ? APP_DEBUG : false);
define('MK_DEVELOPER', defined('DEVELOPER') ? DEVELOPER : false);
define('MK_TEST', defined('APP_TEST') ? APP_TEST : false);
define('MK_ERROR_JS_ENABLED', defined('APP_ERROR_JS_ENABLED') ? APP_ERROR_JS_ENABLED : true);
