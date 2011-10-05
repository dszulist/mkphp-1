<?php
/**
 * TcpStream
 *
 * Klasa do obs³gi tcp jako plików
 * pomaga przy sprawdzaniu file_exist etc.
 * na podstawie: http://www.php.net/manual/en/streamwrapper.url-stat.php
 *
 * @category	Mkphp
 * @package	TcpStream
 * @author	bskrzypkowiak
 */
Class TcpStream {

    /**
     * Sprawdzanie protoko³u
     * 
     * @param String $path
     * @param Integer $flags
     * @return Boolean
     */
    public static function url_stat($path, $flags) {

        if (!stream_socket_client($path, $errno, $errstr, 10)) {
            echo "Brak po³¹czenia: \r\n$path \r\n $errstr";
            return false;
        }

        return true;
    }

    /**
     * Przy próbie utworzenia pliku dla "tcp" nic nie twórz i zwróæ true
     * 
     * @param String $path
     * @param Integer $mode
     * @param Integer $options
     * @return Boolean 
     */
    public static function mkdir($path, $mode, $options) {
        return true;
    }

}