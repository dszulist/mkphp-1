<?php
/**
 * MK_XML_Convert
 *
 * Obsługa plików xml :
 *  - Xml ->  Object
 *  - Object  ->  XML
 *  - Pobieranie XML'a z wewnątrz innego XML'a
 *
 * @category MK_XML
 * @package	MK_XML_Convert
 * @author	bskrzypkowiak
 */
class MK_XML_Convert {

    /**
     * Ścieżka do pliku, lub ciag zawierający XML
     *
     * @var String
     */
    private $xmlSrc;

    /**
     * Przechowuje XML'a w przypadku próby prezentacji obiektu jako XML
     *
     * @var SimpleXMLElement
     */
    private  $xmlResult;

    /**
     * Konstruktor
     *
     * @param String|null $xmlSrc - scieżka do pliku xml albo ciąg zawierający XML
     */
    public function __construct($xmlSrc=null) {
        if($xmlSrc !== null){
            $this->xmlSrc = $xmlSrc;
        }
    }

    /**
     * Funkcja rekurencyjnie zbiera elementy obiektu i tworzy z niego XML
     *
     * @param stdClass $object
     * @param SimpleXMLElement $xml
     */
    private function iteratechildren($object, SimpleXMLElement $xml){
    	if(!empty($object)){
	        foreach ($object as $name => $value){
	            if (is_string($value) || is_numeric($value)) {
	               $xml->$name = $value;
	            }
	            else {
	               $xml->$name = null;
	               $this->iteratechildren($value, $xml->$name);
	            }
	        }
    	}
    }

	/**
	 * Tworzy obiekt XMLReader i ładuje do niego xml'a z pliku albo stringa w zależności co podano podczas tworzenia obiektu klasy
	 *
	 * @return XMLReader
	 */
	private function getXMLReader(){
		$xmlReader = new XMLReader();
		$method = (is_file($this->xmlSrc)) ? 'open' : 'XML';
		$xmlReader->$method($this->xmlSrc);

		return $xmlReader;
	}

    /**
     * Parsuje XML'a na Objekt
     *
     * @param XMLReader $xml
     * @return array|null|string
     */
    protected function xml2obj(XMLReader $xml){
        $tree = null;

        while($xml->read()) {
            switch ($xml->nodeType) {
                case XMLReader::END_ELEMENT:
                    //var_dump($tree);
                    return $tree;
                case XMLReader::ELEMENT:
                    if($xml->isEmptyElement) {
                        $tree[$xml->name] = $xml->value;
                    }
                    else {
                        $elem = $this->xml2obj($xml);
                        //przed mykiem
                        //$tree[$xml->name] = (is_string($elem) || is_numeric($elem)) ? $elem : new $xml->name($elem);

                        //UWAGA MYK!!!
                        //TODO: PRZEMYSLEC PROBLEM TWORZENIA KLAS DLA TYPOW OPISANYCH W XSD (nzawy zastrzezone np. return)
                        if((is_string($elem) || is_numeric($elem) || is_null($elem))){
                            $tree[$xml->name] = $elem;
                        }
                        else {
                            $className = $xml->name;
                            if ($className === 'return') {
                                $className = "backOfficeSyncResult";
                            }
                            if ($className === 'payments'){
                                $className = 'frontOfficeEPackagePayment';
                            }
                            $tmp = new $className($elem);
                            if(isset($tree[$xml->name])){
                                if (is_array($tree[$xml->name])){
                                    $tree[$xml->name][] = $tmp;
                                }
                                else {
                                    $tree[$xml->name] = array($tree[$xml->name], $tmp);

                                }
                            }
                            else {
                                $tree[$xml->name] = $tmp;
                            }

                        }


                        //KONIEC MYKU
                    }
                    break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    $tree .= $xml->value;
            }
        }
        return $tree;
    }

	/**
	 * Zwraca wewnętrzny XML
	 *
	 * @param string $name - nazwa elementu w którym znajduje się zagnieżdzony XML
	 * @return bool|string
	 */
	public function getInnerXML($name){
		$xmlReader = $this->getXMLReader();

		while ($xmlReader->read()) {
			switch ($xmlReader->nodeType) {
				case XMLReader::ELEMENT:
					if($xmlReader->name == $name){
						$xml = $xmlReader->readInnerXML();
						$xmlReader->close();
						return html_entity_decode($xml);
					}
				break;
			}
		}

		$xmlReader->close();
		return false;
	 }

    /**
     * Zwraca przekazany obiekt w formie XML
     *
     * @param stdClass $object
     * @param string $rootNode
     *
     * @return mixed
     */
    public function getAsXml($object, $rootNode='root'){
        $this->xmlResult = new SimpleXMLElement("<$rootNode></$rootNode>");
        $this->iteratechildren($object, $this->xmlResult);
        return $this->xmlResult->asXML();
    }

    /**
     * Zwraca XML'a w formie Obiektu,
     *
     * @param String $xml
     * @return mixed
     */
    public function getAsObject($xml=null){
        spl_autoload_register('MK_XML_Convert::autoloadProxyClass');

        $tmpXML = $this->xmlSrc;
        $this->xmlSrc = $xml;
        $xml = $this->getXMLReader();
        $res = $this->xml2obj($xml);
        $xml->close();
        $this->xmlSrc = $tmpXML;

        spl_autoload_unregister('MK_XML_Convert::autoloadProxyClass');

        return (object)$res;
    }

    /**
     * Autoload dla klas Proxy (jeżeli klasa nie istnieje zostanie utworzona dynamicznie)
     *
     * @final
     * @static
     * @param String $className
     * @return bool
     */
    final public static function autoloadProxyClass($className){
        eval("Final Class {$className} extends MK_XML_ProxyClassAbstract { }");
        return true;
    }

    /**
     * Zmienia typ obiektu na inny
     *
     * @static
     * @param stdClass $obj
     * @param string $toClass
     * @return bool|mixed
     */
    public static function cast($obj, $toClass) {
    	
		if(class_exists($toClass)) {
			$objArray = explode(":", serialize($obj));
			$objArray[1] = strlen($toClass);
			$objArray[2] = '"'.$toClass.'"';
			return unserialize(implode(':', $objArray));
		}
    	return false;
	}
    
    
}
