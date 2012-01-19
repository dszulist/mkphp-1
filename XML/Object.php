<?php
/**
 * MK_XML_Object
 *
 * Klasa do obsłgi tcp jako plików
 * sciągnięte z : http://www.akchauhan.com/php-class-for-converting-xml-to-object-and-object-to-xml/
 * (oryginalny przykład został zmodyfikowany)
 *
 * @category MK_XML
 * @package	MK_XML_Object
 * @author	bskrzypkowiak
 */
class MK_XML_Object {

    /**
     * @var \XmlWriter
     */
    public $xml;

    /**
     * Konstruktor
     */
	public function __construct() {
		$this->xml = new XmlWriter();
		$this->xml->openMemory();
		$this->xml->startDocument('1.0');
		$this->xml->setIndent(true);
	}

    /**
     * Method to convert Object into XML string
     *
     * @param stdClass $obj
     * @return string
     */
	public function objToXML($obj) {
		$this->getObject2XML($this->xml, $obj);

		$this->xml->endElement();

		return $this->xml->outputMemory(true);
	}

	/**
     * Method to convert XML string into Object
     *
     * @param String $xmlString
     * @return SimpleXMLElement
     */
	public function xmlToObj($xmlString) {
//        var_dump($xmlString);
        //$obj->castClass((Object)array(
        //        'name' => 'XYZ',
        //        'age' => '28',
        //        'gender'=>'Male'
        //    ), 'Bogi'),
        $xmlObj = simplexml_load_string($xmlString);

        var_dump($xmlObj);

        $dataArray = array();
        foreach ($xmlObj as $sectionName => $sectionData) {
            var_dump($sectionData);
            echo  $sectionName." => ". $sectionData.PHP_EOL;
//            $xmlObj->$sectionName = ('bogi')$sectionData;
            //$dataArray[$sectionName] = $this->_processExtends($config, $sectionName);
        }

		//return $test;
	}

    /**
     * Przetwarza obiekt na XML
     *
     * @param XMLWriter $xml
     * @param array $data
     */
	private function getObject2XML(XMLWriter $xml, $data) {
		foreach($data as $key => $value) {
			if(is_object($value)) {
				$xml->startElement($key);
				$this->getObject2XML($xml, $value);
				$xml->endElement();
				continue;
			}
			else if(is_array($value)) {
				$this->getArray2XML($xml, $key, $value);
			}

			if (is_string($value)) {
				$xml->writeElement($key, $value);
			}
		}
	}

    /**
     * Przetwarza tablice na XML
     *
     * @param XMLWriter $xml
     * @param $keyParent
     * @param $data
     */
	private function getArray2XML(XMLWriter $xml, $keyParent, $data) {
		foreach($data as $key => $value) {
			if (is_string($value)) {
				$xml->writeElement($keyParent, $value);
				continue;
			}

			if (is_numeric($key)) {
				$xml->startElement($keyParent);
			}

			if(is_object($value)) {
				$this->getObject2XML($xml, $value);
			}
			else if(is_array($value)) {
				$this->getArray2XML($xml, $key, $value);
				continue;
			}

			if (is_numeric($key)) {
				$xml->endElement();
			}
		}
	}


    function castClass($object, $newclass)
    {
        if( !is_object($object) )
        {
            trigger_error('cast_class expects parameter 1 to be object, ' . gettype($object) . ' given', E_USER_WARNING);
            return false;
        }
        if( !class_exists($newclass) )
        {
            // We'll save unserialize the work of triggering an error if the class does not exist
            trigger_error('Class ' . $newclass . ' not found', E_USER_ERROR);

            return false;
        }
        $serialized_parts = explode(':', serialize($object));

        $serialized_parts[1] = strlen($newclass);
        $serialized_parts[2] = '"' . $newclass . '"';



        return unserialize(implode(':', $serialized_parts));
    }


    function parseXMLtoObject($xml) {
        $obj = new stdClass();

        $xml = explode("\n",$xml);

        $main_n = '';

        foreach ($xml as $x) {
            $first_n = false;
            $close_n = false;
            if ($x != '') {
                $start_val = (strpos($x,">")+1);
                $end_val = strrpos($x,"<") - $start_val;
                $start_n = (strpos($x,"<")+1);
                $end_n = strpos($x,">") - $start_n;
                $n = strtolower(substr($x,$start_n,$end_n));
                if (substr_count($x,"<") == 1) {
                    if (!empty($main_n) && !stristr($n,"/")) {
                        $submain_n = $n;
                        $first_n = true;
                    } else {
                        $main_n = $n;
                        $submain_n = '';
                        $first_n = true;
                    }
                }
                if (!empty($submain_n) && stristr($submain_n,"/")) {
                    $submain_n = '';
                    $first_n = false;
                    $close_n = true;
                }
                if (!empty($main_n) && stristr($main_n,"/")) {
                    $main_n = '';
                    $submain_n = '';
                    $first_n = false;
                    $close_n = true;
                }
                $value = substr($x,$start_val,$end_val);
                if (!$close_n) {
                    if (empty($main_n)) {
                        $obj->$n = $value;
                    } else {
                        if ($first_n) {
                            if (empty($submain_n)) {
                                $obj->$main_n = new stdClass();
                            } else {
                                $obj->$main_n->$submain_n = new stdClass();
                            }
                        } else {
                            if (!empty($value)) {
                                if (empty($submain_n)) {
                                    $obj->$main_n->$n = $value;
                                } else {
                                    $obj->$main_n->$submain_n->$n = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $obj;
    }
}

