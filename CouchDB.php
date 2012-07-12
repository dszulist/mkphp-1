<?php

/**
 * @todo opisy
 */
class MK_CouchDB
{
	private $host = 'localhost';
	private $port = '5984';
	private $user;
	private $pass;
	private $headers;
	private $body;

	/**
	 * Nazwa aktualnej bazy danych
	 * @var string
	 */
	private $db;

	/**
	 * @param $options
	 */
	public function __construct($options)
	{
		foreach ($options AS $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * @param $method
	 * @param $url
	 * @param null $post_data
	 * @return bool
	 */
	private function send($method, $url, $post_data = NULL)
	{
		$s = fsockopen($this->host, $this->port, $errno, $errstr);
		if (!$s) {
			echo "$errno: $errstr\n";
			return false;
		}

		$request = "$method $url HTTP/1.0\r\nHost: $this->host\r\n";

		if ($this->user) {
			$request .= "Authorization: Basic " . base64_encode("$this->user:$this->pass") . "\r\n";
		}

		if ($post_data) {
			$request .= "Content-Length: " . strlen($post_data) . "\r\n\r\n";
			$request .= "$post_data\r\n";
		}
		else {
			$request .= "\r\n";
		}

		fwrite($s, $request);
		$response = "";

		while (!feof($s)) {
			$response .= fgets($s);
		}

		list($this->headers, $this->body) = explode("\r\n\r\n", $response);
		return $this->body;
	}

	/**
	 * @param $resp
	 * @return bool
	 */
	private function getResponse($resp){
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
		return $this->getResponse($this->send("PUT", "/$name"));
	}

	/**
	 * Ustawia nazwe bazy danych na której ma operować
	 * @param $dbName
	 * @return MK_CouchDB
	 */
	public function setDb($dbName){
		$this->db = $dbName;
		return $this;
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
	 * @return mixed
	 * @throws MK_CouchDB_Exception
	 */
	public function getAllDocs(){
		if(empty($this->db)){
			throw new MK_CouchDB_Exception('Nie ustawiono bazy danych');
		}
		return $this->send("GET", "/{$this->db}/_all_docs");
	}

	/**
	 * @param array $data
	 * @param string $idKey
	 *
	 * @throws MK_CouchDB_Exception
	 */
	public function add(array $data, $idKey='id'){

		if(empty($data[$idKey])){
			throw new MK_CouchDB_Exception('Nie podano id');
		}

		$id = $data[$idKey];
		unset($data[$idKey]);
		$data["_$idKey"] = $id;

		$this->send("PUT", "/{$this->db}/$id", json_encode($data));
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function get($id){
		return json_decode($this->send('GET', "/{$this->db}/{$id}"));
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function delete($id){
		return $this->getResponse($this->send('DELETE', "/{$this->db}/{$id}"));
	}

	/**
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
