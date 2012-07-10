<?php
/**
 * MK_XML_DOMDocument
 *
 * Obsługa plików xml
 *
 * @category MK_XML
 * @package    MK_XML_DOMDocument
 * @author    bskrzypkowiak
 */
class MK_XML_DOMDocument extends DOMDocument
{

	/**
	 * Konstruktor
	 *
	 * @param String/null $xmlSrc - scieżka do pliku xml albo ciąg zawierający XML
	 *
	 * @throws Exception
	 */
	public function __construct($xmlSrc = null)
	{
		if ($xmlSrc === null) {
			throw new Exception('Brak źródła XML');
		}
		$this->loadXML($xmlSrc);
	}

	/**
	 * Zwraca liste dzieci danego noda w postaci tablicy
	 *
	 * @param DOMNode $node
	 * @param bool $withAttributes - gdy ustawione na true zwraca w tablicy też atrybuty ich nazwy kluczy poprzedzone są znakiem: @
	 *
	 * @return array|bool|string
	 */
	protected function getChildsAsArray(DOMNode $node, $withAttributes = false)
	{

		if ($node->hasChildNodes() || ($withAttributes === true && $node->hasAttributes())) {

			$arr = array();

			if ($withAttributes === true && !is_null($node->attributes)) {
				foreach ($node->attributes as $attr) {
					$arr["@{$attr->name}"] = $attr->value;
				}
			}

			foreach ($node->childNodes as $value) {

				if ($value->hasChildNodes()) {
					$arr[$value->nodeName] = $this->getChildsAsArray($value, $withAttributes);
				}
				else {
					if ($value->nodeName == "#text") {
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
	 *
	 * @return array|bool
	 */
	protected function getElementAttributes(DOMNode $node)
	{
		if ($node->hasAttributes()) {
			$arr = array();
			foreach ($node->attributes as $value) {
				$arr[$value->nodeName] = $value->nodeValue;
			}
			return $arr;
		}
		return false;
	}


}

