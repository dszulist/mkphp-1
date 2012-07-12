<?php
class MK_Stream_Couchdb
{
	/**
	 * Uchwyt do bazy danych
	 * @var MK_CouchDB
	 */
	private $db;

	/**
	 * Utworzenie połączenia z bazą
	 *
	 * @param $path
	 * @param $mode
	 * @param $options
	 * @param $opened_path
	 * @return bool
	 */
    function stream_open($path, $mode, $options, &$opened_path)
    {
	    $url = parse_url($path);
	    $url['db'] = $url['path'];
	    unset($url['scheme']);
	    unset($url['path']);
        $this->db = new \MK_CouchDB($url);
	    return true;
    }

	/**
	 * @todo opisy
	 * @param $data
	 * @param null $lenght
	 * @return int
	 */
    function stream_write($data, $lenght=null)
    {
		$this->db->add(json_decode($data, true));
        return strlen($data);
    }

	/**
	 * @todo do zrobienia
	 * @param $path
	 */
    public function unlink($path)
    {
        $_url = parse_url($path);
        $_path = $_url['path'];
    }

	/**
	 * puki co nie widze potrzeby oprogramowywania tego, nie będziemy czytać streamem z couchdb :)
	 * @param null $count
	 * @return string
	 */
    function stream_read($count=null)
    {
	    return "not implemented Yet!";
    }

    public function __construct()
    {
    }

    public function dir_closedir()
    {
    }

    public function dir_opendir($path , $options)
    {
    }

    public function dir_readdir()
    {
    }

    public function dir_rewinddir()
    {
    }

    public function mkdir($path , $mode , $options)
    {
    }

    public function rename($path_from , $path_to)
    {
    }

    public function rmdir($path , $options)
    {
    }

    public function stream_cast($cast_as)
    {
    }

    public function stream_close()
    {
    }

    public function stream_eof()
    {
    }

    public function stream_flush()
    {
    }

    public function stream_lock($operation)
    {
    }

    public function stream_seek($offset , $whence = SEEK_SET)
    {
    }

    public function stream_set_option($option , $arg1 , $arg2)
    {
    }

    public function stream_stat()
    {
    }

    public function stream_tell()
    {
    }
}