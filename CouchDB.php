<?php

/**
 * MK_Stream_CouchDB
 *
 * Klasa do obsłgi couchdb
 *
 * @category MK
 * @package MK_CouchDB
 */
class MK_CouchDB
{
	/**
	 * Ip/nazwa serwera bazy danych
	 * @var string
	 */
	private $host = 'localhost';

	/**
	 * Port serwera bazy dandych
	 * @var int
	 */
	private $port = 5984;

	/**
	 * Nazwa użytkownika bazy
	 * @var string
	 */
	private $user;

	/**
	 * Hasło użytkownika
	 * @var string
	 */
	private $pass;

	/**
	 * Nazwa aktualnej bazy danych
	 * @var string
	 */
	private $dbname;

	/**
	 * Ustawia parametry połączenia
	 *
	 * @param array|string $options
	 *
	 * @throws MK_CouchDB_Exception
	 */
	public function __construct($options)
	{
		stream_wrapper_register("couchdb", "MK_Stream_CouchDB");
		if(is_string($options)){
			$options = parse_url($options);
			$options['dbname'] = $options['path'];
		    unset($options['scheme']);
		    unset($options['path']);
		}

		if(is_array($options)){
			foreach ($options AS $key => $value) {
				$this->$key = $value;
			}
		}

		if(empty($this->dbname)){
			throw new MK_CouchDB_Exception('Nie ustawiono bazy danych.');
		}
	}

	/**
	 * Otwiera połączenie wysyła zapytanie i zwraca odpowiedź
	 *
	 * @param string $method PUT/GET/DELETE
	 * @param string $url
	 * @param string\null $postData
	 * @return mixed
	 */
	private function send($method, $url, $postData = NULL)
	{
		$s = fsockopen($this->host, $this->port, $errno, $errstr);
		if (!$s) {
			echo "$errno: $errstr\n";
			return false;
		}

		$request = "$method $url HTTP/1.0\r\nHost: $this->host\r\n";

		if ($this->user && $this->pass) {
			$request .= "Authorization: Basic " . base64_encode("$this->user:$this->pass") . "\r\n";
		}

		if ($postData) {
			$request .= "Content-Length: " . strlen($postData) . "\r\n\r\n";
			$request .= "$postData\r\n";
		}
		else {
			$request .= "\r\n";
		}

		fwrite($s, $request);
		$response = "";

		while (!feof($s)) {
			$response .= fgets($s);
		}

		list($headers, $body) = explode("\r\n\r\n", $response);
		return $body;
	}

	/**
	 * Sprawdza czy odpowiedz jest pozytywna
	 * @param string $resp JSON z odpowiedzią z serwera
	 * @return bool
	 */
	private function isResponseOk($resp){
		$resp = json_decode($resp, true);
		return (isset($resp['ok']) && $resp['ok'] == 'true');
	}

	/**
	 * Tworzy baze danych o podanej nazwie
	 * w przypadku powodzenia zwraca true
	 * w przypadku niepowodzenia false
	 *
	 * @param string $name nazwa bazy do stworzenia
	 * @return bool
	 */
	public function createDB($name){
		return $this->isResponseOk($this->send("PUT", "/$name"));
	}

	/**
	 * Zwraca informacje o serwerze w postaci tablicy
	 * @return mixed
	 */
	public function getServerInfo(){
		return json_decode($this->send("GET", "/"));
	}

	/**
	 * Zwraca liste baz na serwerze w postaci tablicy
	 * @return mixed
	 */
	public function getDbList(){
		return json_decode($this->send("GET", "/_all_dbs"));
	}

	/**
	 * Zwraca wszystkie rekordy z bazy
	 * @return mixed
	 * @throws MK_CouchDB_Exception
	 */
	public function getAllDocs(){
		return $this->send("GET", "/{$this->dbname}/_all_docs");
	}

	/**
	 * Dodaje wpis do bazy
	 * @param array $data tablica z informacjami do wprowadzenia
	 * @param string $idKey klucz w tablicy zawierający identyfikator dla bazy
	 *
	 * @throws MK_CouchDB_Exception
	 */
	public function add(array $data, $idKey='id'){

		if(empty($data[$idKey])){
			throw new MK_CouchDB_Exception('Nie podano identyfikatora wpisu');
		}

		$data["_$idKey"] = $data[$idKey];
		unset($data[$idKey]);

		$this->send("PUT", "/{$this->dbname}/{$data["_$idKey"]}", json_encode($data));
	}

	/**
	 * Zwraca rekord o podanym id
	 * @param string $id
	 * @return mixed
	 */
	public function get($id){
		return json_decode($this->send('GET', "/{$this->dbname}/{$id}"));
	}

	/**
	 * Usuwa rekord o podanym id
	 * @param string $id
	 * @return mixed
	 */
	public function delete($id){
		return $this->isResponseOk($this->send('DELETE', "/{$this->dbname}/{$id}"));
	}

	/**
	 * @todo funkcja do wyszukiwania
	 *
	 * @return bool
	 */
	public function find(){
		// if we want to find only pastebin items that are currently published, we need to do a little more.
		// below, we create a view using a javascript function passed in the post data.
$map = <<<MAP
function(doc) {
    if(doc.name != 'undefined') {
        emit(doc.title, {docTitle: doc.title, docBody: doc.body});
    }
}
MAP;

		// we set the method to POST and send the request to couch db's /_temp_view. the text of the view is passed as post data.
		// this javascript function will return documents whose 'status' field contains 'published'.
		// note that we set the content type to 'text/javascript' for posts in our couchdb class.
		return $this->send('/_temp_view', 'post', "{\"map\":\"$map\"}");
	}

}
