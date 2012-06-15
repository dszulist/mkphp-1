<?php
// Trzeba dodać ścieżkę do Proxy Class, aby bez problemu załadować i zarządzać naszymi obiektami
set_include_path(get_include_path() .
	PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ProxyClass'
);