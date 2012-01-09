<?php

/**
 * MK_Soap_AutoDiscover
 *
 * Klasa do automatycznego genewowania wsdl'i na podstawie klas PHP
 *
 * @category	MK_Soap
 * @package		MK_Soap_AutoDiscover
 * @author		bskrzypkowiak
 */
Class MK_Soap_AutoDiscover extends  Zend_Soap_AutoDiscover {

    /**
     * Nazwa instancji webservice
     * @var String
     */
    private $_serviceInstance;

    /**
     * Konstruktor sprawdza czy są podane wymagane dane w _GET i uruchamia mechanizm generowania wsdl'a
     *
     * @param String $strategy
     * @param Null $uri
     * @param Null $wsdlClass
     */
    public function __construct($strategy='Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence', $uri=null, $wsdlClass=null){

        if(!isset($_GET['instance'])){
            throw new MK_Exception("Brak podanej instancjii serwera");
        }

        $this->setInstanceName();

        if(!isset($_GET['wsdl'])){
            $this->createServer();
        }

        parent::__construct($strategy, $uri, $wsdlClass);

        $this->setClass('SynchronizationService');

        $this->handle();
    }

    /**
     * Zwraca uri zawierajacego instancje oraz proźbę o wsdl'a
     *
     * @param Bool $wsdl
     * @return String
     */
    public function getUri($wsdl=false){
        return parent::getUri() . (($wsdl) ? "?wsdl&instance=" . $this->_serviceInstance : '');
    }

    /**
     * Ustawiamy rodzaj webservice
     *
     * @param String|null $instance
     */
    public function setInstanceName($instance=null) {
        if($instance===null && !empty($_GET['instance'])){
            $instance = $_GET['instance'];
        }
        $this->_serviceInstance = $instance;
    }

    /**
     * Tworzy instancje i stara się obsłużyć rządanie
     */
    private function createServer(){
        $soap = new Zend_Soap_Server($this->getUri(true));
        $soap->setClass($this->_serviceInstance);
        $soap->handle();
    }
}
