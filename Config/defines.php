<?php

define('DEVELOPER',					true);
define('MK_PATH',					realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR));

define('DEFAULT_LANG',				'pl');
define('LANG',						'pl');
define('LOCALE_TIME',				'pl_PL.UTF-8');
define('LOCALE_NUMERIC',			'en_US');
define('TIMEZONE',					'Europe/Warsaw');
define('HEADER',					'Content-Type: text/html; charset=utf-8');
define('PHP_ERROR_EMAIL_ADDRESS',	'error@madkom.pl');
define('SQL_ERROR_EMAIL_ADDRESS',	'error@madkom.pl');
define('COOKIES_PATH',				dirname(((isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : 'localhost').'?'));

//	Ścieżki do plików wykorzystywanych przez aplikacjie
define('FILE_APP_LOCK',				'under_construction.txt');
define('FILE_MTM',					'/var/lib/mtm/task.list');
define('FILE_MTM_LOCK',				'/tmp/mtm_task.lock');

//	Sciezki do aplikacji zewnetrznych 
define('EXEC_MINIFY',				'/opt/yuicompressor-2.4.6/build/yuicompressor-2.4.6.jar');

//	Domyślna konfiguracja systemu
define('DEFAULT_LIMIT',				40);
define('DEFAULT_START',				0);
define('DEFAULT_SORT_DIRECTION',	'ASC');
define('DEFAULT_SORT_COLUMN',		null);

// cachowanie wsdl'i
define('WSDL_CACHE_ENABLE',			false);
