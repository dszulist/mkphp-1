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
     * Konstruktor sprawdza czy są podane wymagane dane w _GET i uruchamia mechanizm generowania wsdl'a
     *
     * @param bool $strategy
     * @param null $uri
     * @param null $wsdlClass
     */
    public function __construct($strategy=null, $uri=null, $wsdlClass=null){

        if(!isset($_GET['wsdl'])){
            throw new MK_Exception("MK Webservice");
        }
        if(!isset($_GET['instance'])){
            throw new MK_Exception("Brak podanej instancjii serwera");
        }

        if($strategy === null){
            $strategy = 'Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence';
        }

        try {
            parent::__construct($strategy, $uri, $wsdlClass);
            $this->setClass($_GET['instance']);
            $this->handle();
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

}
