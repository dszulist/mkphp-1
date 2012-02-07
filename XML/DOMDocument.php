<?php
/**
 * MK_XML_DOMDocument
 *
 * Obsługa plików xml
 *
 * @category MK_XML
 * @package	MK_XML_DOMDocument
 * @author	bskrzypkowiak
 */
class MK_XML_DOMDocument extends DOMDocument {

    /**
     * Konstruktor
     *
     * @param String/null $xmlSrc - scieżka do pliku xml albo ciąg zawierający XML
     */
    public function __construct($xmlSrc=null) {
        if($xmlSrc === null){
            throw new Exception('Brak źródła XML');
        }
        $this->loadXML($xmlSrc);
    }

    /**
     * Zwraca liste dzieci danego noda w postaci tablicy
     *
     * @param DOMNode $node
     * @return array|bool
     */
    protected function getChildsAsArray(DOMNode $node){

        if($node->hasChildNodes()){
            $arr =  array();
            foreach($node->childNodes as $value){
                if($value->hasChildNodes()){
                    $arr[$value->nodeName] = $this->getChildsAsArray($value);
                }
                else {
                    if($value->nodeName == "#text") {
                        return $value->nodeValue;
                    }
                    $arr[$value->nodeName] = $value->nodeValue;
                }
            }
            return $arr;
        }
        return false;
    }

    /**
     * Zwraca liste atrybutow elementu, lub false jeżeli nie ma żadnego
     *
     * @param DOMNode $node
     * @return array|bool
     */
    protected function getElementAttributes(DOMNode $node){
        if($node->hasAttributes()){
            $arr = array();
            foreach ($node->attributes as $value){
                $arr[$value->nodeName] = $value->nodeValue;
            }
            return $arr;
        }
        return false;
    }



}

