<?php

/**
 * MK_Crypt_KeyCert
 *
 * Klasa do obsługi certyfikatów
 *
 * @category	MK_Crypt
 * @package		MK_Crypt_KeyCert
 * @author		bskrzypkowiak
 * @todo przerobic zeby działało ładnie z podaniem typu providera, poprawic komentarze i posprzatać tą klase i z niej dziedziczące
 *
 */
Abstract Class MK_Crypt_KeyCert {

    /**
     * HSM/PCKS12
     * @var String
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
     * Uruchamia jara z podanymi parametrami
     *
     * @param String $params
     * @param bool $load - jezeli true to robi load jezeli false to robi save
     * @param bool $silent - w przypadku ustawinia na true nie wurzuca wyjatków
     * @return array
     */
    protected function executeJar($params, $load = true, $silent=false){
        $load = ($load === true) ? "-load {$this->srcType}" : "-save {$this->srcType}";

        $command = EXEC_JAVA . " -jar {$this->jarFilePath} {$load} {$params} -v";

        try {
            exec($command, $output, $returnCode);

            if ($returnCode != '0' && $silent === false){
                throw new Exception('Niepowiodło się wykonywanie polecenia' . MK_EOL . ((MK_DEBUG) ? ":" . $command . MK_EOL . json_decode($output) : '') );
            }
        }
        catch(Exception $e){
            if($silent === false){
                throw new Exception(MK_EOL . $e->getMessage() . ( (MK_DEBUG) ?  MK_EOL . $e->getTraceAsString() : '') );
            }
        }

        return array(
            'output' => $output,
            'returnCode' => $returnCode
        );
    }

    /**
     * Wyświetlanie listy certyfikatów z HSMA/Pliku w formacie JSON
     *    $ java -jar KeyCert.jar -load TYP -lslot NR -lkspass HASLO -listjson
     *
     * @param $slot
     * @param $kspass
     * @param bool $json
     * @return array|String
     */
     public function getList($slot, $kspass, $json=false){
         $output = null;
         $exec = $this->executeJar("-lslot {$slot} -lkspass {$kspass} -listjson");

         if(array_key_exists(0, $exec['output'])){
             $output = $exec['output'][0];
         }

         return $json === false ? json_decode($output, true) : $output;
     }

    /**
     * Wyświetlanie informacji o certyfikacie na HSMie
     *  $ $java -jar KeyCert.jar -load hsm -lslot 3 -lkspass 1111 -lalias test_user -certdetails
     *
     * @param $slot
     * @param $kspass
     * @param $alias
     *
     * @return string
     */
    public function getInfo($slot, $kspass, $alias){
        $exec = $this->executeJar("-lslot {$slot} -lkspass {$kspass} -lalias {$alias} -certdetails", true, true);

//        Wyświetlanie informacji o certyfikacie na HSMie
//            $ $java -jar KeyCert.jar -load hsm -lslot 3 -lkspass 1111 -lalias test_user -certdetails
//        Wyświetlanie informacji o certyfikacie w pliku PKCS12
//            $ $java -jar KeyCert.jar -load pkcs12 -lfile plik.p12 -lkspass 1111 -lalias test_user -certdetails

        return implode(MK_EOL, $exec['output']);
    }

    /**
     * @param $slot
     * @param $kspass
     * @param $alias
     * @return bool
     */
    public function exist($slot, $kspass, $alias){
        //$ java -jar KeyCert.jar -load hsm -lslot 3 -lkspass 1111 -lalias test_user -check
        $exec = $this->executeJar("-lslot {$slot} -lkspass {$kspass} -lalias {$alias} -check");
//        echo MK_EOL.$exec['returnCode'].MK_EOL;
        return !$exec['returnCode'];
    }


    /**
     * @todo to nie moze tak byc :)
     *
     * @param $name
     * @param $localization
     * @param $countryCode
     * @param $state
     * @param $organization
     * @param $altName
     * @param $dateFrom
     * @param $dateTo
     * @param $slot
     * @param $pin
     * @param $alias
     * @param $interSlot
     * @param $interKspass
     * @param $interAlias
     * @param $serial
     * @internal param $skpass
     */
    public function generate($name, $localization, $countryCode, $state,$organization, $altName, $dateFrom, $dateTo, $slot, $pin, $alias, $interSlot, $interKspass, $interAlias, $serial){

        $command = "-gen -CN '{$name}' -L {$localization} -C {$countryCode} -ST {$state} -O {$organization} -altname '{$altName}' -notafter '{$dateTo}' -notbefore '{$dateFrom}' -sslot {$slot} -skspass {$pin} -salias {$alias} -interslot {$interSlot} -interkspass {$interKspass} -interalias {$interAlias} -sserial {$serial}";

        $this->executeJar($command, false);

    }


}
