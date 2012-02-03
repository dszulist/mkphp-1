<?php

/**
 * MK_Crypt_KeyCert
 *
 * Klasa do obsługi certyfikatów
 *
 * @category	MK_Crypt
 * @package		MK_Crypt_KeyCert
 * @author		bskrzypkowiak
 */
Abstract Class MK_Crypt_KeyCert {

    /**
     * HSM/PCKS12
     * @var
     */
    protected $srcType;

    /**
     * Scieżka do pliku jar
     * @var #DMK_PATH|string
     */
    protected $jarFilePath = MK_PATH;

    /**
     * Konstruktor
     */
    public function __construct(){
        $this->jarFilePath .= DIRECTORY_SEPARATOR . 'Crypt' . DIRECTORY_SEPARATOR . 'KeyCert' . DIRECTORY_SEPARATOR . 'jar' . DIRECTORY_SEPARATOR . 'KeyCert.jar';
    }


    /**
     * Wyświetlanie listy certyfikatów z HSMA/Pliku w formacie JSON
     *    $ java -jar KeyCert.jar -load TYP -lslot NR -lkspass HASLO -listjson
     *
     * @param $slot
     * @param $kspass
     * @param bool $json
     * @return array|JsonString
     */
     public function getList($slot, $kspass, $json=false){

         exec(EXEC_JAVA . " -jar {$this->jarFilePath} -load {$this->srcType} -lslot {$slot} -lkspass {$kspass} -listjson", $output, $returnCode);

         if(array_key_exists(0, $output)){
             $output = $output[0];
         }

         return $json === false ? json_decode($output, true) : $output;
     }


    public function generate($name, $localization, $countryCode, $organization, $altName, $dateFrom, $dateTo, $slot, $skpass, $alias){
        /*
         $ java -jar KeyCert.jar -gen
            -CN "TEST user"
                -L Gdynia
                -C PL
                -O madkom
                -altname "madkom user"
                -notafter "2020-01-01 00:00:00"
                -notbefore "2020-01-01 00:00:00"
                -save hsm
                -sslot 3
                -skspass 1111
                -salias test_user

                -interslot 2
                -interkspass 1111
                -interalias test_inter
            */

            $command = EXEC_JAVA . " -jar {$this->jarFilePath} ";


        }

}
